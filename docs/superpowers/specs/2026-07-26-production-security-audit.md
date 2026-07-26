# Production Security Audit — Nexus-21

**Date:** 2026-07-26
**Scope:** Full codebase (per request), triggered by a pre-production push. Two passes: (1) a diff-scoped security review of the newly built "Perubahan Kelas" feature (8 commits, 12 ahead of `origin/main`), (2) a whole-codebase audit across auth/session, controller-wide authorization, file upload/external integrations, and Docker/deployment config.
**Method:** Each finding below was independently identified, then re-verified against the actual source/vendor code (file:line evidence, class hierarchies, config values) before inclusion. Only HIGH/MEDIUM-confidence, concretely exploitable issues are listed — no theoretical hardening suggestions.

**Bottom line: do not deploy this compose/entrypoint configuration to production as-is.** Several findings below are full remote-code-execution or full-database-compromise paths that require no more than "have an account" or "reach the host on the docker-published ports."

---

## Part 1 — New feature ("Perubahan Kelas" upload), diff-scoped review

Clean. No HIGH/MEDIUM findings. Authorization (`checkAdminAccess()` on all 4 new actions), SQL injection (Eloquent-only, one static `selectRaw` fragment with no interpolation), XSS (all output escaped, `kelas_baru` values constrained to an existing-`kelas` whitelist before they ever reach storage/view), file handling (managed upload temp path, `mimes:xlsx` validation), and the session-based preview→confirm flow (fully re-validated against live DB state before commit, closing the TOCTOU gap) all check out.

One sub-threshold note (6/10 confidence, did not clear the strict bar for this report but is a real, verified code defect): in `previewPerubahanKelas()`, the `catch (\Throwable $e)` block flashes `$e->getMessage()` to the browser whenever `$e instanceof \RuntimeException` — intended to surface a safe, developer-authored header-validation message. But `Illuminate\Database\QueryException extends \PDOException extends \RuntimeException`, and the same try block also runs Eloquent queries. A genuine DB fault (deadlock, connection blip) during preview would therefore leak raw SQL text, bound parameter values, and the connection name to the browser instead of showing the generic message. Reachable only by an already-authenticated Admin, and only on a genuine backend fault (not attacker-triggerable on demand) — low practical severity, but worth a narrow `instanceof` fix (check the parser's own exception type, not any `RuntimeException`).

---

## Part 2 — Whole-codebase audit

### HIGH severity

#### H1. Signature upload → arbitrary PHP file write → remote code execution
**`app/Http/Controllers/PelanggaranController.php:176-209, 373-426`**

`ttd_murid` (student signature) is validated only as `["nullable", "string"]` — never through Laravel's `file`/`image`/`mimes` rules. The controller manually parses a data-URI, takes the `image/xxx` token **directly from attacker input** with no whitelist, and writes the raw decoded bytes to `storage/app/public/ttd-pelanggaran/` (web-reachable via the `storage:link` symlink nginx serves).

**Exploit:** Any `Admin`/`TATIB` account submits the pelanggaran form with `ttd_murid = data:image/php;base64,<base64 of "<?php system($_GET['c']); ?>">`. The server writes a `.php` file into a directory nginx executes PHP from. Visiting `.../storage/ttd-pelanggaran/<name>.php?c=id` runs code as the web server user — full RCE, DB access, `.env` secrets, container pivot.

**Fix:** Route `ttd_murid` through the same validated pipeline as the `bukti` field (which is safe — see below), or decode and re-encode through an image library that only ever emits a hardcoded extension (e.g. always `.png`), verify decoded bytes are a real image (`getimagesize()`), and confirm the resolved path stays inside the intended directory. Apply in both `store()` and `update()`.

*(For contrast: the `bukti` evidence upload uses Laravel's `image` validation rule, and Laravel 12.38.1 — the version actually installed — blocks `.php`-family extensions inside that validator. That field is not exploitable. `ttd_murid` bypasses the validator entirely, which is why it is.)*

#### H2. `APP_DEBUG=true` is hardcoded and cannot be turned off via `.env`
**`docker-compose.yml:17-19`**

The `app` service sets `environment: [APP_ENV=local, APP_DEBUG=true]` directly, with no `env_file:` pulling in `.env.docker` for that service. Docker Compose environment variables take priority over anything inside the container's `.env` (phpdotenv never overwrites an already-set process variable). **There is no way to turn this off short of editing the compose file.**

**Impact:** Any unhandled exception on the live site renders Laravel's full debug page to anonymous visitors — stack traces, file paths, and resolved config/secrets.

**Fix:** Remove the hardcoded `APP_ENV`/`APP_DEBUG` from `docker-compose.yml`; use a production compose file (or profile) with `APP_ENV=production`, `APP_DEBUG=false`, and let `.env.docker` be the actual source of truth.

#### H3. Production image ships dev dependencies, which (combined with H2) activates unauthenticated dev tooling
**`Dockerfile:52-57`, `docker/entrypoint.sh:7-12`**

`composer install` runs without `--no-dev`, so `laravel/boost` (a `require-dev` package) ships in the production image. Its service provider activates whenever `APP_DEBUG === true` — guaranteed by H2 — which:
- Injects a `<script>` into **every HTML response** served to real users.
- Registers `POST /_boost/browser-logs`, explicitly exempted from CSRF, with no authentication, writing arbitrary attacker-controlled JSON straight into `storage/logs/browser.log`.

**Fix:** Add `--no-dev` to both `composer install` invocations. Fixing H2 alone also closes this, but both should be fixed independently (defense in depth).

#### H4. `chmod -R 777` on storage/cache at every container start
**`docker/entrypoint.sh:42`**

Runs unconditionally, overriding the correctly-scoped `775 www-data:www-data` set at build time. Since the app directory is bind-mounted into both `app` and `nginx` containers, this makes session files, cached compiled views, and publicly-served uploads writable by any process that can reach the shared mount — not just the web server user.

**Fix:** Delete this line. The build-time permissions are already correct and sufficient.

#### H5. Database and phpMyAdmin published to the host with root credentials, documented as the intended setup
**`docker-compose.yml:56-57, 88-104`, `DOCKER.md:82-91`**

`db` is published on `3306:3306`, `phpmyadmin` on `8080:80`, logging in as `root`/`$DB_PASSWORD`. `DOCKER.md` documents this as the expected end state with no production-hardening caveat, and the example password (`DB_PASSWORD=secret` in `.env.docker.example`) is weak with no rotation instruction.

**Impact:** If deployed to any host without an external firewall blocking these ports, both a full DB-admin web UI and the raw MySQL protocol are reachable from the internet as `root`. This is a direct path to full compromise of all student/school data.

**Fix:** Remove `ports:` publishing for `db` and `phpmyadmin` — both `app`/`nginx` already reach `db` over the internal `nexus_network`. If phpMyAdmin access is genuinely needed, put it behind an SSH tunnel/VPN, never a public port.

#### H6. Documented setup creates the Admin account with a hardcoded, weak password
**`DOCKER.md:26-38, 46`**

The official first-run instructions create `username: admin`, `password: bcrypt('password')`, `role: Admin`, and state the login as `admin`/`password` with no instruction anywhere to change it.

**Fix:** Generate a random password at setup time (print once, require rotation on first login), and add an explicit warning in `DOCKER.md`.

#### H7. OTP login step-2 has no rate limiting — full 2FA bypass by brute force
**`routes/auth.php:29-30`, `app/Http/Controllers/Auth/OtpController.php:19-68`**

`POST /verify-otp` has no throttle (contrast with the email-verification routes in the same file, which explicitly use `throttle:6,1`), no attempt counter, and no lockout. The 6-digit code (1,000,000 possibilities) stays valid for its full 5-minute window regardless of how many wrong guesses are submitted.

**Exploit:** An attacker who already has valid credentials (credential stuffing, phishing, password reuse) completes step 1, then scripts repeated guesses against `/verify-otp` within the 5-minute window — entirely feasible with a handful of concurrent requests against a cheap string-compare endpoint.

**Fix:** Add throttle middleware, track failed attempts per session/user, lock out and force re-authentication after ~5 wrong guesses.

*(Two related, lower-severity issues in the same flow, both worth fixing together: the OTP is generated with `mt_rand()` — not cryptographically secure, should be `random_int()` — and the generated OTP value is written in cleartext to `storage/logs/laravel.log`, `OtpController.php:80`, a second full-bypass path for anyone with log access. Also flagged below as M-severity but grouped here since they compound H7.)*

#### H8. `ReportController` has zero access control — any role can pull every class's data
**`app/Http/Controllers/ReportController.php:17,34`**

Unlike every other data-bearing controller in the app, `index()`/`generate()` never check the caller's role or scope results to the caller's own class.

**Exploit:** A `Walikelas` account (who should only ever see their own class, per the pattern used everywhere else) calls `POST /report/generate` with a `kelas_list`/`selected_level` covering the whole school and receives a PDF of every student's name, gender, and full violation history school-wide.

**Fix:** Apply the same class-scoping already used in `PelanggaranController::index()`/`rekap()` when `role === 'Walikelas'`.

#### H9. `PelanggaranController::show()` is missing the role check every sibling action has — IDOR
**`app/Http/Controllers/PelanggaranController.php:233`**

`create/store/edit/update/destroy` all block `Walikelas`; `show()` doesn't. Violation records use a plain sequential auto-increment ID.

**Exploit:** A `Walikelas` visits `/pelanggaran/1`, `/pelanggaran/2`, … and views full violation detail — including uploaded evidence photos and student signatures — for students outside their own class.

**Fix:** Add the same check, or scope to the Walikelas's own class and 403 otherwise.

### MEDIUM severity

#### M1. `getMurid()` API leaks any student's data to any authenticated user regardless of class
`app/Http/Controllers/PelanggaranController.php:468` (`GET /api/pelanggaran/murid/{nis}`) — no role/class scoping, unlike the rest of the controller. A `Walikelas` can query any NIS directly and get name/gender/kelas/violation points for students outside their class.

#### M2. `/clear-cache` is a fully unauthenticated administrative endpoint
`routes/web.php:17-20` — defined outside the `auth` middleware group, runs `Artisan::call('optimize:clear')` for anyone, no login required. Remove before production, or gate behind `auth` + Admin.

#### M3. `trustProxies(at: '*')` lets a client-supplied `X-Forwarded-For` header defeat the login throttle
`bootstrap/app.php:14` — the 5-attempt login rate limiter keys on `$request->ip()`, which resolves through the (fully-trusted) forwarded-for header. If the app is reachable in any topology where that header isn't stripped/overwritten by a real trusted proxy first, an attacker gets a fresh 5-attempt bucket per spoofed IP, enabling unlimited password brute-forcing against a target username. Set `trustProxies` to the actual proxy's IP/CIDR, not `*`.

#### M4. WhatsApp gateway `/send-message` — no auth, published to the host
`whatsapp-server.js:68-101`, `docker-compose.yml:79-83` — meant to be internal-only (the app reaches it via `http://whatsapp:3000` on the docker network) but also published on the host with zero authentication on the endpoint itself. Anyone who can reach the port can send arbitrary WhatsApp messages from the school's connected number. Remove the host port mapping; add a shared-secret header as defense in depth.

---

## Suggested priority order

1. **Before any production push:** H1 (RCE), H2 (debug mode stuck on), H5 (DB/phpMyAdmin public with root) — each alone is a full-compromise path.
2. **Same batch, cheap fixes:** H3 (`--no-dev`), H4 (`chmod 777`), H6 (rotate/randomize the seeded admin password).
3. **Before real users rely on login/2FA:** H7 + the grouped OTP weak-PRNG/plaintext-log issues.
4. **Before Walikelas accounts are used day-to-day:** H8, H9, M1 (all IDOR/missing-scoping — a Walikelas seeing another class's data is a real privacy issue for a school app handling minors' disciplinary records).
5. **Cleanup:** M2, M3, M4.

None of these touch the newly built Perubahan Kelas feature or require reworking it — they're all pre-existing code and infrastructure config.

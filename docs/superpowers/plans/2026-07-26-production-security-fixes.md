# Production Security Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close all 9 High and 4 Medium severity findings from `docs/superpowers/specs/2026-07-26-production-security-audit.md`, plus one sub-threshold finding from the same audit's Part 1, before this app is pushed to production.

**Architecture:** Ten independent fixes across PHP controllers, auth/routing config, Docker/deployment config, and the Node.js WhatsApp gateway. Each task targets exactly one finding (or a tightly related cluster of findings in the same file). No task depends on another task's code changes — they touch different files or disjoint sections of shared files (`docker-compose.yml`, `.env.docker.example`).

**Tech Stack:** Laravel 12, PHP 8.2, Pest 4 (existing tests use two conventions — plain `test()` functions in `tests/Feature/PerubahanKelasTest.php`, and PHPUnit-style classes in `tests/Feature/OtpLoginTest.php`; match whichever convention the file you're editing already uses), Docker Compose, Node.js/Express (`whatsapp-server.js`, no test framework currently configured for it).

## Global Constraints

- **Known environment blocker, same as the previous plan in this repo:** `php artisan test` fails suite-wide during `RefreshDatabase` setup because of a pre-existing, unrelated migration (`database/migrations/2025_11_13_080002_alter_pelanggaran_jenis_to_varchar.php`) using MySQL-only syntax against the SQLite test DB. This is NOT something any task in this plan should fix. Every task must verify via `php -l` (syntax), `php artisan route:list` (route registration), careful hand-tracing of test logic against the diff, and — where the fix involves parsing/crypto/string logic — a standalone throwaway PHP or Node script run directly (not through Laravel/artisan) to empirically verify the specific behavior, mirroring the approach already proven in this repo's prior security fix rounds. Report status as DONE_WITH_CONCERNS noting this blocker; it is not a reason to alter scope.
- This app runs in Docker via `docker-compose.yml` (services: `app`, `nginx`, `db`, `whatsapp`, `phpmyadmin`, all on an internal `nexus_network` bridge network); `nginx` is the only service meant to be reachable from outside the host once these fixes land.
- Every controller access-control fix must reuse this codebase's existing pattern: an inline `if (auth()->user()->role === "Walikelas") { redirect ...with error }` check (as already used by every other action in `PelanggaranController`), not a new middleware or policy class — stay consistent with the surrounding code.
- No new Composer or npm dependencies. Every fix uses either a language/framework built-in (`random_int()`, `getimagesizefromstring()`, Node's `crypto` module, Laravel's `Http::withToken()`) or a small new first-party class (`app/Exceptions/PerubahanKelasFileException`).
- Do not touch the already-reviewed "Perubahan Kelas" feature's logic except the one narrow, explicitly-scoped exception-type fix in Task 10.
- Docker/infrastructure changes (Tasks 7–9) must not break the existing boot sequence (`entrypoint.sh`'s migrate/cache/storage-link steps) — verify by reading the full script after editing, not just the changed lines.

---

### Task 1: Fix RCE via signature (`ttd_murid`) upload

**Files:**
- Modify: `app/Http/Controllers/PelanggaranController.php:176-209` (`store()`), `:373-421` (`update()`)
- Test: `tests/Feature/PelanggaranSignatureUploadTest.php` (create)

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing new. `Pelanggaran::ttd_murid` continues to store a `"ttd-pelanggaran/<filename>.png"` relative path exactly as before — only the filename's extension and the pre-write validation change.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PelanggaranSignatureUploadTest.php`:

```php
<?php

use App\Models\JenisPelanggaran;
use App\Models\Murid;
use App\Models\Pelanggaran;
use App\Models\User;

function makeStaffUser(): User
{
    return User::factory()->create(['role' => 'TATIB']);
}

function makeSignatureTestMurid(string $nis = '1111111111'): Murid
{
    return Murid::create([
        'NIS' => $nis,
        'nama_lengkap' => 'Budi Testoso',
        'gender' => 'Laki-laki',
        'kelas' => null,
        'status' => 'Aktif',
    ]);
}

function makeSignatureTestJenis(): JenisPelanggaran
{
    return JenisPelanggaran::create([
        'nama' => 'Terlambat',
        'poin' => 5,
    ]);
}

function tinyPngDataUri(): string
{
    // A valid, minimal 1x1 transparent PNG.
    $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

    return 'data:image/png;base64,' . base64_encode($bytes);
}

function maliciousPhpPayloadDataUri(): string
{
    $phpCode = "<?php system(\$_GET['c']); ?>";

    return 'data:image/php;base64,' . base64_encode($phpCode);
}

function assertNoPhpFilesInTtdStorage(): void
{
    $storagePath = storage_path('app/public/ttd-pelanggaran');
    if (! is_dir($storagePath)) {
        return;
    }

    foreach (glob($storagePath . '/*') as $file) {
        expect(pathinfo($file, PATHINFO_EXTENSION))->not->toBe('php');
    }
}

test('store rejects a PHP payload disguised as ttd_murid and writes no php file', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid();
    $jenis = makeSignatureTestJenis();

    $response = $this->actingAs($user)->post(route('pelanggaran.store'), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => maliciousPhpPayloadDataUri(),
    ]);

    $response->assertRedirect(route('pelanggaran.index'));

    $pelanggaran = Pelanggaran::first();
    expect($pelanggaran)->not->toBeNull();
    expect($pelanggaran->ttd_murid)->toBeNull();

    assertNoPhpFilesInTtdStorage();
});

test('store saves a genuine PNG signature with a hardcoded .png extension', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid();
    $jenis = makeSignatureTestJenis();

    $response = $this->actingAs($user)->post(route('pelanggaran.store'), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => tinyPngDataUri(),
    ]);

    $response->assertRedirect(route('pelanggaran.index'));

    $pelanggaran = Pelanggaran::first();
    expect($pelanggaran->ttd_murid)->not->toBeNull();
    expect($pelanggaran->ttd_murid)->toEndWith('.png');

    $fullPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
    expect(file_exists($fullPath))->toBeTrue();

    @unlink($fullPath);
});

test('update rejects a PHP payload disguised as ttd_murid and writes no php file', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid('2222222222');
    $jenis = makeSignatureTestJenis();

    $pelanggaran = Pelanggaran::create([
        'NIS' => $murid->NIS,
        'nama_murid' => $murid->nama_lengkap,
        'gender' => $murid->gender,
        'kelas' => $murid->kelas,
        'username' => $user->username,
        'jenis_pelanggaran' => $jenis->nama,
        'poin' => $jenis->poin,
        'tanggal' => now()->toDateString(),
        'semester' => '2025/2026-1',
    ]);

    $response = $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => maliciousPhpPayloadDataUri(),
    ]);

    $response->assertRedirect(route('pelanggaran.index'));

    $pelanggaran->refresh();
    expect($pelanggaran->ttd_murid)->toBeNull();

    assertNoPhpFilesInTtdStorage();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PelanggaranSignatureUploadTest`
Expected: FAIL — either at `RefreshDatabase` setup (the known pre-existing migration blocker — confirm the error is exactly that) or, if you have a way to run against a working DB, FAIL because the current code writes a `.php` file / doesn't reject the payload.

- [ ] **Step 3: Fix `store()`**

In `app/Http/Controllers/PelanggaranController.php`, find (inside `store()`):

```php
        $ttdSaved = false;
        if (! empty($validated["ttd_murid"])) {
            $image_parts = explode(";base64,", $validated["ttd_murid"]);
            if (count($image_parts) === 2) {
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1] ?? "png";
                $image_base64 = base64_decode($image_parts[1], true);
                if ($image_base64 !== false && strlen($image_base64) > 0) {
                    $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? "murid");
                    $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                    $filename = "{$safeName}_{$date}_ttd.{$image_type}";

                    // Simpan ke storage/app/public/ttd-pelanggaran/
                    $storagePath = storage_path('app/public/ttd-pelanggaran');
                    if (!file_exists($storagePath)) {
                        mkdir($storagePath, 0755, true);
                    }

                    $fullPath = $storagePath . '/' . $filename;
                    $saved = file_put_contents($fullPath, $image_base64);

                    if ($saved !== false) {
                        $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                        $ttdSaved = true;
                    }
                } else {
                    unset($validated["ttd_murid"]);
                }
            } else {
                unset($validated["ttd_murid"]);
            }
        } else {
            unset($validated["ttd_murid"]);
        }
```

Replace with:

```php
        $ttdSaved = false;
        if (! empty($validated["ttd_murid"])) {
            $image_parts = explode(";base64,", $validated["ttd_murid"]);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1], true);
                if ($image_base64 !== false && strlen($image_base64) > 0 && getimagesizefromstring($image_base64) !== false) {
                    $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? "murid");
                    $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                    // Extension is always .png — never derived from attacker-supplied input.
                    $filename = "{$safeName}_{$date}_ttd.png";

                    // Simpan ke storage/app/public/ttd-pelanggaran/
                    $storagePath = storage_path('app/public/ttd-pelanggaran');
                    if (!file_exists($storagePath)) {
                        mkdir($storagePath, 0755, true);
                    }

                    $fullPath = $storagePath . '/' . $filename;
                    $saved = file_put_contents($fullPath, $image_base64);

                    if ($saved !== false) {
                        $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                        $ttdSaved = true;
                    }
                } else {
                    unset($validated["ttd_murid"]);
                }
            } else {
                unset($validated["ttd_murid"]);
            }
        } else {
            unset($validated["ttd_murid"]);
        }
```

- [ ] **Step 4: Fix `update()`**

In the same file, find (inside `update()`):

```php
                if (strpos($validated["ttd_murid"], ';base64,') !== false) {
                    $image_parts = explode(";base64,", $validated["ttd_murid"]);
                    if (count($image_parts) == 2) {
                        // Hapus file lama jika ada
                        if ($pelanggaran->ttd_murid) {
                            $oldPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1] ?? "png";
                        $image_base64 = base64_decode($image_parts[1]);

                        $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? $pelanggaran->nama_murid ?? "murid");
                        $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                        $filename = "{$safeName}_{$date}_ttd.{$image_type}";

                        // Simpan ke storage/app/public/ttd-pelanggaran/
                        $storagePath = storage_path('app/public/ttd-pelanggaran');
                        if (!file_exists($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $fullPath = $storagePath . '/' . $filename;
                        file_put_contents($fullPath, $image_base64);
                        $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                    } else {
                        // Base64 tidak valid, pertahankan nilai lama
                        unset($validated["ttd_murid"]);
                    }
                } elseif ($validated["ttd_murid"] === "CLEAR") {
```

Replace with:

```php
                if (strpos($validated["ttd_murid"], ';base64,') !== false) {
                    $image_parts = explode(";base64,", $validated["ttd_murid"]);
                    $image_base64 = count($image_parts) === 2
                        ? base64_decode($image_parts[1], true)
                        : false;

                    if ($image_base64 !== false && strlen($image_base64) > 0 && getimagesizefromstring($image_base64) !== false) {
                        // Hapus file lama jika ada
                        if ($pelanggaran->ttd_murid) {
                            $oldPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? $pelanggaran->nama_murid ?? "murid");
                        $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                        // Extension is always .png — never derived from attacker-supplied input.
                        $filename = "{$safeName}_{$date}_ttd.png";

                        // Simpan ke storage/app/public/ttd-pelanggaran/
                        $storagePath = storage_path('app/public/ttd-pelanggaran');
                        if (!file_exists($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $fullPath = $storagePath . '/' . $filename;
                        file_put_contents($fullPath, $image_base64);
                        $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                    } else {
                        // Base64 tidak valid, pertahankan nilai lama
                        unset($validated["ttd_murid"]);
                    }
                } elseif ($validated["ttd_murid"] === "CLEAR") {
```

- [ ] **Step 5: Verify with a standalone PHP probe (no Laravel needed)**

```bash
php -r '
$malicious = base64_decode(base64_encode("<?php system(\$_GET[\"c\"]); ?>"));
var_dump(getimagesizefromstring($malicious) !== false); // must print bool(false)

$png = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=");
var_dump(getimagesizefromstring($png) !== false); // must print bool(true)
'
```

Expected: `bool(false)` then `bool(true)`.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter=PelanggaranSignatureUploadTest`
Expected: PASS if the environment blocker is somehow not hit; otherwise confirm the failure is exactly the pre-existing `MODIFY COLUMN` migration error and note this in the report, alongside the Step 5 probe output as evidence the fix logic is correct.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PelanggaranController.php tests/Feature/PelanggaranSignatureUploadTest.php
git commit -m "fix: prevent arbitrary file write via ttd_murid signature upload (RCE)"
```

---

### Task 11: Fix path-traversal → arbitrary file deletion in `ttd_murid` passthrough (discovered during Task 1 review)

**Files:**
- Modify: `app/Http/Controllers/PelanggaranController.php:372-426` (`update()`'s signature-handling block), plus a new private helper method
- Test: `tests/Feature/PelanggaranSignatureUploadTest.php` (append)

**Interfaces:**
- Consumes: `makeStaffUser()`, `makeSignatureTestMurid()`, `makeSignatureTestJenis()`, `tinyPngDataUri()` from Task 1's test file.
- Produces: new private method `safeDeleteTtdMurid(?string $relativePath): void` on `PelanggaranController`, used by both delete sites in `update()`.

**Context:** Task 1's review found that `update()`'s signature-handling `else` branch (for "this is an existing file path, keep it unchanged") does no validation at all on the client-supplied `ttd_murid` string before storing it, and that stored string is later passed unvalidated into two raw `unlink()` calls (one when re-uploading a new signature, one on `"CLEAR"`). Since Laravel's Flysystem-based `Storage::delete()` (used correctly elsewhere in this same controller, e.g. `destroy()`) blocks `..` traversal but a raw `unlink()` does not, an attacker with any Admin/TATIB account can: (1) PUT `ttd_murid = "../../../../../var/www/public/index.php"` (or any path) in one request — it passes through untouched and gets saved to the DB; (2) PUT `ttd_murid = "CLEAR"` in a follow-up request — the stored traversal path is fed straight into `unlink()`, deleting an arbitrary file the web server user can write to. This task closes both the write-time hole (validate the passthrough value) and the delete-time hole (validate again immediately before any `unlink()`, as defense in depth against any other path that might reach a bad value in the future). It also fixes a related quality gap the same review found: `update()`'s new-signature-write branch doesn't check `file_put_contents()`'s return value before committing the new path to `$validated` (unlike `store()`, which already does).

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PelanggaranSignatureUploadTest.php`:

```php
test('update rejects a path-traversal value in ttd_murid passthrough', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid('8888888888');
    $jenis = makeSignatureTestJenis();

    $pelanggaran = Pelanggaran::create([
        'NIS' => $murid->NIS,
        'nama_murid' => $murid->nama_lengkap,
        'gender' => $murid->gender,
        'kelas' => $murid->kelas,
        'username' => $user->username,
        'jenis_pelanggaran' => $jenis->nama,
        'poin' => $jenis->poin,
        'tanggal' => now()->toDateString(),
        'semester' => '2025/2026-1',
        'ttd_murid' => 'ttd-pelanggaran/existing_signature.png',
    ]);

    $response = $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => '../../../../../public/index.php',
    ]);

    $response->assertRedirect(route('pelanggaran.index'));

    $pelanggaran->refresh();
    expect($pelanggaran->ttd_murid)->toBe('ttd-pelanggaran/existing_signature.png');
});

test('CLEAR never deletes a file outside ttd-pelanggaran even if a bad path reached the DB', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid('1212121212');
    $jenis = makeSignatureTestJenis();

    $pelanggaran = Pelanggaran::create([
        'NIS' => $murid->NIS,
        'nama_murid' => $murid->nama_lengkap,
        'gender' => $murid->gender,
        'kelas' => $murid->kelas,
        'username' => $user->username,
        'jenis_pelanggaran' => $jenis->nama,
        'poin' => $jenis->poin,
        'tanggal' => now()->toDateString(),
        'semester' => '2025/2026-1',
    ]);

    $canaryPath = storage_path('app/public_test_canary.txt');
    file_put_contents($canaryPath, 'do not delete me');

    // Simulate a bad value already in the DB (e.g. from before this fix existed),
    // bypassing the app layer entirely, to prove the delete-time guard is what
    // protects the canary — not just the write-time validation.
    $pelanggaran->forceFill(['ttd_murid' => '../public_test_canary.txt'])->save();

    $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => 'CLEAR',
    ]);

    expect(file_exists($canaryPath))->toBeTrue();

    @unlink($canaryPath);
});

test('update preserves a well-formed existing ttd_murid path unchanged', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid('9999999999');
    $jenis = makeSignatureTestJenis();

    $pelanggaran = Pelanggaran::create([
        'NIS' => $murid->NIS,
        'nama_murid' => $murid->nama_lengkap,
        'gender' => $murid->gender,
        'kelas' => $murid->kelas,
        'username' => $user->username,
        'jenis_pelanggaran' => $jenis->nama,
        'poin' => $jenis->poin,
        'tanggal' => now()->toDateString(),
        'semester' => '2025/2026-1',
        'ttd_murid' => 'ttd-pelanggaran/existing_signature.png',
    ]);

    $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => 'ttd-pelanggaran/existing_signature.png',
    ]);

    $pelanggaran->refresh();
    expect($pelanggaran->ttd_murid)->toBe('ttd-pelanggaran/existing_signature.png');
});

test('update saves a genuine PNG signature with a hardcoded .png extension', function () {
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid('1010101010');
    $jenis = makeSignatureTestJenis();

    $pelanggaran = Pelanggaran::create([
        'NIS' => $murid->NIS,
        'nama_murid' => $murid->nama_lengkap,
        'gender' => $murid->gender,
        'kelas' => $murid->kelas,
        'username' => $user->username,
        'jenis_pelanggaran' => $jenis->nama,
        'poin' => $jenis->poin,
        'tanggal' => now()->toDateString(),
        'semester' => '2025/2026-1',
    ]);

    $response = $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => tinyPngDataUri(),
    ]);

    $response->assertRedirect(route('pelanggaran.index'));

    $pelanggaran->refresh();
    expect($pelanggaran->ttd_murid)->not->toBeNull();
    expect($pelanggaran->ttd_murid)->toEndWith('.png');

    $fullPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
    expect(file_exists($fullPath))->toBeTrue();

    @unlink($fullPath);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PelanggaranSignatureUploadTest`
Expected: FAIL — either the known migration blocker, or (if runnable) the first two new tests fail because no validation/guard exists yet.

- [ ] **Step 3: Add the `safeDeleteTtdMurid` helper and fix the signature-handling block**

In `app/Http/Controllers/PelanggaranController.php`, find the entire block (inside `update()`):

```php
        // Handle signature update
        if (isset($validated["ttd_murid"])) {
            if (!empty($validated["ttd_murid"])) {
                // Check if it's new base64 data (contains ";base64,")
                if (strpos($validated["ttd_murid"], ';base64,') !== false) {
                    $image_parts = explode(";base64,", $validated["ttd_murid"]);
                    $image_base64 = count($image_parts) === 2
                        ? base64_decode($image_parts[1], true)
                        : false;

                    if ($image_base64 !== false && strlen($image_base64) > 0 && getimagesizefromstring($image_base64) !== false) {
                        // Hapus file lama jika ada
                        if ($pelanggaran->ttd_murid) {
                            $oldPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? $pelanggaran->nama_murid ?? "murid");
                        $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                        // Extension is always .png — never derived from attacker-supplied input.
                        $filename = "{$safeName}_{$date}_ttd.png";

                        // Simpan ke storage/app/public/ttd-pelanggaran/
                        $storagePath = storage_path('app/public/ttd-pelanggaran');
                        if (!file_exists($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $fullPath = $storagePath . '/' . $filename;
                        file_put_contents($fullPath, $image_base64);
                        $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                    } else {
                        // Base64 tidak valid, pertahankan nilai lama
                        unset($validated["ttd_murid"]);
                    }
                } elseif ($validated["ttd_murid"] === "CLEAR") {
                    // Hapus file lama jika ada
                    if ($pelanggaran->ttd_murid) {
                        $oldPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $validated["ttd_murid"] = null;
                } else {
                    // Ini adalah path file yang sudah ada (misal: "ttd-pelanggaran/xxx.jpg")
                    // Pertahankan nilai ini tanpa perubahan
                }
            } else {
                // Nilai kosong, hapus dari array agar tidak menimpa nilai lama
                unset($validated["ttd_murid"]);
            }
        }
```

Replace with:

```php
        // Handle signature update
        if (isset($validated["ttd_murid"])) {
            if (!empty($validated["ttd_murid"])) {
                // Check if it's new base64 data (contains ";base64,")
                if (strpos($validated["ttd_murid"], ';base64,') !== false) {
                    $image_parts = explode(";base64,", $validated["ttd_murid"]);
                    $image_base64 = count($image_parts) === 2
                        ? base64_decode($image_parts[1], true)
                        : false;

                    if ($image_base64 !== false && strlen($image_base64) > 0 && getimagesizefromstring($image_base64) !== false) {
                        // Hapus file lama jika ada
                        $this->safeDeleteTtdMurid($pelanggaran->ttd_murid);

                        $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? $pelanggaran->nama_murid ?? "murid");
                        $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                        // Extension is always .png — never derived from attacker-supplied input.
                        $filename = "{$safeName}_{$date}_ttd.png";

                        // Simpan ke storage/app/public/ttd-pelanggaran/
                        $storagePath = storage_path('app/public/ttd-pelanggaran');
                        if (!file_exists($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $fullPath = $storagePath . '/' . $filename;
                        if (file_put_contents($fullPath, $image_base64) !== false) {
                            $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                        } else {
                            unset($validated["ttd_murid"]);
                        }
                    } else {
                        // Base64 tidak valid, pertahankan nilai lama
                        unset($validated["ttd_murid"]);
                    }
                } elseif ($validated["ttd_murid"] === "CLEAR") {
                    // Hapus file lama jika ada
                    $this->safeDeleteTtdMurid($pelanggaran->ttd_murid);
                    $validated["ttd_murid"] = null;
                } else {
                    // Ini adalah path file yang sudah ada (misal: "ttd-pelanggaran/xxx.png"),
                    // dikirim balik oleh form edit lewat hidden input. Validasi ketat di sini
                    // karena nilai ini datang langsung dari klien dan dipakai untuk unlink()
                    // pada request berikutnya — tanpa ini, string path-traversal bisa tersimpan
                    // lalu menghapus file sembarang saat "CLEAR" dikirim.
                    if (! preg_match('/^ttd-pelanggaran\/[A-Za-z0-9_]+\.png$/', $validated["ttd_murid"])) {
                        unset($validated["ttd_murid"]);
                    }
                    // Jika valid, biarkan nilai ini tanpa perubahan (path file yang sudah ada).
                }
            } else {
                // Nilai kosong, hapus dari array agar tidak menimpa nilai lama
                unset($validated["ttd_murid"]);
            }
        }
```

Then add this private method to the class (e.g. directly after `update()`, before `destroy()`):

```php
    /**
     * Delete a stored ttd_murid file, refusing anything that isn't a plain
     * filename inside the ttd-pelanggaran directory. This is a second guard
     * in addition to write-time validation, in case a value ever reaches the
     * DB some other way (e.g. a row written before this validation existed).
     */
    private function safeDeleteTtdMurid(?string $relativePath): void
    {
        if (! $relativePath || ! preg_match('/^ttd-pelanggaran\/[A-Za-z0-9_]+\.png$/', $relativePath)) {
            return;
        }

        $fullPath = storage_path('app/public/' . $relativePath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
```

- [ ] **Step 4: Verify with a standalone PHP probe**

```bash
php -r '
var_dump((bool) preg_match("/^ttd-pelanggaran\/[A-Za-z0-9_]+\.png\$/", "../../../../../public/index.php")); // expect bool(false)
var_dump((bool) preg_match("/^ttd-pelanggaran\/[A-Za-z0-9_]+\.png\$/", "../public_test_canary.txt")); // expect bool(false)
var_dump((bool) preg_match("/^ttd-pelanggaran\/[A-Za-z0-9_]+\.png\$/", "ttd-pelanggaran/existing_signature.png")); // expect bool(true)
'
```

Expected: `bool(false)`, `bool(false)`, `bool(true)`.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PelanggaranSignatureUploadTest`
Expected: PASS if runnable; otherwise confirm the known migration blocker and hand-trace all tests in this file (both Task 1's and this task's) against the final diff.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PelanggaranController.php tests/Feature/PelanggaranSignatureUploadTest.php
git commit -m "fix: close path-traversal arbitrary file deletion in ttd_murid passthrough"
```

---

### Task 2: Fix `ReportController` missing access control

**Files:**
- Modify: `app/Http/Controllers/ReportController.php:17-32` (`index()`), `:34-54` (`generate()`)
- Test: `tests/Feature/ReportAccessControlTest.php` (create)

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing new — `generate()`'s public contract (route, input names, response) is unchanged; only which `kelas_list`/`selected_level` values a `Walikelas` caller is allowed to actually use is restricted.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ReportAccessControlTest.php`:

```php
<?php

use App\Models\Kelas;
use App\Models\Murid;
use App\Models\User;

test('walikelas cannot generate a report for a class they do not own', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    Kelas::create(['kelas' => 'X-1', 'username' => $walikelas->username]);
    Kelas::create(['kelas' => 'X-2']);

    Murid::create([
        'NIS' => '2222222222',
        'nama_lengkap' => 'Murid Kelas Lain',
        'gender' => 'Laki-laki',
        'kelas' => 'X-2',
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($walikelas)->post(route('report.generate'), [
        'mode' => 'kelas',
        'semester' => '2025/2026-1',
        'kelas_list' => ['X-2'],
    ]);

    $response->assertSessionHas('error');
});

test('walikelas can generate a report scoped to their own class', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    Kelas::create(['kelas' => 'X-1', 'username' => $walikelas->username]);

    Murid::create([
        'NIS' => '3333333333',
        'nama_lengkap' => 'Murid Sendiri',
        'gender' => 'Perempuan',
        'kelas' => 'X-1',
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($walikelas)->post(route('report.generate'), [
        'mode' => 'kelas',
        'semester' => '2025/2026-1',
        'kelas_list' => ['X-1'],
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('walikelas selecting a whole level only gets their own class within that level', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    Kelas::create(['kelas' => 'X-1', 'username' => $walikelas->username]);
    Kelas::create(['kelas' => 'X-2']);

    Murid::create([
        'NIS' => '4444444444',
        'nama_lengkap' => 'Murid X1',
        'gender' => 'Laki-laki',
        'kelas' => 'X-1',
        'status' => 'Aktif',
    ]);
    Murid::create([
        'NIS' => '5555555555',
        'nama_lengkap' => 'Murid X2',
        'gender' => 'Perempuan',
        'kelas' => 'X-2',
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($walikelas)->post(route('report.generate'), [
        'mode' => 'kelas',
        'semester' => '2025/2026-1',
        'selected_level' => 'X',
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    // Cannot easily assert PDF body content here — the fix's correctness for this
    // scenario also relies on the unit-level reasoning documented in Step 3 below.
});

test('admin can generate a report for any class', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    Kelas::create(['kelas' => 'X-1']);

    Murid::create([
        'NIS' => '6666666666',
        'nama_lengkap' => 'Murid Any',
        'gender' => 'Laki-laki',
        'kelas' => 'X-1',
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($admin)->post(route('report.generate'), [
        'mode' => 'kelas',
        'semester' => '2025/2026-1',
        'kelas_list' => ['X-1'],
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ReportAccessControlTest`
Expected: FAIL — either at the known `RefreshDatabase` migration blocker, or (if runnable) the first test fails because no access restriction exists yet.

- [ ] **Step 3: Fix `generate()`**

In `app/Http/Controllers/ReportController.php`, find:

```php
    public function generate(Request $request)
    {
        $request->validate([
            'mode'     => 'required|in:kelas,murid',
            'semester' => 'required|string',
        ]);

        // Perbesar limit untuk report besar
        ini_set('memory_limit', '512M');
        set_time_limit(300);
```

Replace with:

```php
    public function generate(Request $request)
    {
        $request->validate([
            'mode'     => 'required|in:kelas,murid',
            'semester' => 'required|string',
        ]);

        $user = auth()->user();
        if ($user && $user->role === 'Walikelas') {
            $ownKelas = Kelas::where('username', $user->username)->pluck('kelas')->toArray();

            if (empty($ownKelas)) {
                return back()->with('error', 'Anda tidak memiliki kelas yang dikelola.');
            }

            $selectedLevel = $request->input('selected_level');

            if ($selectedLevel) {
                // Restrict the level selection to only the Walikelas's own classes
                // within that level, then route through kelas_list like a normal
                // selection so generatePerKelas()/generatePerMurid() never see the
                // unrestricted level and re-derive the full class list themselves.
                $request->merge([
                    'kelas_list' => array_values(array_filter(
                        $ownKelas,
                        fn ($k) => str_starts_with($k, $selectedLevel . '-')
                    )),
                    'selected_level' => null,
                ]);
            } else {
                $selectedKelas = $request->input('kelas_list', []);
                $request->merge([
                    'kelas_list' => array_values(array_intersect($selectedKelas, $ownKelas)),
                ]);
            }
        }

        // Perbesar limit untuk report besar
        ini_set('memory_limit', '512M');
        set_time_limit(300);
```

- [ ] **Step 4: Fix `index()` so the form dropdown matches what `generate()` will actually allow**

In the same file, find:

```php
    public function index()
    {
        $kelasList = Kelas::orderByRaw("
            CASE
                WHEN kelas LIKE 'X-%' THEN 1
                WHEN kelas LIKE 'XI-%' THEN 2
                WHEN kelas LIKE 'XII-%' THEN 3
                ELSE 4
            END,
            CAST(SUBSTRING_INDEX(kelas, '-', -1) AS UNSIGNED)
        ")->pluck('kelas');

        $semesterList = SemesterHelper::list();

        return view('report.index', compact('kelasList', 'semesterList'));
    }
```

Replace with:

```php
    public function index()
    {
        $user = auth()->user();

        $kelasQuery = Kelas::orderByRaw("
            CASE
                WHEN kelas LIKE 'X-%' THEN 1
                WHEN kelas LIKE 'XI-%' THEN 2
                WHEN kelas LIKE 'XII-%' THEN 3
                ELSE 4
            END,
            CAST(SUBSTRING_INDEX(kelas, '-', -1) AS UNSIGNED)
        ");

        if ($user && $user->role === 'Walikelas') {
            $kelasQuery->where('username', $user->username);
        }

        $kelasList = $kelasQuery->pluck('kelas');

        $semesterList = SemesterHelper::list();

        return view('report.index', compact('kelasList', 'semesterList'));
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=ReportAccessControlTest`
Expected: PASS if runnable; otherwise confirm failures are exactly the known migration blocker and hand-trace each test against the diff (in particular, confirm `generatePerKelas()`'s own `if ($selectedLevel)` branch at line ~65 is skipped because `selected_level` was nulled out by the merge, so it falls through to using the already-restricted `kelas_list` at line 61).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ReportController.php tests/Feature/ReportAccessControlTest.php
git commit -m "fix: scope ReportController to a Walikelas's own classes"
```

---

### Task 3: Fix `PelanggaranController` IDOR (`show()` and `getMurid()`)

**Files:**
- Modify: `app/Http/Controllers/PelanggaranController.php:233-236` (`show()`), `:468-492` (`getMurid()`)
- Test: `tests/Feature/PelanggaranAuthorizationTest.php` (create)

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing new — both actions keep their existing signatures and response shapes.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PelanggaranAuthorizationTest.php`:

```php
<?php

use App\Models\JenisPelanggaran;
use App\Models\Kelas;
use App\Models\Murid;
use App\Models\Pelanggaran;
use App\Models\User;

function makePelanggaranRecord(array $overrides = []): Pelanggaran
{
    return Pelanggaran::create(array_merge([
        'NIS' => '9000000001',
        'nama_murid' => 'Test Murid',
        'gender' => 'Laki-laki',
        'kelas' => 'X-1',
        'jenis_pelanggaran' => 'Terlambat',
        'poin' => 5,
        'tanggal' => now()->toDateString(),
        'semester' => '2025/2026-1',
    ], $overrides));
}

test('walikelas cannot view a pelanggaran detail page', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    $pelanggaran = makePelanggaranRecord();

    $response = $this->actingAs($walikelas)->get(route('pelanggaran.show', $pelanggaran));

    $response->assertRedirect(route('pelanggaran.index'));
});

test('admin can view a pelanggaran detail page', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $pelanggaran = makePelanggaranRecord();

    $response = $this->actingAs($admin)->get(route('pelanggaran.show', $pelanggaran));

    $response->assertOk();
});

test('walikelas cannot fetch murid data outside their class via the getMurid API', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    Kelas::create(['kelas' => 'X-1', 'username' => $walikelas->username]);
    Kelas::create(['kelas' => 'X-2']);
    $murid = Murid::create([
        'NIS' => '4444444444',
        'nama_lengkap' => 'Murid X2',
        'gender' => 'Laki-laki',
        'kelas' => 'X-2',
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($walikelas)->getJson(route('api.pelanggaran.murid', $murid->NIS));

    $response->assertJson(['success' => false]);
});

test('walikelas can fetch murid data for their own class via the getMurid API', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    Kelas::create(['kelas' => 'X-1', 'username' => $walikelas->username]);
    $murid = Murid::create([
        'NIS' => '5555555555',
        'nama_lengkap' => 'Murid X1',
        'gender' => 'Perempuan',
        'kelas' => 'X-1',
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($walikelas)->getJson(route('api.pelanggaran.murid', $murid->NIS));

    $response->assertJson(['success' => true]);
});

test('admin can fetch murid data for any student via the getMurid API', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $murid = Murid::create([
        'NIS' => '6666666666',
        'nama_lengkap' => 'Murid Any',
        'gender' => 'Laki-laki',
        'kelas' => null,
        'status' => 'Aktif',
    ]);

    $response = $this->actingAs($admin)->getJson(route('api.pelanggaran.murid', $murid->NIS));

    $response->assertJson(['success' => true]);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PelanggaranAuthorizationTest`
Expected: FAIL — either the known migration blocker, or (if runnable) the `show()`/`getMurid()` scoping tests fail because no check exists yet.

- [ ] **Step 3: Fix `show()`**

In `app/Http/Controllers/PelanggaranController.php`, find:

```php
    /**
     * Display the specified resource.
     */
    public function show(Pelanggaran $pelanggaran)
    {
        return view("pelanggaran.show", compact("pelanggaran"));
    }
```

Replace with:

```php
    /**
     * Display the specified resource.
     */
    public function show(Pelanggaran $pelanggaran)
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk melihat detail data pelanggaran.",
                );
        }

        return view("pelanggaran.show", compact("pelanggaran"));
    }
```

- [ ] **Step 4: Fix `getMurid()`**

In the same file, find:

```php
    public function getMurid(string $nis)
    {
        $murid = Murid::where('NIS', $nis)->first();

        if ($murid) {
            $semesterAktif     = SemesterHelper::current();
```

Replace with:

```php
    public function getMurid(string $nis)
    {
        $murid = Murid::where('NIS', $nis)->first();

        if ($murid) {
            $user = auth()->user();
            if ($user && $user->role === 'Walikelas') {
                $ownKelas = \App\Models\Kelas::where('username', $user->username)->pluck('kelas');
                if (! $murid->kelas || ! $ownKelas->contains($murid->kelas)) {
                    return response()->json(['success' => false]);
                }
            }

            $semesterAktif     = SemesterHelper::current();
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PelanggaranAuthorizationTest`
Expected: PASS if runnable; otherwise confirm the known migration blocker and hand-trace each test.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PelanggaranController.php tests/Feature/PelanggaranAuthorizationTest.php
git commit -m "fix: close IDOR in PelanggaranController show() and getMurid() API"
```

---

### Task 4: Remove unauthenticated `/clear-cache` route

**Files:**
- Modify: `routes/web.php:17-21`
- Test: `tests/Feature/ClearCacheRouteTest.php` (create)

**Interfaces:** None — pure removal.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ClearCacheRouteTest.php`:

```php
<?php

test('clear-cache route no longer exists', function () {
    $response = $this->get('/clear-cache');

    $response->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ClearCacheRouteTest`
Expected: FAIL — the route currently returns 200, not 404 (this specific test does not depend on the database at all, so it should actually run cleanly despite the environment blocker — confirm that).

- [ ] **Step 3: Remove the route**

In `routes/web.php`, find:

```php
Route::get("/", function () {
    return redirect()->route("login");
});

Route::get('/clear-cache', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return 'Configuration, route, and view caches cleared successfully!';
});

Route::get("/dashboard", function () {
```

Replace with:

```php
Route::get("/", function () {
    return redirect()->route("login");
});

Route::get("/dashboard", function () {
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=ClearCacheRouteTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/ClearCacheRouteTest.php
git commit -m "fix: remove unauthenticated /clear-cache route"
```

---

### Task 5: Fix OTP login — rate limiting, weak PRNG, plaintext logging

**Files:**
- Modify: `routes/auth.php:29-30`
- Modify: `app/Http/Controllers/Auth/OtpController.php:19-68` (`store()`), `:73-80` (`sendOtp()`)
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php:38-42`
- Modify: `tests/Feature/OtpLoginTest.php` (existing PHPUnit-class-style file — add methods to the existing class, matching its style; do not switch it to Pest-style `test()` functions)

**Interfaces:**
- Consumes: nothing new.
- Produces: new session key `auth_otp_attempts` (int), set to `0` when an OTP is sent and incremented on each wrong guess; cleared alongside the other `auth_otp_*` keys on success, expiry, or lockout.

- [ ] **Step 1: Write the failing tests**

Open `tests/Feature/OtpLoginTest.php` and add these methods inside the existing `OtpLoginTest` class (after `test_otp_verification_logs_in_user`):

```php
    public function test_otp_verification_fails_with_wrong_code_and_tracks_attempts()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withSession([
            'auth_otp' => '123456',
            'auth_username' => 'testuser',
            'auth_otp_attempts' => 0,
        ])->post('/verify-otp', [
            'otp' => '999999',
        ]);

        $response->assertSessionHas('auth_otp_attempts', 1);
        $this->assertGuest();
    }

    public function test_otp_verification_locks_out_after_five_wrong_attempts()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withSession([
            'auth_otp' => '123456',
            'auth_username' => 'testuser',
            'auth_otp_attempts' => 4,
        ])->post('/verify-otp', [
            'otp' => '999999',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('auth_otp');
        $this->assertGuest();
    }

    public function test_verify_otp_route_is_rate_limited()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withSession([
                'auth_otp' => '123456',
                'auth_username' => 'testuser',
                'auth_otp_attempts' => 0,
            ])->post('/verify-otp', ['otp' => '000000']);
        }

        $response = $this->withSession([
            'auth_otp' => '123456',
            'auth_username' => 'testuser',
            'auth_otp_attempts' => 0,
        ])->post('/verify-otp', ['otp' => '000000']);

        $response->assertStatus(429);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=OtpLoginTest`
Expected: FAIL — either the known migration blocker, or (if runnable) `test_otp_verification_fails_with_wrong_code_and_tracks_attempts` fails because `auth_otp_attempts` is never set, and `test_verify_otp_route_is_rate_limited` fails because there's no throttle middleware (no 429 ever returned).

- [ ] **Step 3: Add throttle middleware to the route**

In `routes/auth.php`, find:

```php
    // The POST route is used by the OTP Modal to submit the verification code.
    Route::post('verify-otp', [\App\Http\Controllers\Auth\OtpController::class, 'store'])
        ->name('verify-otp.store');
```

Replace with:

```php
    // The POST route is used by the OTP Modal to submit the verification code.
    Route::post('verify-otp', [\App\Http\Controllers\Auth\OtpController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('verify-otp.store');
```

- [ ] **Step 4: Add attempt tracking and lockout to `OtpController::store()`**

In `app/Http/Controllers/Auth/OtpController.php`, find:

```php
        if ($request->otp !== $sessionOtp) {
            return back()->with([
                'show_otp' => true,
                'error' => 'Kode OTP yang dimasukkan salah.'
            ])->withInput();
        }
```

Replace with:

```php
        if ($request->otp !== $sessionOtp) {
            $attempts = (int) $request->session()->get('auth_otp_attempts', 0) + 1;
            $request->session()->put('auth_otp_attempts', $attempts);

            if ($attempts >= 5) {
                $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);

                return redirect()->route('login')->with('error', 'Terlalu banyak percobaan kode OTP yang salah. Silakan login kembali.');
            }

            return back()->with([
                'show_otp' => true,
                'error' => 'Kode OTP yang dimasukkan salah.'
            ])->withInput();
        }
```

Also update the two other places in the same method that call `$request->session()->forget([...])` to also clear `auth_otp_attempts`. Find:

```php
        if ($expiresAt && now()->isAfter($expiresAt)) {
            $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at']);
            return redirect()->route('login')->with('error', 'Waktu verifikasi OTP telah habis (5 menit). Silakan login kembali.');
        }
```

Replace with:

```php
        if ($expiresAt && now()->isAfter($expiresAt)) {
            $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);
            return redirect()->route('login')->with('error', 'Waktu verifikasi OTP telah habis (5 menit). Silakan login kembali.');
        }
```

And find:

```php
        if (!$user) {
            $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember']);
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan.');
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        // Clear OTP session variables
        $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at']);
```

Replace with:

```php
        if (!$user) {
            $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_attempts']);
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan.');
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        // Clear OTP session variables
        $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);
```

- [ ] **Step 5: Fix the weak PRNG and plaintext OTP logging in `sendOtp()`**

In the same file, find:

```php
    public static function sendOtp($user)
    {
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $phoneNumber = self::formatPhoneNumber($user->nomor_telepon);

        $message = "Halo {$user->nama_lengkap},\n\nKode OTP Anda untuk login ke NEXUS 21 adalah: *{$otp}*\n\nKode ini bersifat rahasia. Jangan berikan kode ini kepada siapapun.";
        
        Log::info("OTP generated for user {$user->username}: {$otp}"); // For testing
```

Replace with:

```php
    public static function sendOtp($user)
    {
        $otp = sprintf('%06d', random_int(100000, 999999));
        $phoneNumber = self::formatPhoneNumber($user->nomor_telepon);

        $message = "Halo {$user->nama_lengkap},\n\nKode OTP Anda untuk login ke NEXUS 21 adalah: *{$otp}*\n\nKode ini bersifat rahasia. Jangan berikan kode ini kepada siapapun.";

        Log::info("OTP generated for user {$user->username}");
```

- [ ] **Step 6: Reset the attempt counter whenever a fresh OTP is sent**

In `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, find:

```php
        // Simpan data OTP sementara
        $request->session()->put('auth_otp', $otp);
        $request->session()->put('auth_username', $user->username);
        $request->session()->put('auth_remember', $request->boolean('remember'));
        $request->session()->put('auth_otp_expires_at', now()->addMinutes(5));
```

Replace with:

```php
        // Simpan data OTP sementara
        $request->session()->put('auth_otp', $otp);
        $request->session()->put('auth_username', $user->username);
        $request->session()->put('auth_remember', $request->boolean('remember'));
        $request->session()->put('auth_otp_expires_at', now()->addMinutes(5));
        $request->session()->put('auth_otp_attempts', 0);
```

- [ ] **Step 7: Verify the PRNG fix with a standalone PHP probe**

```bash
php -r '
for ($i = 0; $i < 5; $i++) {
    $otp = sprintf("%06d", random_int(100000, 999999));
    echo $otp . " (" . strlen($otp) . " digits)\n";
}
'
```

Expected: 5 lines, each a distinct 6-digit number.

- [ ] **Step 8: Run the tests to verify they pass**

Run: `php artisan test --filter=OtpLoginTest`
Expected: PASS if runnable; otherwise confirm the known migration blocker and hand-trace each new test, paying attention to whether the `throttle:5,1` middleware keys on IP (default Laravel testing IP is consistent across requests within one test, so 5 same-IP requests should trip it on the 6th).

- [ ] **Step 9: Commit**

```bash
git add routes/auth.php app/Http/Controllers/Auth/OtpController.php app/Http/Controllers/Auth/AuthenticatedSessionController.php tests/Feature/OtpLoginTest.php
git commit -m "fix: rate-limit OTP verification, use random_int, stop logging OTP value"
```

---

### Task 6: Scope trusted proxies away from `'*'`

**Files:**
- Modify: `bootstrap/app.php:14`

**Interfaces:** None.

No automated test for this task — trusted-proxy resolution depends on the real TCP peer address (`REMOTE_ADDR`), which Laravel's in-process HTTP testing client cannot realistically spoof to be outside `127.0.0.1`, so a test here would either be vacuous or misleadingly rely on undocumented internals. Verify by code review instead (Step 2).

- [ ] **Step 1: Narrow the trusted proxy list**

In `bootstrap/app.php`, find:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
```

Replace with:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only RFC1918 private-network ranges, not '*'. This app's only
        // internet-facing service is the `nginx` container (docker-compose.yml);
        // `app` has no published host port and is reachable exclusively from
        // other containers on the internal `nexus_network` bridge network, which
        // always falls in these private ranges. Trusting '*' let a client-supplied
        // X-Forwarded-For header defeat the login rate limiter, which keys on
        // $request->ip() (see app/Http/Requests/Auth/LoginRequest.php).
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
```

- [ ] **Step 2: Verify by reasoning through the resolution algorithm**

Confirm in your report: because `app` (PHP-FPM) has no published host port in `docker-compose.yml` and is only reachable via `nginx`'s `fastcgi_pass` over the internal bridge network, the real `REMOTE_ADDR` PHP-FPM ever sees is always an internal Docker IP (private range) belonging to `nginx`. With `trustProxies` now scoped to private ranges only, Symfony's proxy resolution (`Illuminate\Http\Middleware\TrustProxies` → `Symfony\Component\HttpFoundation\Request::getClientIp()`) trusts that immediate peer as a proxy, then walks the `X-Forwarded-For` chain from right to left, stopping at the first entry NOT in the trusted list — which will be nginx's own `$proxy_add_x_forwarded_for`-appended real-client IP, not any earlier attacker-injected value, since nothing outside the private ranges is trusted to extend the walk further.

- [ ] **Step 3: Commit**

```bash
git add bootstrap/app.php
git commit -m "fix: scope trusted proxies to private ranges instead of '*'"
```

---

### Task 7: Docker/infra hardening — stop forcing debug mode, ship without dev deps, fix permissions

**Files:**
- Modify: `docker-compose.yml` (the `app` service's `environment:`/`env_file:`)
- Modify: `Dockerfile:52-57`
- Modify: `docker/entrypoint.sh:7-12`, `:42`
- Modify: `.env.docker.example:1-5`

**Interfaces:** None — pure configuration.

No automated test — this is infrastructure config with no PHP/JS code path to exercise. Verify via `docker compose config` if Docker is available in your environment (validates YAML + interpolation); otherwise verify by careful manual review of the full resulting file (Step 5), and note in your report whether `docker compose config` was actually run or only reviewed by eye.

- [ ] **Step 1: Stop hardcoding debug mode in `docker-compose.yml`**

In `docker-compose.yml`, find:

```yaml
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: nexus_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - vendor_data:/var/www/vendor
      - node_modules_data:/var/www/node_modules
      - public_build_data:/var/www/public/build
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    depends_on:
      db:
        condition: service_healthy
    networks:
      - nexus_network
```

Replace with:

```yaml
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: nexus_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - vendor_data:/var/www/vendor
      - node_modules_data:/var/www/node_modules
      - public_build_data:/var/www/public/build
    env_file:
      - .env.docker
    depends_on:
      db:
        condition: service_healthy
    networks:
      - nexus_network
```

- [ ] **Step 2: Ship the production image without dev dependencies**

In `Dockerfile`, find:

```dockerfile
# ── Install PHP dependencies ──────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader
```

Replace with:

```dockerfile
# ── Install PHP dependencies ──────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader
```

- [ ] **Step 3: Match the same flag in `entrypoint.sh`'s fallback install, and stop world-writable permissions**

In `docker/entrypoint.sh`, find:

```sh
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "📦 vendor/ not found, running composer install..."
    composer install \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
        --optimize-autoloader
    echo "✅ Composer install done."
fi
```

Replace with:

```sh
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "📦 vendor/ not found, running composer install..."
    composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
        --optimize-autoloader
    echo "✅ Composer install done."
fi
```

Then, further down in the same file, find:

```sh
# ── Fix permissions storage ───────────────────────────────────────────────────
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
```

Replace with:

```sh
# ── Fix permissions storage ───────────────────────────────────────────────────
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

- [ ] **Step 4: Make the shipped `.env.docker.example` default to production-safe values**

In `.env.docker.example`, find:

```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
```

Replace with:

```
# Nilai default di bawah ini aman untuk production. Untuk development lokal
# dengan debug mode aktif, override APP_ENV/APP_DEBUG di .env.docker milikmu
# sendiri (file itu di-gitignore — jangan commit APP_DEBUG=true).
APP_NAME=Laravel
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8000
```

- [ ] **Step 5: Manually review the full resulting files**

Read the complete, current `docker-compose.yml`, `Dockerfile`, and `docker/entrypoint.sh` after your edits (not just the diffs) to confirm: the `app` service still has all its original volumes/`depends_on`/`networks`; `entrypoint.sh`'s migrate/cache/storage-link sequence is otherwise untouched; and `.env.docker.example` still has every other variable it had before (only the 2 lines changed). If Docker is available in your environment, run `docker compose config` from the repo root and confirm it parses without error.

- [ ] **Step 6: Commit**

```bash
git add docker-compose.yml Dockerfile docker/entrypoint.sh .env.docker.example
git commit -m "fix: stop forcing debug mode in docker-compose, ship without dev deps, fix storage perms"
```

---

### Task 8: Stop publishing DB/phpMyAdmin to the host; randomize the seeded admin password

**Files:**
- Modify: `docker-compose.yml` (the `db` and `phpmyadmin` services' `ports:`)
- Modify: `DOCKER.md`

**Interfaces:** None.

No automated test — infra config + documentation. Verify per Task 7's Step 5 pattern.

- [ ] **Step 1: Stop publishing the database port**

In `docker-compose.yml`, find:

```yaml
  db:
    image: mysql:8.0
    container_name: nexus_db
    restart: unless-stopped
    env_file:
      - .env.docker
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
```

Replace with:

```yaml
  db:
    image: mysql:8.0
    container_name: nexus_db
    restart: unless-stopped
    env_file:
      - .env.docker
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
```

- [ ] **Step 2: Stop publishing phpMyAdmin**

In the same file, find:

```yaml
  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    container_name: nexus_phpmyadmin
    restart: unless-stopped
    ports:
      - "8080:80"
    env_file:
      - .env.docker
```

Replace with:

```yaml
  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    container_name: nexus_phpmyadmin
    restart: unless-stopped
    env_file:
      - .env.docker
```

- [ ] **Step 3: Update `DOCKER.md`'s admin-creation step to generate a random password**

In `DOCKER.md`, find:

```markdown
### 3. Buat user Admin pertama

```bash
docker compose exec app php artisan tinker --execute="
\App\Models\User::create([
    'username'      => 'admin',
    'nama_lengkap'  => 'Administrator',
    'nomor_telepon' => '08123456789',
    'password'      => bcrypt('password'),
    'role'          => 'Admin'
]);
"
```

### 4. Buka di browser

```
http://localhost:8000
```

Login: **username** `admin` / **password** `password`
```

Replace with:

```markdown
### 3. Buat user Admin pertama

```bash
docker compose exec app php artisan tinker --execute="
\$password = \Illuminate\Support\Str::random(16);
\App\Models\User::create([
    'username'      => 'admin',
    'nama_lengkap'  => 'Administrator',
    'nomor_telepon' => '08123456789',
    'password'      => bcrypt(\$password),
    'role'          => 'Admin'
]);
echo 'Admin password: ' . \$password . PHP_EOL;
"
```

> ⚠️ **Catat password yang ditampilkan** — ini satu-satunya kesempatan untuk melihatnya. Segera login dan ganti password ini lewat halaman profil.

### 4. Buka di browser

```
http://localhost:8000
```

Login dengan **username** `admin` dan password yang dicatat di langkah 3.
```

- [ ] **Step 4: Update the Services table and phpMyAdmin note**

In `DOCKER.md`, find:

```markdown
## 📡 Services

| Service         | URL                       |
| --------------- | ------------------------- |
| Aplikasi        | http://localhost:8000     |
| phpMyAdmin      | http://localhost:8080     |
| WhatsApp server | http://localhost:3000     |
| MySQL           | localhost:3306            |

> **phpMyAdmin login:** Username `root`, Password sesuai `DB_PASSWORD` di `.env.docker`
```

Replace with:

```markdown
## 📡 Services

| Service         | URL                       |
| --------------- | ------------------------- |
| Aplikasi        | http://localhost:8000     |

> **phpMyAdmin, MySQL, dan WhatsApp server tidak lagi dipublikasikan ke host** untuk keamanan — semuanya hanya bisa diakses lewat jaringan Docker internal (`nexus_network`). Untuk mengakses database secara manual saat development:
> - `docker compose exec db mysql -u root -p` (langsung dari terminal, tanpa expose port apapun)
> - Kalau benar-benar butuh phpMyAdmin sementara: `docker compose port phpmyadmin 80` untuk cek port internal, lalu buat SSH tunnel ke situ — jangan publish permanen ke internet.
```

- [ ] **Step 5: Manually review the full resulting files**

Read the complete `docker-compose.yml` and `DOCKER.md` after editing to confirm nothing else in either file was accidentally altered.

- [ ] **Step 6: Commit**

```bash
git add docker-compose.yml DOCKER.md
git commit -m "fix: stop publishing db/phpMyAdmin to host, randomize seeded admin password"
```

---

### Task 9: Require a shared secret on the WhatsApp gateway; stop publishing it to the host

**Files:**
- Modify: `whatsapp-server.js:1-11, 67-101`
- Modify: `app/Http/Controllers/PelanggaranController.php:598-603`
- Modify: `app/Http/Controllers/Auth/OtpController.php:87-92`
- Modify: `docker-compose.yml` (the `whatsapp` service)
- Modify: `.env.docker.example`
- Test: `tests/Feature/WhatsappSharedSecretTest.php` (create)

**Interfaces:**
- Consumes: nothing new.
- Produces: new env var `WHATSAPP_SHARED_SECRET`, read by `whatsapp-server.js` via `process.env.WHATSAPP_SHARED_SECRET` and by both PHP callers via `env('WHATSAPP_SHARED_SECRET', '')`, sent as `Authorization: Bearer <value>`.

- [ ] **Step 1: Write the failing PHP tests**

Create `tests/Feature/WhatsappSharedSecretTest.php`:

```php
<?php

use App\Models\JenisPelanggaran;
use App\Models\Kelas;
use App\Models\Murid;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('notifyWalikelas sends an Authorization bearer header to the whatsapp gateway', function () {
    Http::fake();

    $walikelas = User::factory()->create(['role' => 'Walikelas', 'nomor_telepon' => '08123456789']);
    Kelas::create(['kelas' => 'X-1', 'username' => $walikelas->username]);
    $murid = Murid::create([
        'NIS' => '7777777777',
        'nama_lengkap' => 'Murid Notif',
        'gender' => 'Laki-laki',
        'kelas' => 'X-1',
        'status' => 'Aktif',
    ]);
    $jenis = JenisPelanggaran::create(['nama' => 'Terlambat', 'poin' => 5]);
    $staff = User::factory()->create(['role' => 'TATIB']);

    $this->actingAs($staff)->post(route('pelanggaran.store'), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/send-message')
            && $request->hasHeader('Authorization');
    });
});

test('sendOtp sends an Authorization bearer header to the whatsapp gateway', function () {
    Http::fake();

    $user = User::factory()->create(['nomor_telepon' => '08123456789']);

    \App\Http\Controllers\Auth\OtpController::sendOtp($user);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/send-message')
            && $request->hasHeader('Authorization');
    });
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=WhatsappSharedSecretTest`
Expected: FAIL — either the known migration blocker, or (if runnable) both tests fail because no `Authorization` header is currently sent.

- [ ] **Step 3: Add shared-secret middleware to `whatsapp-server.js`**

In `whatsapp-server.js`, find:

```js

import { makeWASocket, DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@whiskeysockets/baileys';
import express from 'express';
import bodyParser from 'body-parser';
import pino from 'pino';
import qrcode from 'qrcode-terminal';

const app = express();
const PORT = 3000;

app.use(bodyParser.json());
```

Replace with:

```js

import { makeWASocket, DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@whiskeysockets/baileys';
import express from 'express';
import bodyParser from 'body-parser';
import pino from 'pino';
import qrcode from 'qrcode-terminal';
import crypto from 'crypto';

const app = express();
const PORT = 3000;
const SHARED_SECRET = process.env.WHATSAPP_SHARED_SECRET || '';

app.use(bodyParser.json());

function timingSafeEqualStrings(a, b) {
    const bufA = Buffer.from(a);
    const bufB = Buffer.from(b);
    if (bufA.length !== bufB.length) {
        return false;
    }
    return crypto.timingSafeEqual(bufA, bufB);
}

function requireSharedSecret(req, res, next) {
    if (!SHARED_SECRET) {
        console.warn('WHATSAPP_SHARED_SECRET is not set — refusing all requests. Set it in the environment to enable this endpoint.');
        return res.status(503).json({ status: false, error: 'Service not configured' });
    }

    const authHeader = req.headers['authorization'] || '';
    const expected = `Bearer ${SHARED_SECRET}`;

    if (!timingSafeEqualStrings(authHeader, expected)) {
        return res.status(401).json({ status: false, error: 'Unauthorized' });
    }

    next();
}
```

Then find:

```js
app.post('/send-message', async (req, res) => {
```

Replace with:

```js
app.post('/send-message', requireSharedSecret, async (req, res) => {
```

- [ ] **Step 4: Send the shared secret from `PelanggaranController::notifyWalikelas()`**

In `app/Http/Controllers/PelanggaranController.php`, find:

```php
            $whatsappHost = env('WHATSAPP_HOST', 'http://whatsapp:3000');
            
            \Illuminate\Support\Facades\Http::timeout(5)->post("{$whatsappHost}/send-message", [
                'id' => $phoneNumber,
                'message' => $message,
            ]);
```

Replace with:

```php
            $whatsappHost = env('WHATSAPP_HOST', 'http://whatsapp:3000');

            \Illuminate\Support\Facades\Http::timeout(5)
                ->withToken(env('WHATSAPP_SHARED_SECRET', ''))
                ->post("{$whatsappHost}/send-message", [
                    'id' => $phoneNumber,
                    'message' => $message,
                ]);
```

- [ ] **Step 5: Send the shared secret from `OtpController::sendOtp()`**

In `app/Http/Controllers/Auth/OtpController.php`, find:

```php
            $whatsappHost = env('WHATSAPP_HOST', 'http://whatsapp:3000');
            
            $response = Http::post("{$whatsappHost}/send-message", [
                'id' => $phoneNumber,
                'message' => $message,
            ]);
```

Replace with:

```php
            $whatsappHost = env('WHATSAPP_HOST', 'http://whatsapp:3000');

            $response = Http::withToken(env('WHATSAPP_SHARED_SECRET', ''))
                ->post("{$whatsappHost}/send-message", [
                    'id' => $phoneNumber,
                    'message' => $message,
                ]);
```

- [ ] **Step 6: Stop publishing the WhatsApp gateway to the host and wire the env var through**

In `docker-compose.yml`, find:

```yaml
  whatsapp:
    image: node:20-alpine
    container_name: nexus_whatsapp
    restart: unless-stopped
    working_dir: /app
    volumes:
      - .:/app
    command: sh -c "npm install --omit=dev && node whatsapp-server.js"
    ports:
      - "3000:3000"
    networks:
      - nexus_network
```

Replace with:

```yaml
  whatsapp:
    image: node:20-alpine
    container_name: nexus_whatsapp
    restart: unless-stopped
    working_dir: /app
    volumes:
      - .:/app
    command: sh -c "npm install --omit=dev && node whatsapp-server.js"
    env_file:
      - .env.docker
    networks:
      - nexus_network
```

- [ ] **Step 7: Add the new env var to `.env.docker.example`**

In `.env.docker.example`, find:

```
# Gunakan nama service Docker Compose untuk WhatsApp notifier
PELANGGARAN_NOTIFICATION_ENDPOINT=http://whatsapp:3000/send-message
```

Replace with:

```
# Gunakan nama service Docker Compose untuk WhatsApp notifier
PELANGGARAN_NOTIFICATION_ENDPOINT=http://whatsapp:3000/send-message

# Dikirim sebagai header "Authorization: Bearer <value>" ke whatsapp-server.js.
# WAJIB diisi string acak yang panjang sebelum deploy — tanpa ini endpoint
# /send-message menolak semua request (lihat whatsapp-server.js).
WHATSAPP_SHARED_SECRET=
```

- [ ] **Step 8: Verify the Node-side auth logic with a standalone script**

Create a throwaway file (do not commit it) to verify the middleware logic in isolation, e.g. `/tmp/probe-whatsapp-auth.mjs`:

```js
import crypto from 'crypto';

function timingSafeEqualStrings(a, b) {
    const bufA = Buffer.from(a);
    const bufB = Buffer.from(b);
    if (bufA.length !== bufB.length) {
        return false;
    }
    return crypto.timingSafeEqual(bufA, bufB);
}

const SHARED_SECRET = 'test-secret-value';

console.log('correct token:', timingSafeEqualStrings(`Bearer ${SHARED_SECRET}`, `Bearer ${SHARED_SECRET}`)); // expect true
console.log('wrong token:', timingSafeEqualStrings('Bearer wrong-value', `Bearer ${SHARED_SECRET}`)); // expect false
console.log('missing token:', timingSafeEqualStrings('', `Bearer ${SHARED_SECRET}`)); // expect false
console.log('different length:', timingSafeEqualStrings('Bearer short', `Bearer ${SHARED_SECRET}`)); // expect false
```

Run: `node /tmp/probe-whatsapp-auth.mjs`
Expected: `true`, `false`, `false`, `false`. Delete the probe file afterward.

- [ ] **Step 9: Run the PHP tests to verify they pass**

Run: `php artisan test --filter=WhatsappSharedSecretTest`
Expected: PASS if runnable; otherwise confirm the known migration blocker and hand-trace against the diff, plus cite the Step 8 probe output as evidence for the Node-side logic (which the PHP test suite can't exercise directly).

- [ ] **Step 10: Commit**

```bash
git add whatsapp-server.js app/Http/Controllers/PelanggaranController.php app/Http/Controllers/Auth/OtpController.php docker-compose.yml .env.docker.example tests/Feature/WhatsappSharedSecretTest.php
git commit -m "fix: require a shared secret on the WhatsApp gateway, stop publishing it to the host"
```

---

### Task 10: Narrow the exception type that leaks raw DB errors in `previewPerubahanKelas`

**Files:**
- Create: `app/Exceptions/PerubahanKelasFileException.php`
- Modify: `app/Http/Controllers/NaikKelasController.php:292` (the `throw` inside `parsePerubahanKelasFile`), `:453-455` (the `catch` block inside `previewPerubahanKelas`)
- Test: `tests/Feature/PerubahanKelasTest.php` (append)

**Interfaces:**
- Consumes: nothing new.
- Produces: `App\Exceptions\PerubahanKelasFileException` (extends `\RuntimeException`), thrown by `parsePerubahanKelasFile()` for a malformed header, caught specifically (not via the broader `\RuntimeException`) by `previewPerubahanKelas()`.

This closes the sub-threshold finding from `docs/superpowers/specs/2026-07-26-production-security-audit.md` Part 1: `Illuminate\Database\QueryException extends \PDOException extends \RuntimeException`, so the current `$e instanceof \RuntimeException` check in `previewPerubahanKelas()`'s catch block would also match a genuine DB query fault during `validatePerubahanKelasRows()` (which runs inside the same try block), flashing raw SQL text, bindings, and the connection name to the browser instead of the intended generic message.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PerubahanKelasTest.php`:

```php
test('QueryException is not an instance of PerubahanKelasFileException', function () {
    $queryException = new \Illuminate\Database\QueryException(
        'mysql',
        'select 1',
        [],
        new \PDOException('simulated db fault')
    );

    expect($queryException instanceof \App\Exceptions\PerubahanKelasFileException)->toBeFalse();
});

test('PerubahanKelasFileException is a RuntimeException usable for the header-validation error', function () {
    $fileException = new \App\Exceptions\PerubahanKelasFileException('Format file tidak sesuai.');

    expect($fileException instanceof \App\Exceptions\PerubahanKelasFileException)->toBeTrue();
    expect($fileException instanceof \RuntimeException)->toBeTrue();
    expect($fileException->getMessage())->toBe('Format file tidak sesuai.');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — `App\Exceptions\PerubahanKelasFileException` does not exist yet (this specific failure is a class-not-found error, not the `RefreshDatabase` migration blocker, since these two new tests touch no database — confirm the failure reason is exactly "class not found").

- [ ] **Step 3: Create the exception class**

Create `app/Exceptions/PerubahanKelasFileException.php`:

```php
<?php

namespace App\Exceptions;

/**
 * Thrown by NaikKelasController::parsePerubahanKelasFile() for a malformed or
 * missing header row. Kept distinct from a bare \RuntimeException so
 * previewPerubahanKelas()'s catch block can safely show this exception's
 * message to the user without also matching \Illuminate\Database\QueryException
 * (which extends \PDOException, which extends \RuntimeException).
 */
class PerubahanKelasFileException extends \RuntimeException
{
}
```

- [ ] **Step 4: Throw the new exception type instead of a bare `\RuntimeException`**

In `app/Http/Controllers/NaikKelasController.php`, find:

```php
                            throw new \RuntimeException("Format file tidak sesuai. Pastikan file memiliki header 'NIS' dan 'Kelas Baru' pada baris pertama.");
```

Replace with:

```php
                            throw new \App\Exceptions\PerubahanKelasFileException("Format file tidak sesuai. Pastikan file memiliki header 'NIS' dan 'Kelas Baru' pada baris pertama.");
```

- [ ] **Step 5: Narrow the catch block's `instanceof` check**

In the same file, find:

```php
            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Gagal membaca file. Pastikan file berformat Excel (.xlsx) yang valid.';
```

Replace with:

```php
            $message = $e instanceof \App\Exceptions\PerubahanKelasFileException
                ? $e->getMessage()
                : 'Gagal membaca file. Pastikan file berformat Excel (.xlsx) yang valid.';
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: The 2 new tests PASS regardless of the known migration blocker (they touch no database). Any other failures in this file should be exactly the pre-existing `RefreshDatabase` blocker — confirm nothing new broke.

- [ ] **Step 7: Commit**

```bash
git add app/Exceptions/PerubahanKelasFileException.php app/Http/Controllers/NaikKelasController.php tests/Feature/PerubahanKelasTest.php
git commit -m "fix: stop leaking raw QueryException details through previewPerubahanKelas's error branch"
```

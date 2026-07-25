# Perubahan Kelas via Upload Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an Admin bulk-move active students to different classes by downloading a template, filling in NIS + target kelas, uploading it, reviewing a preview, and confirming — independent of the existing auto-promote "Naik Kelas" flow.

**Architecture:** New actions on the existing `NaikKelasController`, reusing the OpenSpout read/write patterns already used by `MuridController` for import/template. Validation runs entirely server-side in one pass over all rows (all-or-nothing); once a file validates clean, the computed changes are cached in the Laravel session so a second POST (confirm) can commit them inside a `DB::transaction()` without re-uploading the file. A new "Form Perubahan Kelas" card is added to the existing `/naik-kelas` Blade view, functionally independent of the Naik Kelas card above it.

**Tech Stack:** Laravel 12, PHP 8.2, OpenSpout ^4.32 (xlsx read/write — already a dependency), Pest 4 with `RefreshDatabase` and in-memory sqlite for tests, Alpine.js + Blade `x-modal` component for the confirm dialog (existing pattern in this view).

## Global Constraints

- Upload format is `.xlsx` only — no CSV/TXT support (spec: Template Format).
- Template/upload columns are exactly `NIS`, `Kelas Baru` — no `Kelas Lama` column (spec: Kolom Template).
- `Kelas Baru` must already exist in the `kelas` table — never auto-created, never accepts `Lulus` (spec: Validasi Kelas Baru).
- Only students with `status = 'Aktif'` can be targeted (spec: Non-Goals).
- Max 40 active students per class, checked against the **projected end state** after all valid rows in the file are applied, not the current DB count in isolation (spec: Validation Rules #5).
- A row where `Kelas Baru` equals the student's current kelas is valid but a no-op — excluded from the commit and from capacity math (spec: Validation Rules #4).
- All-or-nothing: if any row is invalid, **nothing** is written to the DB or session (spec: Error Handling Summary).
- Two-step flow: `preview` (validate + stash in session) then `proses` (re-validate from session + commit) — never a single-request commit (spec: Preview & Confirm Flow).
- The feature must work with zero dependency on the Naik Kelas auto-promote form having ever been run — no shared state, no gating (spec: Context, Placement).
- Access restricted to `role === 'Admin'` only, via the existing `checkAdminAccess()` pattern already on every `NaikKelasController` action.
- Only the `murid.kelas` column is ever modified by this feature.
- No new Composer dependencies. No new Eloquent factories — this codebase only has `UserFactory`; `Murid`/`Kelas` test fixtures are built with direct `::create()` calls, matching existing convention.

---

### Task 1: Download template endpoint

**Files:**
- Modify: `app/Http/Controllers/NaikKelasController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PerubahanKelasTest.php` (create)

**Interfaces:**
- Produces: route `naik-kelas.perubahan-kelas.template` (GET) → `NaikKelasController::downloadTemplatePerubahanKelas()`. Test helper `makeAdmin(): \App\Models\User` (role `Admin`), reused by every later task in this plan.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PerubahanKelasTest.php`:

```php
<?php

use App\Models\User;

function makeAdmin(): User
{
    return User::factory()->create(['role' => 'Admin']);
}

test('admin can download perubahan kelas template', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->get(route('naik-kelas.perubahan-kelas.template'));

    $response->assertOk();
    $response->assertDownload('template_perubahan_kelas.xlsx');
});

test('non-admin cannot download perubahan kelas template', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);

    $response = $this->actingAs($walikelas)->get(route('naik-kelas.perubahan-kelas.template'));

    $response->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — route `naik-kelas.perubahan-kelas.template` not defined.

- [ ] **Step 3: Add the route**

In `routes/web.php`, find:

```php
    // Naik Kelas
    Route::get('/naik-kelas', [NaikKelasController::class, 'index'])->name('naik-kelas.index');
    Route::post('/naik-kelas/process', [NaikKelasController::class, 'processNaikKelas'])->name('naik-kelas.process');

});
```

Replace with:

```php
    // Naik Kelas
    Route::get('/naik-kelas', [NaikKelasController::class, 'index'])->name('naik-kelas.index');
    Route::post('/naik-kelas/process', [NaikKelasController::class, 'processNaikKelas'])->name('naik-kelas.process');

    // Perubahan Kelas (upload massal, independen dari Naik Kelas)
    Route::get('/naik-kelas/perubahan-kelas/template', [NaikKelasController::class, 'downloadTemplatePerubahanKelas'])->name('naik-kelas.perubahan-kelas.template');

});
```

- [ ] **Step 4: Add the controller method**

In `app/Http/Controllers/NaikKelasController.php`, change the top imports from:

```php
use App\Models\Kelas;
use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
```

to:

```php
use App\Models\Kelas;
use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
```

Then add this method at the end of the class, just before the final closing `}`:

```php
    /**
     * Download template Excel untuk perubahan kelas (NIS + Kelas Baru).
     */
    public function downloadTemplatePerubahanKelas()
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        $filename = 'template_perubahan_kelas.xlsx';
        $tempPath = storage_path('app/temp/'.$filename);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        try {
            $options = new Options;
            $options->setTempFolder(storage_path('app/temp'));
            $writer = new Writer($options);
            $writer->openToFile($tempPath);

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11);

            $writer->addRow(Row::fromValues(['NIS', 'Kelas Baru'], $headerStyle));
            $writer->addRow(Row::fromValues(['1234567890', 'XI-2']));
            $writer->addRow(Row::fromValues(['1234567891', 'XI-3']));

            $writer->close();

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return redirect()->route('naik-kelas.index')
                ->with('error', 'Gagal download template: '.$e->getMessage());
        }
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/NaikKelasController.php routes/web.php tests/Feature/PerubahanKelasTest.php
git commit -m "feat: add perubahan kelas template download endpoint"
```

---

### Task 2: Preview endpoint — parsing & core row validation

**Files:**
- Modify: `app/Http/Controllers/NaikKelasController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/PerubahanKelasTest.php`

**Interfaces:**
- Consumes: `makeAdmin()` from Task 1.
- Produces: route `naik-kelas.perubahan-kelas.preview` (POST) → `NaikKelasController::previewPerubahanKelas(Request $request)`. Private `parsePerubahanKelasFile(string $path): array` returning a list of `['row_number' => int, 'nis' => string, 'kelas_baru' => string]` (header row skipped, fully-empty rows skipped). Private `validatePerubahanKelasRows(array $rows): array` returning `['errors' => string[], 'changes' => array<array{NIS: string, nama_lengkap: string, kelas_lama: string, kelas_baru: string}>, 'skipped_noop' => int]` (capacity check NOT yet included — added in Task 3, but the method computes and keeps a local `$netChange` array during its row loop for Task 3 to reuse). Session key `perubahan_kelas_preview` (array of changes, same shape as `changes` above). Flash keys: `perubahan_kelas_errors` (string[], max 10), `perubahan_kelas_errors_remaining` (int), `perubahan_kelas_preview_ready` (bool), `perubahan_kelas_skipped_noop` (int), `error_perubahan_kelas` (string). Test helpers `makeKelas(string $kode): \App\Models\Kelas`, `makeMurid(string $nis, ?string $kelas, string $status = 'Aktif', string $nama = 'Test Murid'): \App\Models\Murid`, `makeXlsxUploadFile(array $rows, array $header = ['NIS', 'Kelas Baru']): \Illuminate\Http\UploadedFile`.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PerubahanKelasTest.php` (after the existing tests, still inside the same file — add these helper functions near the top, right after `makeAdmin()`):

```php
function makeKelas(string $kode): \App\Models\Kelas
{
    return \App\Models\Kelas::create(['kelas' => $kode]);
}

function makeMurid(string $nis, ?string $kelas, string $status = 'Aktif', string $nama = 'Test Murid'): \App\Models\Murid
{
    return \App\Models\Murid::create([
        'NIS' => $nis,
        'nama_lengkap' => $nama,
        'gender' => 'Laki-laki',
        'kelas' => $kelas,
        'status' => $status,
    ]);
}

function makeXlsxUploadFile(array $rows, array $header = ['NIS', 'Kelas Baru']): \Illuminate\Http\UploadedFile
{
    $path = sys_get_temp_dir().'/'.uniqid('perubahan_kelas_test_').'.xlsx';

    $writer = new \OpenSpout\Writer\XLSX\Writer;
    $writer->openToFile($path);
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($header));
    foreach ($rows as $row) {
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($row));
    }
    $writer->close();

    return new \Illuminate\Http\UploadedFile(
        $path,
        'perubahan_kelas.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}
```

Then append these tests at the end of the file:

```php
test('preview stores only real changes in session and reports noop count', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');
    makeMurid('1111111111', 'XI-1', 'Aktif', 'Budi');
    makeMurid('2222222222', 'XI-1', 'Aktif', 'Sari');

    $file = makeXlsxUploadFile([
        ['1111111111', 'XI-2'],
        ['2222222222', 'XI-1'],
    ]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    $response->assertRedirect(route('naik-kelas.index'));
    $response->assertSessionHas('perubahan_kelas_preview_ready', true);
    $response->assertSessionHas('perubahan_kelas_skipped_noop', 1);

    $preview = session('perubahan_kelas_preview');
    expect($preview)->toHaveCount(1);
    expect($preview[0])->toMatchArray([
        'NIS' => '1111111111',
        'nama_lengkap' => 'Budi',
        'kelas_lama' => 'XI-1',
        'kelas_baru' => 'XI-2',
    ]);
});

test('preview shows error and stores nothing when NIS is not found or inactive', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');
    makeMurid('1111111111', 'XI-1');
    makeMurid('3333333333', null, 'Lulus', 'Sudah Lulus');

    $file = makeXlsxUploadFile([
        ['9999999999', 'XI-2'],
        ['3333333333', 'XI-2'],
    ]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    $errors = session('perubahan_kelas_errors');
    expect($errors)->toContain('Baris 2: NIS 9999999999 tidak ditemukan atau bukan murid aktif');
    expect($errors)->toContain('Baris 3: NIS 3333333333 tidak ditemukan atau bukan murid aktif');
    $this->assertNull(session('perubahan_kelas_preview'));
});

test('preview shows error when NIS is duplicated in the file', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');
    makeKelas('XI-3');
    makeMurid('1111111111', 'XI-1');

    $file = makeXlsxUploadFile([
        ['1111111111', 'XI-2'],
        ['1111111111', 'XI-3'],
    ]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    expect(session('perubahan_kelas_errors'))
        ->toContain('Baris 2: NIS 1111111111 muncul lebih dari sekali dalam file');
    $this->assertNull(session('perubahan_kelas_preview'));
});

test('preview shows error when target kelas does not exist', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeMurid('1111111111', 'XI-1');

    $file = makeXlsxUploadFile([['1111111111', 'XI-9']]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    expect(session('perubahan_kelas_errors'))
        ->toContain("Baris 2: Kelas 'XI-9' tidak ditemukan");
});

test('preview shows info message and stores nothing when all rows are noop', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeMurid('1111111111', 'XI-1');

    $file = makeXlsxUploadFile([['1111111111', 'XI-1']]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    $response->assertSessionHas('error_perubahan_kelas');
    $this->assertNull(session('perubahan_kelas_preview'));
});

test('preview shows error when file has no data rows', function () {
    $admin = makeAdmin();

    $file = makeXlsxUploadFile([]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    $response->assertSessionHas(
        'error_perubahan_kelas',
        'File tidak berisi data. Pastikan file memiliki baris data selain header.'
    );
});

test('non-admin cannot preview perubahan kelas', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);
    $file = makeXlsxUploadFile([['1111111111', 'XI-2']]);

    $response = $this->actingAs($walikelas)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    $response->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — route `naik-kelas.perubahan-kelas.preview` not defined.

- [ ] **Step 3: Add the route**

In `routes/web.php`, find the line added in Task 1:

```php
    Route::get('/naik-kelas/perubahan-kelas/template', [NaikKelasController::class, 'downloadTemplatePerubahanKelas'])->name('naik-kelas.perubahan-kelas.template');
```

Add immediately after it:

```php
    Route::post('/naik-kelas/perubahan-kelas/preview', [NaikKelasController::class, 'previewPerubahanKelas'])->name('naik-kelas.perubahan-kelas.preview');
```

- [ ] **Step 4: Add the `Reader` import**

In `app/Http/Controllers/NaikKelasController.php`, add `use OpenSpout\Reader\XLSX\Reader;` to the import block so it reads:

```php
use App\Models\Kelas;
use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
```

- [ ] **Step 5: Implement parsing, validation, and the preview action**

Add these methods at the end of the class (after `downloadTemplatePerubahanKelas`, before the final closing `}`):

```php
    /**
     * Parse file Excel perubahan kelas menjadi baris [row_number, nis, kelas_baru].
     * Baris header (baris pertama) dan baris yang sepenuhnya kosong dilewati.
     */
    private function parsePerubahanKelasFile(string $path): array
    {
        $rows = [];
        $reader = new Reader;
        $reader->open($path);

        $rowIndex = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                if ($rowIndex === 1) {
                    continue;
                }

                $cells = $row->getCells();
                $nis = trim((string) ($cells[0]->getValue() ?? ''));
                $kelasBaru = trim((string) ($cells[1]->getValue() ?? ''));

                if ($nis === '' && $kelasBaru === '') {
                    continue;
                }

                $rows[] = [
                    'row_number' => $rowIndex,
                    'nis' => $nis,
                    'kelas_baru' => $kelasBaru,
                ];
            }
            break;
        }

        $reader->close();

        return $rows;
    }

    /**
     * Validasi semua baris perubahan kelas. Tidak ada perubahan yang dianggap
     * "siap disimpan" kalau ada satu saja baris error (all-or-nothing di level caller).
     */
    private function validatePerubahanKelasRows(array $rows): array
    {
        $errors = [];
        $changes = [];
        $skippedNoop = 0;

        $nisCounts = [];
        foreach ($rows as $row) {
            $nisCounts[$row['nis']] = ($nisCounts[$row['nis']] ?? 0) + 1;
        }

        $activeMurids = Murid::where('status', 'Aktif')->get()->keyBy('NIS');
        $validKelas = Kelas::pluck('kelas')->flip();

        $netChange = [];

        foreach ($rows as $row) {
            $rowNumber = $row['row_number'];
            $nis = $row['nis'];
            $kelasBaru = $row['kelas_baru'];

            if ($nis === '' || $kelasBaru === '') {
                $errors[] = "Baris {$rowNumber}: NIS dan Kelas Baru wajib diisi";

                continue;
            }

            if ($nisCounts[$nis] > 1) {
                $errors[] = "Baris {$rowNumber}: NIS {$nis} muncul lebih dari sekali dalam file";

                continue;
            }

            $murid = $activeMurids->get($nis);
            if (! $murid) {
                $errors[] = "Baris {$rowNumber}: NIS {$nis} tidak ditemukan atau bukan murid aktif";

                continue;
            }

            if (! $validKelas->has($kelasBaru)) {
                $errors[] = "Baris {$rowNumber}: Kelas '{$kelasBaru}' tidak ditemukan";

                continue;
            }

            $kelasLama = $murid->kelas;
            if ($kelasLama === $kelasBaru) {
                $skippedNoop++;

                continue;
            }

            $netChange[$kelasLama] = ($netChange[$kelasLama] ?? 0) - 1;
            $netChange[$kelasBaru] = ($netChange[$kelasBaru] ?? 0) + 1;

            $changes[] = [
                'NIS' => $nis,
                'nama_lengkap' => $murid->nama_lengkap,
                'kelas_lama' => $kelasLama,
                'kelas_baru' => $kelasBaru,
            ];
        }

        return [
            'errors' => $errors,
            'changes' => $changes,
            'skipped_noop' => $skippedNoop,
        ];
    }

    /**
     * Preview perubahan kelas dari file yang diupload. Kalau semua baris valid,
     * simpan hasilnya ke session untuk tahap konfirmasi (prosesPerubahanKelas).
     */
    public function previewPerubahanKelas(Request $request)
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'file.required' => 'File harus diupload.',
            'file.file' => 'File tidak valid.',
            'file.mimes' => 'File harus berformat Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        session()->forget('perubahan_kelas_preview');

        $rows = $this->parsePerubahanKelasFile($request->file('file')->getRealPath());

        if (empty($rows)) {
            return redirect()->route('naik-kelas.index')
                ->with('error_perubahan_kelas', 'File tidak berisi data. Pastikan file memiliki baris data selain header.');
        }

        $result = $this->validatePerubahanKelasRows($rows);

        if (! empty($result['errors'])) {
            $displayedErrors = array_slice($result['errors'], 0, 10);

            return redirect()->route('naik-kelas.index')
                ->with('perubahan_kelas_errors', $displayedErrors)
                ->with('perubahan_kelas_errors_remaining', count($result['errors']) - count($displayedErrors));
        }

        if (empty($result['changes'])) {
            return redirect()->route('naik-kelas.index')
                ->with('error_perubahan_kelas', 'Tidak ada perubahan kelas yang perlu diproses (semua baris kelas barunya sama dengan kelas saat ini).');
        }

        session(['perubahan_kelas_preview' => $result['changes']]);

        return redirect()->route('naik-kelas.index')
            ->with('perubahan_kelas_preview_ready', true)
            ->with('perubahan_kelas_skipped_noop', $result['skipped_noop']);
    }
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: PASS (all tests so far)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/NaikKelasController.php routes/web.php tests/Feature/PerubahanKelasTest.php
git commit -m "feat: add perubahan kelas preview endpoint with row validation"
```

---

### Task 3: Capacity check (max 40 per class, projected end state)

**Files:**
- Modify: `app/Http/Controllers/NaikKelasController.php:validatePerubahanKelasRows`
- Modify: `tests/Feature/PerubahanKelasTest.php`

**Interfaces:**
- Consumes: `validatePerubahanKelasRows()`, `makeAdmin()`, `makeKelas()`, `makeMurid()`, `makeXlsxUploadFile()` from Tasks 1–2.
- Produces: no new public interface — extends the existing `validatePerubahanKelasRows()` contract by populating `errors` when the projected class size exceeds 40.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PerubahanKelasTest.php`:

```php
test('preview rejects when destination kelas would exceed 40 active students', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');

    for ($i = 1; $i <= 40; $i++) {
        makeMurid(str_pad((string) $i, 10, '0', STR_PAD_LEFT), 'XI-2');
    }
    makeMurid('9999999998', 'XI-1', 'Aktif', 'Calon Pindah');

    $file = makeXlsxUploadFile([['9999999998', 'XI-2']]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    expect(session('perubahan_kelas_errors'))
        ->toContain("Kelas 'XI-2' akan melebihi kapasitas 40 murid (saat ini 40, akan menjadi 41)");
    $this->assertNull(session('perubahan_kelas_preview'));
});

test('preview allows a same-size swap between two classes without false capacity rejection', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');

    for ($i = 1; $i <= 40; $i++) {
        makeMurid('a'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), 'XI-1');
    }
    for ($i = 1; $i <= 40; $i++) {
        makeMurid('b'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), 'XI-2');
    }

    $file = makeXlsxUploadFile([
        ['a000000001', 'XI-2'],
        ['b000000001', 'XI-1'],
    ]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    $response->assertSessionHas('perubahan_kelas_preview_ready', true);
    expect(session('perubahan_kelas_preview'))->toHaveCount(2);
});

test('preview combines multiple rows moving into the same class for capacity check', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');

    for ($i = 1; $i <= 39; $i++) {
        makeMurid('c'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), 'XI-2');
    }
    makeMurid('d000000001', 'XI-1');
    makeMurid('d000000002', 'XI-1');

    $file = makeXlsxUploadFile([
        ['d000000001', 'XI-2'],
        ['d000000002', 'XI-2'],
    ]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    expect(session('perubahan_kelas_errors'))
        ->toContain("Kelas 'XI-2' akan melebihi kapasitas 40 murid (saat ini 39, akan menjadi 41)");
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — the 3 new tests fail because no capacity error is raised (the first test's `assertNull(session('perubahan_kelas_preview'))` fails since the row currently validates as OK).

- [ ] **Step 3: Add the capacity check**

In `app/Http/Controllers/NaikKelasController.php`, inside `validatePerubahanKelasRows`, find the tail of the method:

```php
            $changes[] = [
                'NIS' => $nis,
                'nama_lengkap' => $murid->nama_lengkap,
                'kelas_lama' => $kelasLama,
                'kelas_baru' => $kelasBaru,
            ];
        }

        return [
            'errors' => $errors,
            'changes' => $changes,
            'skipped_noop' => $skippedNoop,
        ];
    }
```

Replace with:

```php
            $changes[] = [
                'NIS' => $nis,
                'nama_lengkap' => $murid->nama_lengkap,
                'kelas_lama' => $kelasLama,
                'kelas_baru' => $kelasBaru,
            ];
        }

        if (! empty($netChange)) {
            $currentCounts = Murid::where('status', 'Aktif')
                ->whereIn('kelas', array_keys($netChange))
                ->selectRaw('kelas, count(*) as total')
                ->groupBy('kelas')
                ->pluck('total', 'kelas');

            foreach ($netChange as $kelas => $delta) {
                $current = (int) ($currentCounts[$kelas] ?? 0);
                $projected = $current + $delta;

                if ($projected > 40) {
                    $errors[] = "Kelas '{$kelas}' akan melebihi kapasitas 40 murid (saat ini {$current}, akan menjadi {$projected})";
                }
            }
        }

        return [
            'errors' => $errors,
            'changes' => $changes,
            'skipped_noop' => $skippedNoop,
        ];
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: PASS (all tests so far)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/NaikKelasController.php tests/Feature/PerubahanKelasTest.php
git commit -m "feat: enforce projected 40-student capacity in perubahan kelas validation"
```

---

### Task 4: Confirm (proses) endpoint

**Files:**
- Modify: `app/Http/Controllers/NaikKelasController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/PerubahanKelasTest.php`

**Interfaces:**
- Consumes: `validatePerubahanKelasRows()`, session key `perubahan_kelas_preview`, `makeAdmin()`, `makeKelas()`, `makeMurid()` from Tasks 1–3.
- Produces: route `naik-kelas.perubahan-kelas.proses` (POST) → `NaikKelasController::prosesPerubahanKelas(Request $request)`. On success, flashes `success` (string) and clears the `perubahan_kelas_preview` session key. On failure, flashes `error_perubahan_kelas` (string), clears the session key, never partially commits.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PerubahanKelasTest.php`:

```php
test('proses commits changes from session and clears it', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');
    makeMurid('1111111111', 'XI-1', 'Aktif', 'Budi');

    session(['perubahan_kelas_preview' => [
        ['NIS' => '1111111111', 'nama_lengkap' => 'Budi', 'kelas_lama' => 'XI-1', 'kelas_baru' => 'XI-2'],
    ]]);

    $response = $this->actingAs($admin)->post(route('naik-kelas.perubahan-kelas.proses'));

    $response->assertRedirect(route('naik-kelas.index'));
    $response->assertSessionHas('success');
    $this->assertNull(session('perubahan_kelas_preview'));
    expect(\App\Models\Murid::find('1111111111')->kelas)->toBe('XI-2');
});

test('proses redirects with error when there is no preview session data', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->post(route('naik-kelas.perubahan-kelas.proses'));

    $response->assertSessionHas('error_perubahan_kelas');
});

test('proses rejects and clears session when data became stale since preview', function () {
    $admin = makeAdmin();
    makeKelas('XI-1');
    makeKelas('XI-2');
    $murid = makeMurid('1111111111', 'XI-1', 'Aktif', 'Budi');

    session(['perubahan_kelas_preview' => [
        ['NIS' => '1111111111', 'nama_lengkap' => 'Budi', 'kelas_lama' => 'XI-1', 'kelas_baru' => 'XI-2'],
    ]]);

    // Kondisi berubah: murid sudah lulus sebelum konfirmasi diproses
    $murid->update(['status' => 'Lulus', 'kelas' => null]);

    $response = $this->actingAs($admin)->post(route('naik-kelas.perubahan-kelas.proses'));

    $response->assertSessionHas('error_perubahan_kelas');
    $this->assertNull(session('perubahan_kelas_preview'));
    expect($murid->fresh()->kelas)->toBeNull();
});

test('non-admin cannot proses perubahan kelas', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);

    $response = $this->actingAs($walikelas)->post(route('naik-kelas.perubahan-kelas.proses'));

    $response->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — route `naik-kelas.perubahan-kelas.proses` not defined.

- [ ] **Step 3: Add the route**

In `routes/web.php`, find the line added in Task 2:

```php
    Route::post('/naik-kelas/perubahan-kelas/preview', [NaikKelasController::class, 'previewPerubahanKelas'])->name('naik-kelas.perubahan-kelas.preview');
```

Add immediately after it:

```php
    Route::post('/naik-kelas/perubahan-kelas/proses', [NaikKelasController::class, 'prosesPerubahanKelas'])->name('naik-kelas.perubahan-kelas.proses');
```

- [ ] **Step 4: Implement the confirm action**

Add this method at the end of the class in `app/Http/Controllers/NaikKelasController.php` (after `previewPerubahanKelas`, before the final closing `}`):

```php
    /**
     * Konfirmasi & simpan perubahan kelas yang tersimpan di session (dari previewPerubahanKelas).
     * Re-validasi singkat sebelum commit karena kondisi bisa berubah sejak preview dibuat.
     */
    public function prosesPerubahanKelas(Request $request)
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        $previewChanges = session('perubahan_kelas_preview');

        if (empty($previewChanges)) {
            return redirect()->route('naik-kelas.index')
                ->with('error_perubahan_kelas', 'Tidak ada data preview. Silakan upload ulang file perubahan kelas.');
        }

        $rows = [];
        foreach ($previewChanges as $index => $change) {
            $rows[] = [
                'row_number' => $index + 1,
                'nis' => $change['NIS'],
                'kelas_baru' => $change['kelas_baru'],
            ];
        }

        $result = $this->validatePerubahanKelasRows($rows);

        if (! empty($result['errors'])) {
            session()->forget('perubahan_kelas_preview');

            return redirect()->route('naik-kelas.index')
                ->with('perubahan_kelas_errors', array_slice($result['errors'], 0, 10))
                ->with('error_perubahan_kelas', 'Data sudah berubah sejak preview dibuat. Silakan upload ulang file perubahan kelas.');
        }

        DB::beginTransaction();
        try {
            foreach ($result['changes'] as $change) {
                Murid::where('NIS', $change['NIS'])->update(['kelas' => $change['kelas_baru']]);
            }

            DB::commit();
            session()->forget('perubahan_kelas_preview');

            return redirect()->route('naik-kelas.index')
                ->with('success', count($result['changes']).' murid berhasil dipindahkan kelasnya.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('naik-kelas.index')
                ->with('error_perubahan_kelas', 'Terjadi kesalahan saat memproses perubahan kelas: '.$e->getMessage());
        }
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: PASS (all tests so far)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/NaikKelasController.php routes/web.php tests/Feature/PerubahanKelasTest.php
git commit -m "feat: add perubahan kelas confirm endpoint with re-validation and transaction commit"
```

---

### Task 5: Cancel (batal) endpoint

**Files:**
- Modify: `app/Http/Controllers/NaikKelasController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/PerubahanKelasTest.php`

**Interfaces:**
- Consumes: `makeAdmin()` from Task 1.
- Produces: route `naik-kelas.perubahan-kelas.batal` (POST) → `NaikKelasController::batalPerubahanKelas()`. Clears session key `perubahan_kelas_preview`.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/PerubahanKelasTest.php`:

```php
test('batal clears preview session data', function () {
    $admin = makeAdmin();

    session(['perubahan_kelas_preview' => [
        ['NIS' => '1111111111', 'nama_lengkap' => 'Budi', 'kelas_lama' => 'XI-1', 'kelas_baru' => 'XI-2'],
    ]]);

    $response = $this->actingAs($admin)->post(route('naik-kelas.perubahan-kelas.batal'));

    $response->assertRedirect(route('naik-kelas.index'));
    $this->assertNull(session('perubahan_kelas_preview'));
});

test('non-admin cannot batal perubahan kelas', function () {
    $walikelas = User::factory()->create(['role' => 'Walikelas']);

    $response = $this->actingAs($walikelas)->post(route('naik-kelas.perubahan-kelas.batal'));

    $response->assertRedirect(route('dashboard'));
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — route `naik-kelas.perubahan-kelas.batal` not defined.

- [ ] **Step 3: Add the route**

In `routes/web.php`, find the line added in Task 4:

```php
    Route::post('/naik-kelas/perubahan-kelas/proses', [NaikKelasController::class, 'prosesPerubahanKelas'])->name('naik-kelas.perubahan-kelas.proses');
```

Add immediately after it:

```php
    Route::post('/naik-kelas/perubahan-kelas/batal', [NaikKelasController::class, 'batalPerubahanKelas'])->name('naik-kelas.perubahan-kelas.batal');
```

- [ ] **Step 4: Implement the cancel action**

Add this method at the end of the class in `app/Http/Controllers/NaikKelasController.php` (after `prosesPerubahanKelas`, before the final closing `}`):

```php
    /**
     * Batalkan preview perubahan kelas yang tersimpan di session.
     */
    public function batalPerubahanKelas()
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        session()->forget('perubahan_kelas_preview');

        return redirect()->route('naik-kelas.index')->with('success', 'Preview perubahan kelas dibatalkan.');
    }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: PASS (all tests so far)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/NaikKelasController.php routes/web.php tests/Feature/PerubahanKelasTest.php
git commit -m "feat: add perubahan kelas cancel endpoint"
```

---

### Task 6: "Form Perubahan Kelas" UI

**Files:**
- Modify: `resources/views/naik-kelas/index.blade.php`
- Modify: `tests/Feature/PerubahanKelasTest.php`

**Interfaces:**
- Consumes: routes `naik-kelas.perubahan-kelas.template`, `.preview`, `.proses`, `.batal` (Tasks 1, 2, 4, 5), session keys `perubahan_kelas_preview`, `perubahan_kelas_errors`, `perubahan_kelas_errors_remaining`, `perubahan_kelas_skipped_noop`, `error_perubahan_kelas` (Tasks 2–5). Existing Blade components `<x-modal>`, `<x-primary-button>`, `<x-secondary-button>`, `<x-danger-button>`, `<x-input-error>` (already used elsewhere in this same view).
- Produces: no new backend interface — this is the last piece wiring the existing endpoints to a visible form.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/PerubahanKelasTest.php`:

```php
test('naik kelas page shows perubahan kelas upload form by default', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->get(route('naik-kelas.index'));

    $response->assertOk();
    $response->assertSee('Form Perubahan Kelas');
    $response->assertSee('Download Template');
    $response->assertDontSee('Konfirmasi & Simpan Perubahan');
});

test('naik kelas page shows validation errors for perubahan kelas', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->withSession(['perubahan_kelas_errors' => ['Baris 2: NIS 123 tidak ditemukan atau bukan murid aktif']])
        ->get(route('naik-kelas.index'));

    $response->assertSee('Baris 2: NIS 123 tidak ditemukan atau bukan murid aktif');
});

test('naik kelas page shows preview table and confirm button when preview session exists', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->withSession(['perubahan_kelas_preview' => [
            ['NIS' => '1111111111', 'nama_lengkap' => 'Budi', 'kelas_lama' => 'XI-1', 'kelas_baru' => 'XI-2'],
        ]])
        ->get(route('naik-kelas.index'));

    $response->assertSee('Budi');
    $response->assertSee('XI-1');
    $response->assertSee('XI-2');
    $response->assertSee('Konfirmasi & Simpan Perubahan');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: FAIL — the view doesn't contain "Form Perubahan Kelas" yet.

- [ ] **Step 3: Add the UI section**

In `resources/views/naik-kelas/index.blade.php`, find this exact block (the tail of the existing "Form Naik Kelas" card, right before the "Modal Pilih Murid Tetap" section):

```blade
                    <!-- Confirmation Modal Tahap 2 -->
                    <x-modal name="confirm-step-2" :show="false" focusable>
                        <div class="p-6 border-t-8 border-red-600">
                            <h2 class="text-xl font-bold text-red-600 flex items-center mb-4">
                                <svg class="w-6 h-6 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                KONFIRMASI AKHIR
                            </h2>
                            <p class="text-base text-gray-800 font-medium">Apakah Anda benar-benar yakin?</p>
                            <p class="text-red-600 mt-2 font-bold mb-6">Proses ini akan mempengaruhi SEMUA KELAS dan TIDAK DAPAT DIBATALKAN!</p>
                            <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">
                                    Batal
                                </x-secondary-button>
                                <x-danger-button x-on:click="submitForm()">
                                    YA, PROSES SEKARANG
                                </x-danger-button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih Murid Tetap -->
```

Replace with (this keeps everything identical and inserts a new sibling card before the `max-w-4xl` container closes):

```blade
                    <!-- Confirmation Modal Tahap 2 -->
                    <x-modal name="confirm-step-2" :show="false" focusable>
                        <div class="p-6 border-t-8 border-red-600">
                            <h2 class="text-xl font-bold text-red-600 flex items-center mb-4">
                                <svg class="w-6 h-6 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                KONFIRMASI AKHIR
                            </h2>
                            <p class="text-base text-gray-800 font-medium">Apakah Anda benar-benar yakin?</p>
                            <p class="text-red-600 mt-2 font-bold mb-6">Proses ini akan mempengaruhi SEMUA KELAS dan TIDAK DAPAT DIBATALKAN!</p>
                            <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">
                                    Batal
                                </x-secondary-button>
                                <x-danger-button x-on:click="submitForm()">
                                    YA, PROSES SEKARANG
                                </x-danger-button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mt-8" x-data="{}">
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Form Perubahan Kelas
                    </h3>
                </div>
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-6">
                        Ubah kelas beberapa murid aktif sekaligus dengan upload file. Fitur ini berdiri sendiri, bisa dipakai kapan saja tanpa perlu menjalankan proses Naik Kelas terlebih dahulu.
                    </p>

                    @if (session('error_perubahan_kelas'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md" role="alert">
                            <p class="font-medium">{{ session('error_perubahan_kelas') }}</p>
                        </div>
                    @endif

                    @if (session('perubahan_kelas_errors'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md" role="alert">
                            <p class="font-semibold mb-2">File berisi data yang tidak valid. Tidak ada perubahan yang disimpan:</p>
                            <ul class="text-sm list-disc list-inside space-y-1">
                                @foreach (session('perubahan_kelas_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            @if (session('perubahan_kelas_errors_remaining', 0) > 0)
                                <p class="text-sm mt-2">dan {{ session('perubahan_kelas_errors_remaining') }} error lainnya.</p>
                            @endif
                            <p class="text-sm mt-3">Perbaiki file lalu upload ulang.</p>
                        </div>
                    @endif

                    @if (session('perubahan_kelas_preview'))
                        @php $previewData = session('perubahan_kelas_preview'); @endphp
                        <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 px-6 py-4 rounded-lg">
                            <p class="font-semibold">{{ count($previewData) }} murid akan dipindah kelasnya.</p>
                            @if (session('perubahan_kelas_skipped_noop', 0) > 0)
                                <p class="text-sm mt-1">{{ session('perubahan_kelas_skipped_noop') }} baris dilewati karena kelas baru sama dengan kelas saat ini.</p>
                            @endif
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-lg mb-6">
                            <table class="w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">NIS</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">Nama</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">Kelas Lama</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">Kelas Baru</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($previewData as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-mono">{{ $item['NIS'] }}</td>
                                            <td class="px-4 py-2 text-sm">{{ $item['nama_lengkap'] }}</td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-semibold">{{ $item['kelas_lama'] }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">{{ $item['kelas_baru'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                            <form method="POST" action="{{ route('naik-kelas.perubahan-kelas.batal') }}">
                                @csrf
                                <x-secondary-button type="submit" class="w-full md:w-auto justify-center">
                                    Batal
                                </x-secondary-button>
                            </form>
                            <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'confirm-perubahan-kelas')" class="w-full md:w-auto justify-center">
                                Konfirmasi & Simpan Perubahan
                            </x-primary-button>
                        </div>

                        <x-modal name="confirm-perubahan-kelas" :show="false" focusable>
                            <div class="p-6 border-t-8 border-yellow-500">
                                <h2 class="text-xl font-bold text-red-600 flex items-center mb-4">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    KONFIRMASI PERUBAHAN KELAS
                                </h2>
                                <p class="text-base text-gray-800 font-medium">
                                    Anda akan memindahkan {{ count($previewData) }} murid ke kelas baru sesuai tabel preview.
                                </p>
                                <p class="text-red-600 mt-2 font-bold mb-6">Proses ini tidak dapat dibatalkan!</p>
                                <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">
                                        Batal
                                    </x-secondary-button>
                                    <form method="POST" action="{{ route('naik-kelas.perubahan-kelas.proses') }}">
                                        @csrf
                                        <x-danger-button type="submit">
                                            YA, SIMPAN PERUBAHAN
                                        </x-danger-button>
                                    </form>
                                </div>
                            </div>
                        </x-modal>
                    @else
                        <div class="flex flex-col sm:flex-row gap-3 mb-4">
                            <a href="{{ route('naik-kelas.perubahan-kelas.template') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                                Download Template
                            </a>
                        </div>
                        <form method="POST" action="{{ route('naik-kelas.perubahan-kelas.preview') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label for="perubahan_kelas_file" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Upload File Perubahan Kelas (.xlsx)
                                </label>
                                <input type="file" name="file" id="perubahan_kelas_file" accept=".xlsx" required
                                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-teal-500 to-cyan-600 hover:from-teal-600 hover:to-cyan-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                                Preview Perubahan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih Murid Tetap -->
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=PerubahanKelasTest`
Expected: PASS (all tests)

- [ ] **Step 5: Run the full test suite to check for regressions**

Run: `php artisan test`
Expected: PASS (no regressions in other feature tests)

- [ ] **Step 6: Commit**

```bash
git add resources/views/naik-kelas/index.blade.php tests/Feature/PerubahanKelasTest.php
git commit -m "feat: add Form Perubahan Kelas UI to naik-kelas page"
```

---

### Task 7: Manual browser verification

**Files:** none (verification only)

**Interfaces:**
- Consumes: the full feature built in Tasks 1–6.

- [ ] **Step 1: Start the dev server**

Use the `run` skill (or `composer run dev` / `php artisan serve` directly) to start the application locally.

- [ ] **Step 2: Seed an Admin user and some test data**

Run: `php artisan tinker --execute="
\App\Models\User::firstOrCreate(['username' => 'admin-test'], ['nama_lengkap' => 'Admin Test', 'role' => 'Admin', 'password' => bcrypt('password')]);
\App\Models\Kelas::firstOrCreate(['kelas' => 'XI-1']);
\App\Models\Kelas::firstOrCreate(['kelas' => 'XI-2']);
\App\Models\Murid::firstOrCreate(['NIS' => '9000000001'], ['nama_lengkap' => 'Murid Uji Coba', 'gender' => 'Laki-laki', 'kelas' => 'XI-1', 'status' => 'Aktif']);
"`

- [ ] **Step 3: Walk through the happy path in the browser**

1. Log in as `admin-test`.
2. Go to `/naik-kelas`.
3. Confirm the "Form Perubahan Kelas" card is visible below "Form Naik Kelas", with a "Download Template" link and an upload form — and that this is true even though "Form Naik Kelas" was never submitted.
4. Click "Download Template", open the downloaded `.xlsx`, confirm headers are `NIS` / `Kelas Baru` with example rows.
5. Edit the file: put `9000000001` / `XI-2` in the first data row, delete the example rows.
6. Upload it via "Preview Perubahan".
7. Confirm the preview table shows NIS `9000000001`, name "Murid Uji Coba", `XI-1 → XI-2`.
8. Click "Konfirmasi & Simpan Perubahan", confirm the modal, submit.
9. Confirm a success message appears and the murid's kelas is now `XI-2` (check via `/murid` list or `php artisan tinker --execute="echo \App\Models\Murid::find('9000000001')->kelas;"`).

- [ ] **Step 4: Verify the error path**

1. Go back to `/naik-kelas`, upload a file referencing a NIS that doesn't exist.
2. Confirm the error list renders and no preview table appears.

- [ ] **Step 5: Verify access control**

1. Log in as a non-Admin user (e.g. role `Walikelas`).
2. Visit `/naik-kelas` directly — confirm redirect to dashboard with the permission-denied message.

- [ ] **Step 6: Report results**

Note any visual issues or unexpected behavior found during manual testing; fix if needed before considering the feature complete.

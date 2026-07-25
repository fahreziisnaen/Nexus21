<?php

use App\Models\User;

function makeAdmin(): User
{
    return User::factory()->create(['role' => 'Admin']);
}

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

test('preview shows graceful validation error, not a crash, when a row has only NIS and no cell at all for Kelas Baru', function () {
    $admin = makeAdmin();

    // Row::fromValues(['1111111111']) writes a row with only 1 cell (no second
    // cell at all, not even an empty one) -- OpenSpout's per-row cell array is
    // only as wide as that row's own span, so getCellAtIndex(1) must be handled
    // as possibly-null rather than assumed to exist.
    $file = makeXlsxUploadFile([['1111111111']]);

    $response = $this->actingAs($admin)
        ->post(route('naik-kelas.perubahan-kelas.preview'), ['file' => $file]);

    expect(session('perubahan_kelas_errors'))
        ->toContain('Baris 2: NIS dan Kelas Baru wajib diisi');
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

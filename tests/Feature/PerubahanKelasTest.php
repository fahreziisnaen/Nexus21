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

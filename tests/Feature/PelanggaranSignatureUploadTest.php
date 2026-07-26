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

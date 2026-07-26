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

test('CLEAR actually deletes a real stored signature file with a hyphenated timestamp filename', function () {
    // Regression test: the generated filename format is "{name}_{Y-m-d_H-i-s}_ttd.png",
    // which contains hyphens from the timestamp. A validation regex without "-" in its
    // character class would silently reject every real stored path, making CLEAR a no-op
    // that nulls the DB column but leaves the file on disk (and still publicly served).
    $user = makeStaffUser();
    $murid = makeSignatureTestMurid('1313131313');
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

    $uploadResponse = $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => tinyPngDataUri(),
    ]);
    $uploadResponse->assertRedirect(route('pelanggaran.index'));

    $pelanggaran->refresh();
    expect($pelanggaran->ttd_murid)->not->toBeNull();
    // Sanity check that this is a realistic, hyphenated filename (not the brief's
    // hyphen-free fixture), so this test actually exercises the fixed regex.
    expect($pelanggaran->ttd_murid)->toContain('-');

    $fullPath = storage_path('app/public/' . $pelanggaran->ttd_murid);
    expect(file_exists($fullPath))->toBeTrue();

    $clearResponse = $this->actingAs($user)->put(route('pelanggaran.update', $pelanggaran), [
        'NIS' => $murid->NIS,
        'jenis_pelanggaran' => $jenis->nama,
        'tanggal' => now()->toDateString(),
        'ttd_murid' => 'CLEAR',
    ]);
    $clearResponse->assertRedirect(route('pelanggaran.index'));

    expect(file_exists($fullPath))->toBeFalse();

    $pelanggaran->refresh();
    expect($pelanggaran->ttd_murid)->toBeNull();
});

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

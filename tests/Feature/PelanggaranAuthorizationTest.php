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

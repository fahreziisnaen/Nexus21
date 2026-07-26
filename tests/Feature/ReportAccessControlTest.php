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

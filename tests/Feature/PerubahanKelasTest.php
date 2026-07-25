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

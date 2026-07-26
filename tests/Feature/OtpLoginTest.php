<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_otp_verification()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'nomor_telepon' => '08123456789',
        ]);

        Http::fake([
            'whatsapp:3000/send-message' => Http::response(['status' => true], 200),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/verify-otp');
        $response->assertSessionHas('auth_otp');
        $response->assertSessionHas('auth_username', 'testuser');
    }

    public function test_otp_verification_logs_in_user()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withSession([
            'auth_otp' => '123456',
            'auth_username' => 'testuser',
        ])->post('/verify-otp', [
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

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
}

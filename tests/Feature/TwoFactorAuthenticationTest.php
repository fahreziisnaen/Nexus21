<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = app(TotpService::class);
    }

    /**
     * User dengan 2FA aktif dan secret yang diketahui, supaya test bisa
     * menghitung kode yang valid.
     */
    private function userWithTwoFactor(array $attributes = []): array
    {
        $secret = $this->totp->generateSecret();

        $user = User::factory()->create(array_merge([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'nomor_telepon' => '08123456789',
        ], $attributes));

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user->fresh(), $secret];
    }

    private function currentCode(string $secret): string
    {
        return $this->totp->codeForTimestep($secret, $this->totp->timestepAt());
    }

    // ── Pemilihan metode saat login ──────────────────────────────────────

    public function test_user_without_two_factor_still_gets_whatsapp_otp(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'nomor_telepon' => '08123456789',
        ]);

        Http::fake(['whatsapp:3000/send-message' => Http::response(['status' => true], 200)]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertSessionHas('show_otp', true);
        $response->assertSessionHas('auth_otp');
        $response->assertSessionMissing('auth_2fa_username');
        $this->assertGuest();
    }

    public function test_user_with_two_factor_is_challenged_by_authenticator_and_no_whatsapp_is_sent(): void
    {
        [$user] = $this->userWithTwoFactor();

        Http::fake();

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertSessionHas('show_2fa', true);
        $response->assertSessionHas('auth_2fa_username', $user->username);

        // Inti dari permintaan: kalau 2FA aktif, jalur WhatsApp mati total.
        $response->assertSessionMissing('auth_otp');
        Http::assertNothingSent();

        $this->assertGuest();
    }

    public function test_password_is_still_required_before_the_authenticator_challenge(): void
    {
        $this->userWithTwoFactor();

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('username');
        $response->assertSessionMissing('auth_2fa_username');
        $this->assertGuest();
    }

    // ── Verifikasi kode ──────────────────────────────────────────────────

    public function test_valid_authenticator_code_logs_the_user_in(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', [
            'code' => $this->currentCode($secret),
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_code_does_not_log_in_and_counts_the_attempt(): void
    {
        [$user] = $this->userWithTwoFactor();

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        $response->assertSessionHas('auth_2fa_attempts', 1);
        $this->assertGuest();
    }

    public function test_challenge_is_abandoned_after_five_wrong_attempts(): void
    {
        [$user] = $this->userWithTwoFactor();

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 4,
        ])->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('auth_2fa_username');
        $this->assertGuest();
    }

    public function test_expired_challenge_is_rejected(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->subMinute(),
        ])->post('/two-factor-challenge', [
            'code' => $this->currentCode($secret),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_challenge_without_a_session_is_rejected(): void
    {
        [, $secret] = $this->userWithTwoFactor();

        $response = $this->post('/two-factor-challenge', [
            'code' => $this->currentCode($secret),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Kode yang sama tidak boleh dipakai dua kali, walaupun secara waktu masih
     * berada di dalam window toleransi.
     */
    public function test_a_code_cannot_be_replayed(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();
        $code = $this->currentCode($secret);

        $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', ['code' => $code]);

        $response->assertSessionHas('show_2fa', true);
        $this->assertGuest();
    }

    // ── Recovery code ────────────────────────────────────────────────────

    public function test_recovery_code_logs_the_user_in_and_is_consumed(): void
    {
        [$user] = $this->userWithTwoFactor();

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', [
            'recovery_code' => 'AAAAA-BBBBB',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->assertSame(['CCCCC-DDDDD'], array_values($user->fresh()->two_factor_recovery_codes));
    }

    public function test_a_recovery_code_cannot_be_used_twice(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB']);

        $this->post('/logout');

        $response = $this->withSession([
            'auth_2fa_username' => $user->username,
            'auth_2fa_expires_at' => now()->addMinutes(5),
            'auth_2fa_attempts' => 0,
        ])->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB']);

        $response->assertSessionHas('show_2fa', true);
        $this->assertGuest();
    }

    // ── Jalur WhatsApp harus tertutup saat 2FA aktif ─────────────────────

    public function test_a_stale_whatsapp_otp_cannot_bypass_the_authenticator(): void
    {
        [$user] = $this->userWithTwoFactor();

        // Mensimulasikan OTP yang terbit sebelum user mengaktifkan 2FA dan masih
        // tertinggal di session.
        $response = $this->withSession([
            'auth_otp' => '123456',
            'auth_username' => $user->username,
            'auth_otp_expires_at' => now()->addMinutes(5),
        ])->post('/verify-otp', ['otp' => '123456']);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ── Pengaturan dari halaman Profile ──────────────────────────────────

    public function test_enabling_stores_a_pending_secret_without_activating_yet(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $response = $this->actingAs($user)->post(route('two-factor.enable'));

        $response->assertSessionHas('two_factor_setup', true);
        $response->assertSessionHas('two_factor_pending_secret');

        // Belum aktif sampai dikonfirmasi — OTP WhatsApp harus masih berlaku.
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_confirming_with_a_valid_code_activates_two_factor_and_returns_recovery_codes(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $secret = $this->totp->generateSecret();

        $response = $this->actingAs($user)
            ->withSession(['two_factor_pending_secret' => $secret])
            ->post(route('two-factor.confirm'), ['code' => $this->currentCode($secret)]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('two_factor_recovery_codes');

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasTwoFactorEnabled());
        $this->assertSame($secret, $fresh->two_factor_secret);
        $this->assertCount(8, $fresh->two_factor_recovery_codes);
    }

    public function test_confirming_with_a_wrong_code_does_not_activate_two_factor(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $secret = $this->totp->generateSecret();

        $response = $this->actingAs($user)
            ->withSession(['two_factor_pending_secret' => $secret])
            ->post(route('two-factor.confirm'), ['code' => '000000']);

        $response->assertSessionHas('two_factor_setup', true);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_disabling_requires_the_correct_password(): void
    {
        [$user] = $this->userWithTwoFactor();

        $response = $this->actingAs($user)
            ->delete(route('two-factor.disable'), ['password' => 'password-salah']);

        $response->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_disabling_with_the_correct_password_restores_whatsapp_otp(): void
    {
        [$user] = $this->userWithTwoFactor();

        $response = $this->actingAs($user)
            ->delete(route('two-factor.disable'), ['password' => 'password123']);

        $response->assertRedirect(route('profile.edit'));

        $fresh = $user->fresh();
        $this->assertFalse($fresh->hasTwoFactorEnabled());
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
    }

    public function test_regenerating_recovery_codes_replaces_the_old_ones(): void
    {
        [$user] = $this->userWithTwoFactor();
        $old = $user->two_factor_recovery_codes;

        $response = $this->actingAs($user)
            ->post(route('two-factor.recovery-codes'), ['password' => 'password123']);

        $response->assertSessionHas('two_factor_recovery_codes');

        $new = $user->fresh()->two_factor_recovery_codes;
        $this->assertCount(8, $new);
        $this->assertEmpty(array_intersect($old, $new));
    }

    // ── Reset oleh Admin ─────────────────────────────────────────────────

    public function test_admin_can_reset_another_users_two_factor(): void
    {
        [$target] = $this->userWithTwoFactor(['username' => 'walikelas1', 'role' => 'Walikelas']);
        $admin = User::factory()->create(['username' => 'admin1', 'role' => 'Admin']);

        $response = $this->actingAs($admin)
            ->post(route('users.two-factor.reset', $target));

        $response->assertRedirect(route('users.edit', $target));
        $this->assertFalse($target->fresh()->hasTwoFactorEnabled());
    }

    public function test_walikelas_cannot_reset_two_factor(): void
    {
        [$target] = $this->userWithTwoFactor(['username' => 'walikelas1', 'role' => 'Walikelas']);
        $other = User::factory()->create(['username' => 'walikelas2', 'role' => 'Walikelas']);

        $response = $this->actingAs($other)
            ->post(route('users.two-factor.reset', $target));

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($target->fresh()->hasTwoFactorEnabled());
    }

    public function test_tatib_cannot_reset_an_admins_two_factor(): void
    {
        [$admin] = $this->userWithTwoFactor(['username' => 'admin1', 'role' => 'Admin']);
        $tatib = User::factory()->create(['username' => 'tatib1', 'role' => 'TATIB']);

        $response = $this->actingAs($tatib)
            ->post(route('users.two-factor.reset', $admin));

        $response->assertRedirect(route('users.index'));
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    // ── Penyimpanan ──────────────────────────────────────────────────────

    public function test_secret_and_recovery_codes_are_encrypted_at_rest(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();

        $raw = \Illuminate\Support\Facades\DB::table('users')
            ->where('username', $user->username)
            ->first();

        $this->assertNotSame($secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString($secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString('AAAAA-BBBBB', $raw->two_factor_recovery_codes);

        // Tetap terbaca lewat model.
        $this->assertSame($secret, $user->fresh()->two_factor_secret);
    }
}

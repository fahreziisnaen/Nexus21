<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji halaman penyiapan 2FA benar-benar merender bahan yang dibutuhkan
 * browser untuk menggambar QR code.
 *
 * Ada karena bug sebelumnya: QR tidak pernah muncul walaupun controller dan
 * service-nya benar, karena bagian rendering di sisi HTML-nya yang salah.
 */
class TwoFactorSetupPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_page_renders_the_qr_canvas_with_a_usable_otpauth_uri(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $html = $this->actingAs($user)
            ->withSession([
                'two_factor_pending_secret' => $secret,
                'two_factor_setup' => true,
            ])
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        // Canvas harus membawa URI-nya sendiri — itu yang dibaca modul Vite.
        $this->assertStringContainsString('data-qr-uri="otpauth://totp/', $html);
        $this->assertStringContainsString('data-qr-fallback="two-factor-qr-fallback"', $html);

        // Secret yang benar harus ikut terbawa di URI-nya.
        $this->assertStringContainsString('secret='.$secret, $html);

        // Kunci manual tetap ditampilkan sebagai cadangan.
        $this->assertStringContainsString($totp->formatSecretForDisplay($secret), $html);
    }

    /**
     * Script inline tidak boleh dipakai untuk memanggil QRCode: @vite() merender
     * <script type="module"> yang selalu deferred, sehingga script inline dijamin
     * jalan lebih dulu dan selalu jatuh ke pesan fallback. Ini justru bug yang
     * pernah terjadi, jadi dikunci lewat test.
     */
    public function test_setup_page_does_not_call_qrcode_from_an_inline_script(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $html = $this->actingAs($user)
            ->withSession([
                'two_factor_pending_secret' => app(TotpService::class)->generateSecret(),
                'two_factor_setup' => true,
            ])
            ->get(route('profile.edit'))
            ->getContent();

        $this->assertStringNotContainsString('QRCode.toCanvas', $html);
        $this->assertStringNotContainsString("typeof QRCode", $html);

        // Asset QR tetap harus dimuat di halaman ini.
        $this->assertMatchesRegularExpression('/<script[^>]+type="module"[^>]+qrcode[^>]*>/i', $html);
    }

    public function test_profile_page_without_setup_mode_does_not_load_the_qr_asset(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-qr-uri', $html);
        $this->assertStringNotContainsString('qrcode', strtolower($html));
    }

    public function test_cancellation_modal_renders_with_cancelled_title_and_not_success(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $response = $this->actingAs($user)
            ->withSession(['cancelled' => 'Pendaftaran autentikasi dua faktor dibatalkan.'])
            ->get(route('profile.edit'))
            ->assertOk();

        $response->assertSee('Dibatalkan');
        $response->assertSee('Pendaftaran autentikasi dua faktor dibatalkan.');
        $response->assertDontSee('showSuccessModal: true');
    }
}

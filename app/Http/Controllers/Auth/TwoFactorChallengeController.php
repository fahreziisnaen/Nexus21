<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Tahap kedua login untuk user yang memakai authenticator (TOTP).
 *
 * Dipanggil hanya setelah password terverifikasi di AuthenticatedSessionController;
 * user belum login sampai kode di sini benar. Strukturnya sengaja dibuat sejajar
 * dengan OtpController (WhatsApp) supaya kedua jalur berperilaku sama.
 */
class TwoFactorChallengeController extends Controller
{
    /** Batas percobaan sebelum sesi login dibatalkan, sama dengan jalur OTP WhatsApp. */
    private const MAX_ATTEMPTS = 5;

    private const SESSION_KEYS = [
        'auth_2fa_username',
        'auth_2fa_remember',
        'auth_2fa_expires_at',
        'auth_2fa_attempts',
    ];

    public function __construct(private readonly TotpService $totp) {}

    public function store(Request $request): RedirectResponse
    {
        $username = $request->session()->get('auth_2fa_username');
        $remember = $request->session()->get('auth_2fa_remember', false);
        $expiresAt = $request->session()->get('auth_2fa_expires_at');

        if (! $username) {
            return redirect()->route('login')
                ->with('error', 'Sesi login telah berakhir. Silakan login kembali.');
        }

        if ($expiresAt && now()->isAfter($expiresAt)) {
            $request->session()->forget(self::SESSION_KEYS);

            return redirect()->route('login')
                ->with('error', 'Waktu verifikasi telah habis (5 menit). Silakan login kembali.');
        }

        $user = User::where('username', $username)->first();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(self::SESSION_KEYS);

            return redirect()->route('login')
                ->with('error', 'Pengguna tidak ditemukan atau autentikasi dua faktor sudah tidak aktif. Silakan login kembali.');
        }

        $code = trim((string) $request->input('code', ''));
        $recoveryCode = trim((string) $request->input('recovery_code', ''));

        if ($code === '' && $recoveryCode === '') {
            return $this->backToChallenge('Masukkan kode dari aplikasi authenticator atau salah satu recovery code Anda.');
        }

        // Recovery code diprioritaskan kalau diisi, supaya user yang kehilangan HP
        // tetap bisa masuk tanpa harus mengosongkan field kode dulu.
        if ($recoveryCode !== '') {
            if (! $user->consumeRecoveryCode($recoveryCode)) {
                return $this->registerFailure($request, 'Recovery code tidak valid atau sudah pernah dipakai.');
            }

            Log::warning("Login user {$user->username} memakai recovery code 2FA");

            return $this->completeLogin($request, $user, $remember, remainingRecoveryCodes: count($user->two_factor_recovery_codes ?? []));
        }

        $timestep = $this->totp->verify($user->two_factor_secret, $code);

        if ($timestep === null) {
            return $this->registerFailure($request, 'Kode autentikasi salah atau sudah kedaluwarsa.');
        }

        // Tolak kode yang sudah pernah dipakai. Tanpa ini, kode yang sama masih bisa
        // dipakai ulang selama ~90 detik (window drift) kalau sempat terlihat orang lain.
        if ($user->two_factor_last_timestep !== null && $timestep <= (int) $user->two_factor_last_timestep) {
            return $this->registerFailure($request, 'Kode ini sudah pernah dipakai. Tunggu kode berikutnya muncul di aplikasi authenticator Anda.');
        }

        $user->forceFill(['two_factor_last_timestep' => $timestep])->save();

        return $this->completeLogin($request, $user, $remember);
    }

    /**
     * Kode benar — login dan bersihkan state verifikasi.
     */
    private function completeLogin(Request $request, User $user, bool $remember, ?int $remainingRecoveryCodes = null): RedirectResponse
    {
        Auth::login($user, $remember);

        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_KEYS);

        $redirect = redirect()->intended(route('dashboard', absolute: false));

        if ($remainingRecoveryCodes !== null) {
            $redirect->with('warning', "Anda login menggunakan recovery code. Sisa recovery code: {$remainingRecoveryCodes}. Segera buat ulang recovery code dari halaman Profile.");
        }

        return $redirect;
    }

    /**
     * Kode salah — hitung percobaan, batalkan sesi kalau sudah terlalu banyak.
     */
    private function registerFailure(Request $request, string $message): RedirectResponse
    {
        $attempts = (int) $request->session()->get('auth_2fa_attempts', 0) + 1;
        $request->session()->put('auth_2fa_attempts', $attempts);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $request->session()->forget(self::SESSION_KEYS);

            return redirect()->route('login')
                ->with('error', 'Terlalu banyak percobaan kode yang salah. Silakan login kembali.');
        }

        return $this->backToChallenge($message);
    }

    private function backToChallenge(string $message): RedirectResponse
    {
        return back()->with([
            'show_2fa' => true,
            'error' => $message,
        ]);
    }
}

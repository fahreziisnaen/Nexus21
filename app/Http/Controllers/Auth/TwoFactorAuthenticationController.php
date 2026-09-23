<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Pengelolaan 2FA (authenticator) oleh user sendiri, dari halaman Profile.
 *
 * Alurnya: mulai (buat secret sementara) → scan QR → konfirmasi dengan satu kode
 * → 2FA aktif dan recovery code ditampilkan sekali.
 */
class TwoFactorAuthenticationController extends Controller
{
    /** Key session untuk secret yang belum dikonfirmasi. */
    private const PENDING_SECRET = 'two_factor_pending_secret';

    public function __construct(private readonly TotpService $totp) {}

    /**
     * Mulai pendaftaran: buat secret baru dan simpan di session (belum masuk DB).
     *
     * Secret sengaja belum disimpan ke DB supaya baris user tidak pernah punya secret
     * setengah jadi — 2FA baru "ada" setelah dikonfirmasi di confirm().
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Autentikasi dua faktor sudah aktif. Nonaktifkan dulu jika ingin mendaftarkan perangkat baru.');
        }

        $request->session()->put(self::PENDING_SECRET, $this->totp->generateSecret());

        return redirect()->route('profile.edit')->with('two_factor_setup', true);
    }

    /**
     * Batalkan pendaftaran yang sedang berjalan.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PENDING_SECRET);

        return redirect()->route('profile.edit')
            ->with('success', 'Pendaftaran autentikasi dua faktor dibatalkan.');
    }

    /**
     * Konfirmasi dengan satu kode dari authenticator, lalu aktifkan 2FA.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'code' => ['required', 'string'],
        ], [
            'code.required' => 'Kode dari aplikasi authenticator wajib diisi.',
        ]);

        $secret = $request->session()->get(self::PENDING_SECRET);

        if (! $secret) {
            return redirect()->route('profile.edit')
                ->with('error', 'Sesi pendaftaran sudah berakhir. Silakan mulai ulang proses aktivasi.');
        }

        $timestep = $this->totp->verify($secret, $request->input('code'));

        if ($timestep === null) {
            return redirect()->route('profile.edit')
                ->with('two_factor_setup', true)
                ->with('error', 'Kode yang dimasukkan salah atau sudah kedaluwarsa. Pastikan jam di HP Anda sudah otomatis/akurat, lalu coba kode terbaru.');
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_timestep' => $timestep,
        ])->save();

        $request->session()->forget(self::PENDING_SECRET);

        Log::info("2FA diaktifkan untuk user {$user->username}");

        // Recovery code hanya ditampilkan sekali, lewat flash session.
        return redirect()->route('profile.edit')
            ->with('two_factor_recovery_codes', $recoveryCodes)
            ->with('success', 'Autentikasi dua faktor berhasil diaktifkan. Mulai sekarang login Anda memakai aplikasi authenticator, bukan OTP WhatsApp.');
    }

    /**
     * Matikan 2FA. Password diminta ulang supaya sesi yang ditinggal terbuka
     * tidak bisa dipakai orang lain untuk menurunkan keamanan akun.
     */
    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->validatePassword($request);

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Autentikasi dua faktor memang belum aktif.');
        }

        $user->disableTwoFactor();
        $request->session()->forget(self::PENDING_SECRET);

        Log::info("2FA dinonaktifkan untuk user {$user->username}");

        return redirect()->route('profile.edit')
            ->with('success', 'Autentikasi dua faktor dinonaktifkan. Login Anda kembali memakai OTP WhatsApp.');
    }

    /**
     * Buat ulang recovery code (yang lama langsung hangus).
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->validatePassword($request);

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Autentikasi dua faktor belum aktif.');
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        return redirect()->route('profile.edit')
            ->with('two_factor_recovery_codes', $recoveryCodes)
            ->with('success', 'Recovery code baru berhasil dibuat. Kode lama sudah tidak berlaku.');
    }

    /**
     * @throws ValidationException
     */
    private function validatePassword(Request $request): void
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Password wajib diisi untuk konfirmasi.',
        ]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password yang dimasukkan salah.',
            ]);
        }
    }
}

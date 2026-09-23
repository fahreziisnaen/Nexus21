<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = \App\Models\User::where('username', $request->username)->first();

        if (! $user) {
            return back()->withInput()->with('error', 'Pengguna tidak ditemukan.');
        }

        // Password sudah benar, tapi user belum login sampai tahap kedua selesai.
        // Pilih metode verifikasi: authenticator (kalau user sudah mengaktifkannya)
        // atau OTP WhatsApp sebagai default.
        if ($user->hasTwoFactorEnabled()) {
            return $this->challengeWithAuthenticator($request, $user);
        }

        return $this->challengeWithWhatsappOtp($request, $user);
    }

    /**
     * Tahap kedua lewat aplikasi authenticator (TOTP).
     *
     * Tidak ada apa pun yang dikirim ke WhatsApp di jalur ini — kodenya dihasilkan
     * offline oleh aplikasi di HP user.
     */
    private function challengeWithAuthenticator(LoginRequest $request, \App\Models\User $user): RedirectResponse
    {
        // Bersihkan sisa state jalur WhatsApp, supaya tidak ada dua tantangan aktif
        // sekaligus kalau user sempat gagal login lewat jalur satunya.
        $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);

        $request->session()->put('auth_2fa_username', $user->username);
        $request->session()->put('auth_2fa_remember', $request->boolean('remember'));
        $request->session()->put('auth_2fa_expires_at', now()->addMinutes(5));
        $request->session()->put('auth_2fa_attempts', 0);

        return back()->with([
            'show_2fa' => true,
            'status' => 'Masukkan kode dari aplikasi authenticator Anda.',
        ]);
    }

    /**
     * Tahap kedua lewat OTP WhatsApp (default untuk user tanpa 2FA).
     */
    private function challengeWithWhatsappOtp(LoginRequest $request, \App\Models\User $user): RedirectResponse
    {
        $otp = \App\Http\Controllers\Auth\OtpController::sendOtp($user);

        if (! $otp) {
            return back()->withInput()->with('error', 'Gagal mengirim OTP ke WhatsApp Anda. Pastikan layanan WhatsApp Gateway aktif.');
        }

        $request->session()->forget(['auth_2fa_username', 'auth_2fa_remember', 'auth_2fa_expires_at', 'auth_2fa_attempts']);

        // Simpan data OTP sementara
        $request->session()->put('auth_otp', $otp);
        $request->session()->put('auth_username', $user->username);
        $request->session()->put('auth_remember', $request->boolean('remember'));
        $request->session()->put('auth_otp_expires_at', now()->addMinutes(5));
        $request->session()->put('auth_otp_attempts', 0);

        return back()->with([
            'show_otp' => true,
            'status' => 'Kode OTP telah dikirim ke WhatsApp Anda.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

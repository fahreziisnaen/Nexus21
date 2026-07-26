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

        // Send OTP via WhatsApp
        $otp = \App\Http\Controllers\Auth\OtpController::sendOtp($user);

        if (!$otp) {
            return back()->withInput()->with('error', 'Gagal mengirim OTP ke WhatsApp Anda. Pastikan layanan WhatsApp Gateway aktif.');
        }

        // Simpan data OTP sementara
        $request->session()->put('auth_otp', $otp);
        $request->session()->put('auth_username', $user->username);
        $request->session()->put('auth_remember', $request->boolean('remember'));
        $request->session()->put('auth_otp_expires_at', now()->addMinutes(5));
        $request->session()->put('auth_otp_attempts', 0);

        return back()->with([
            'show_otp' => true,
            'status' => "Kode OTP telah dikirim ke WhatsApp Anda."
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

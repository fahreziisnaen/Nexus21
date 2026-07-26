<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{


    /**
     * Handle an incoming OTP verification request.
     */
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return back()->with([
                'show_otp' => true,
            ])->withErrors($validator)->withInput();
        }

        $sessionOtp = $request->session()->get('auth_otp');
        $sessionUsername = $request->session()->get('auth_username');
        $remember = $request->session()->get('auth_remember', false);
        $expiresAt = $request->session()->get('auth_otp_expires_at');

        if (!$sessionOtp || !$sessionUsername) {
            return redirect()->route('login')->with('error', 'Sesi login telah berakhir. Silakan login kembali.');
        }

        if ($expiresAt && now()->isAfter($expiresAt)) {
            $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);
            return redirect()->route('login')->with('error', 'Waktu verifikasi OTP telah habis (5 menit). Silakan login kembali.');
        }

        if ($request->otp !== $sessionOtp) {
            $attempts = (int) $request->session()->get('auth_otp_attempts', 0) + 1;
            $request->session()->put('auth_otp_attempts', $attempts);

            if ($attempts >= 5) {
                $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);

                return redirect()->route('login')->with('error', 'Terlalu banyak percobaan kode OTP yang salah. Silakan login kembali.');
            }

            return back()->with([
                'show_otp' => true,
                'error' => 'Kode OTP yang dimasukkan salah.'
            ])->withInput();
        }

        // OTP is correct, find the user and log them in
        $user = User::where('username', $sessionUsername)->first();

        if (!$user) {
            $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_attempts']);
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan.');
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        // Clear OTP session variables
        $request->session()->forget(['auth_otp', 'auth_username', 'auth_remember', 'auth_otp_expires_at', 'auth_otp_attempts']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Send OTP via WhatsApp.
     */
    public static function sendOtp($user)
    {
        $otp = sprintf('%06d', random_int(100000, 999999));
        $phoneNumber = self::formatPhoneNumber($user->nomor_telepon);

        $message = "Halo {$user->nama_lengkap},\n\nKode OTP Anda untuk login ke NEXUS 21 adalah: *{$otp}*\n\nKode ini bersifat rahasia. Jangan berikan kode ini kepada siapapun.";

        Log::info("OTP generated for user {$user->username}");

        try {
            // Since Node.js runs on docker container named nexus_whatsapp on port 3000.
            // But we might be accessing it via docker network or localhost depending on environment.
            // Using logic: if in docker can use `nexus_whatsapp` or `localhost`.
            // The docker-compose uses `app` and `whatsapp` services.
            $whatsappHost = env('WHATSAPP_HOST', 'http://whatsapp:3000');

            $response = Http::withToken(env('WHATSAPP_SHARED_SECRET', ''))
                ->post("{$whatsappHost}/send-message", [
                    'id' => $phoneNumber,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                Log::error('Gagal mengirim OTP via WhatsApp: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error mengirim OTP: ' . $e->getMessage());
            return false;
        }

        return $otp;
    }

    /**
     * Format phone number for Baileys.
     * Expects format: 628xxx@c.us
     */
    private static function formatPhoneNumber($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // If starts with 0, replace with 62
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }

        return $number . '@c.us';
    }
}

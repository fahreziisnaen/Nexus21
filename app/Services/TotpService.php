<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * TOTP (RFC 6238) — kompatibel dengan Google Authenticator, Authy, Microsoft
 * Authenticator, dan aplikasi authenticator standar lainnya.
 *
 * Diimplementasikan langsung di project (bukan lewat package composer) karena
 * algoritmanya hanya HMAC-SHA1 + truncation standar, dan build Docker project
 * ini tidak selalu punya akses ke packagist.
 */
class TotpService
{
    /** Panjang satu periode kode, dalam detik. Standar Google Authenticator. */
    public const PERIOD = 30;

    /** Jumlah digit kode. */
    public const DIGITS = 6;

    /** Algoritma HMAC. SHA1 adalah default yang didukung semua authenticator. */
    private const ALGORITHM = 'sha1';

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Secret acak 160-bit (20 byte), sesuai rekomendasi RFC 4226 §4, dalam base32.
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Timestep (counter TOTP) untuk sebuah waktu unix.
     */
    public function timestepAt(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    /**
     * Hitung kode untuk satu timestep tertentu.
     */
    public function codeForTimestep(string $secret, int $timestep): string
    {
        $key = $this->base32Decode($secret);

        // Counter 8 byte big-endian (RFC 4226 §5.1).
        $hash = hash_hmac(self::ALGORITHM, pack('J', $timestep), $key, true);

        // Dynamic truncation (RFC 4226 §5.3).
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $binary % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verifikasi kode terhadap secret.
     *
     * Mengembalikan timestep yang cocok (bukan sekadar true) supaya pemanggil bisa
     * menyimpannya dan menolak kode yang sama dipakai ulang selama masih di dalam
     * window. Mengembalikan null kalau kode tidak cocok.
     *
     * @param  int  $window  Jumlah timestep toleransi ke depan & ke belakang, untuk
     *                       mengakomodasi jam HP yang bergeser sedikit dari jam server.
     */
    public function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== self::DIGITS || $secret === '') {
            return null;
        }

        $current = $this->timestepAt($timestamp);
        $match = null;

        // Sengaja tidak break saat ketemu: seluruh window selalu dievaluasi supaya
        // lama eksekusi tidak membocorkan timestep mana yang cocok.
        for ($offset = -$window; $offset <= $window; $offset++) {
            $timestep = $current + $offset;

            if (hash_equals($this->codeForTimestep($secret, $timestep), $code)) {
                $match = $timestep;
            }
        }

        return $match;
    }

    /**
     * Nama penerbit yang tampil di aplikasi authenticator user, mis. "NEXUS 21 (admin)".
     *
     * Skeleton Laravel masih memakai APP_NAME=Laravel dan .env.example project ini
     * belum diubah, jadi nilai itu diperlakukan sebagai "belum diisi" supaya user
     * tidak melihat entri bernama "Laravel" di HP-nya.
     *
     * Aman diubah kapan saja: issuer hanya label tampilan, bukan bagian dari
     * perhitungan kode, sehingga pendaftaran yang sudah ada tetap berfungsi.
     */
    public function issuer(): string
    {
        $name = config('app.name');

        return (! $name || $name === 'Laravel') ? 'NEXUS 21' : $name;
    }

    /**
     * URI otpauth:// yang di-encode jadi QR code untuk di-scan authenticator.
     */
    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        // Label mengikuti format "Issuer:account" (Key Uri Format Google Authenticator).
        $label = rawurlencode($issuer).':'.rawurlencode($accountName);

        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * Secret dipecah per 4 karakter supaya enak dibaca kalau user memasukkannya manual.
     */
    public function formatSecretForDisplay(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    /**
     * Recovery code sekali pakai, dipakai kalau HP/authenticator user hilang.
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->values()
            ->all();
    }

    private function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $output;
    }

    private function base32Decode(string $secret): string
    {
        // Padding '=' dan spasi (dari secret yang di-copy user) dibuang dulu.
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret) ?? '');

        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos(self::BASE32_ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        // Sisa bit yang tidak genap 8 diabaikan — itu padding dari proses encode.
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }
}

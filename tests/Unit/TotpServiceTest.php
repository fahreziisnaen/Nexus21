<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    /**
     * Secret dari test vector RFC 6238 Appendix B: ASCII "12345678901234567890"
     * dalam base32.
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService;
    }

    /**
     * Ini yang menjamin kode kita identik dengan yang dihasilkan Google
     * Authenticator — kalau test ini lolos, aplikasi authenticator apa pun yang
     * mengikuti standar akan cocok.
     *
     * Nilai harapan adalah 6 digit terakhir dari test vector 8 digit di RFC 6238.
     */
    public function test_matches_rfc_6238_test_vectors(): void
    {
        $vectors = [
            59 => '287082',
            1111111109 => '081804',
            1111111111 => '050471',
            1234567890 => '005924',
            2000000000 => '279037',
            20000000000 => '353130',
        ];

        foreach ($vectors as $timestamp => $expected) {
            $this->assertSame(
                $expected,
                $this->totp->codeForTimestep(self::RFC_SECRET, $this->totp->timestepAt($timestamp)),
                "Kode TOTP tidak cocok untuk T={$timestamp}"
            );
        }
    }

    public function test_verifies_the_current_code(): void
    {
        $now = 1700000000;
        $timestep = $this->totp->timestepAt($now);
        $code = $this->totp->codeForTimestep(self::RFC_SECRET, $timestep);

        $this->assertSame($timestep, $this->totp->verify(self::RFC_SECRET, $code, 1, $now));
    }

    /**
     * Jam HP user sering meleset sedikit dari jam server, jadi satu timestep
     * sebelum/sesudah harus tetap diterima.
     */
    public function test_accepts_codes_within_the_drift_window(): void
    {
        $now = 1700000000;
        $timestep = $this->totp->timestepAt($now);

        foreach ([-1, 0, 1] as $offset) {
            $code = $this->totp->codeForTimestep(self::RFC_SECRET, $timestep + $offset);

            $this->assertSame(
                $timestep + $offset,
                $this->totp->verify(self::RFC_SECRET, $code, 1, $now),
                "Offset {$offset} seharusnya diterima"
            );
        }
    }

    public function test_rejects_codes_outside_the_drift_window(): void
    {
        $now = 1700000000;
        $timestep = $this->totp->timestepAt($now);

        foreach ([-5, -2, 2, 5] as $offset) {
            $code = $this->totp->codeForTimestep(self::RFC_SECRET, $timestep + $offset);

            $this->assertNull(
                $this->totp->verify(self::RFC_SECRET, $code, 1, $now),
                "Offset {$offset} seharusnya ditolak"
            );
        }
    }

    public function test_rejects_malformed_codes(): void
    {
        $now = 1700000000;

        foreach (['', '12345', '1234567', 'abcdef', '000000x'] as $code) {
            $this->assertNull($this->totp->verify(self::RFC_SECRET, $code, 1, $now));
        }
    }

    /**
     * User sering menyalin kode dengan spasi ikut terbawa.
     */
    public function test_normalises_spacing_in_submitted_codes(): void
    {
        $now = 1700000000;
        $timestep = $this->totp->timestepAt($now);
        $code = $this->totp->codeForTimestep(self::RFC_SECRET, $timestep);
        $spaced = substr($code, 0, 3).' '.substr($code, 3);

        $this->assertSame($timestep, $this->totp->verify(self::RFC_SECRET, $spaced, 1, $now));
    }

    public function test_generates_valid_random_base32_secrets(): void
    {
        $first = $this->totp->generateSecret();
        $second = $this->totp->generateSecret();

        // 20 byte → 32 karakter base32.
        $this->assertSame(32, strlen($first));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $first);
        $this->assertNotSame($first, $second);
    }

    /**
     * Secret yang dibuat harus bisa dipakai bolak-balik oleh encoder & decoder
     * kita sendiri, bukan hanya secret dari RFC.
     */
    public function test_generated_secrets_round_trip_through_verification(): void
    {
        $secret = $this->totp->generateSecret();
        $now = time();
        $timestep = $this->totp->timestepAt($now);
        $code = $this->totp->codeForTimestep($secret, $timestep);

        $this->assertSame($timestep, $this->totp->verify($secret, $code, 1, $now));
    }

    public function test_builds_a_provisioning_uri_google_authenticator_understands(): void
    {
        $uri = $this->totp->provisioningUri(self::RFC_SECRET, 'admin', 'NEXUS 21');

        $this->assertStringStartsWith('otpauth://totp/NEXUS%2021:admin?', $uri);
        $this->assertStringContainsString('secret='.self::RFC_SECRET, $uri);
        $this->assertStringContainsString('issuer=NEXUS%2021', $uri);
        $this->assertStringContainsString('algorithm=SHA1', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }

    public function test_generates_distinct_recovery_codes(): void
    {
        $codes = $this->totp->generateRecoveryCodes();

        $this->assertCount(8, $codes);
        $this->assertSame($codes, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}-[A-Z0-9]{5}$/', $code);
        }
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'nomor_telepon',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            // Secret & recovery code adalah kredensial: disimpan terenkripsi di DB
            // supaya dump database saja tidak cukup untuk membuat kode TOTP yang valid.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->username;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Apakah user ini memakai authenticator (TOTP) sebagai metode verifikasi login.
     *
     * Secret yang sudah dibuat tapi belum dikonfirmasi tidak dihitung aktif, supaya
     * user yang gagal menyelesaikan proses scan QR tetap bisa login lewat OTP WhatsApp.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Matikan 2FA dan bersihkan seluruh state-nya (kembali ke OTP WhatsApp).
     */
    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestep' => null,
        ])->save();
    }

    /**
     * Pakai satu recovery code (sekali pakai — langsung dicoret dari daftar).
     * Mengembalikan false kalau kode tidak ada di daftar.
     */
    public function consumeRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(trim($code));

        $matched = null;
        foreach ($codes as $stored) {
            if (hash_equals(strtoupper($stored), $normalized)) {
                $matched = $stored;
            }
        }

        if ($matched === null) {
            return false;
        }

        $this->forceFill([
            'two_factor_recovery_codes' => array_values(array_filter(
                $codes,
                fn ($stored) => $stored !== $matched
            )),
        ])->save();

        return true;
    }

    /**
     * Get the kelas that the user (walikelas) manages.
     */
    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'username', 'username');
    }
}

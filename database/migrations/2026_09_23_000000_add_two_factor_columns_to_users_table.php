<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Secret & recovery code disimpan terenkripsi (cast 'encrypted' di model User),
            // jadi kolomnya text bukan string — ciphertext Laravel jauh lebih panjang
            // daripada secret base32 aslinya.
            $table->text('two_factor_secret')->nullable()->after('role');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // 2FA baru dianggap aktif kalau kolom ini terisi, yaitu setelah user berhasil
            // memasukkan satu kode dari authenticator-nya. Secret yang sudah dibuat tapi
            // belum dikonfirmasi tidak mematikan OTP WhatsApp, supaya user tidak terkunci
            // kalau proses scan QR gagal di tengah jalan.
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            // Timestep TOTP terakhir yang berhasil dipakai. Dipakai untuk menolak kode yang
            // sama dipakai dua kali (replay) selama kode itu masih berada di dalam window.
            $table->unsignedBigInteger('two_factor_last_timestep')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_last_timestep',
            ]);
        });
    }
};

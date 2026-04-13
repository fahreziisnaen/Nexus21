<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change jenis_pelanggaran column from ENUM to VARCHAR(255)
        // so it can hold any dynamically-created violation type name.
        // Existing snapshot data remains intact because the old enum values
        // are valid varchar strings.
        DB::statement("ALTER TABLE pelanggaran MODIFY COLUMN jenis_pelanggaran VARCHAR(255) NOT NULL");
    }

    public function down(): void
    {
        // Revert to the original four-value ENUM
        DB::statement("ALTER TABLE pelanggaran MODIFY COLUMN jenis_pelanggaran ENUM('Terlambat','Seragam Tidak Lengkap','Baju Tidak Dimasukkan','Mewarnai Rambut') NOT NULL");
    }
};

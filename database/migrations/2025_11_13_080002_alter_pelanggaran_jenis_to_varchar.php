<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change jenis_pelanggaran column from ENUM to VARCHAR(255)
        // so it can hold any dynamically-created violation type name.
        // Existing snapshot data remains intact because the old enum values
        // are valid varchar strings.
        //
        // "ALTER TABLE ... MODIFY COLUMN" adalah sintaks khusus MySQL. Driver lain
        // (mis. sqlite yang dipakai test suite) menolaknya, jadi di luar MySQL
        // perubahan kolom dilakukan lewat schema builder Laravel.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pelanggaran MODIFY COLUMN jenis_pelanggaran VARCHAR(255) NOT NULL');

            return;
        }

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->string('jenis_pelanggaran', 255)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Revert to the original four-value ENUM
        $values = [
            'Terlambat',
            'Seragam Tidak Lengkap',
            'Baju Tidak Dimasukkan',
            'Mewarnai Rambut',
        ];

        if (DB::getDriverName() === 'mysql') {
            $list = implode(',', array_map(fn ($value) => "'".$value."'", $values));
            DB::statement("ALTER TABLE pelanggaran MODIFY COLUMN jenis_pelanggaran ENUM({$list}) NOT NULL");

            return;
        }

        Schema::table('pelanggaran', function (Blueprint $table) use ($values) {
            $table->enum('jenis_pelanggaran', $values)->nullable(false)->change();
        });
    }
};

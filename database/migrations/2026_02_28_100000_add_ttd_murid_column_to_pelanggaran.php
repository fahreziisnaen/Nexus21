<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('pelanggaran', 'ttd_murid')) {
            return;
        }

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->string('ttd_murid')->nullable()->after('bukti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropColumn('ttd_murid');
        });
    }
};

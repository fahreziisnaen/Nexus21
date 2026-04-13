<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggaran', function (Blueprint $table) {
            // e.g. "2024/2025-1" = Semester 1, "2024/2025-2" = Semester 2
            $table->string('semester', 20)->nullable()->after('bukti');
            // True = pelanggaran Berat, dikecualikan dari reset otomatis
            $table->boolean('is_excluded_from_reset')->default(false)->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropColumn(['semester', 'is_excluded_from_reset']);
        });
    }
};

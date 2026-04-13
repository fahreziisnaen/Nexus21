<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggaran', function (Blueprint $table) {
            $table->id('id');
            $table->string('NIS')->nullable(); // Snapshot NIS
            $table->string('nama_murid')->nullable(); // Snapshot nama murid
            $table->string('gender')->nullable(); // Snapshot gender
            $table->string('kelas')->nullable(); // Snapshot kelas
            $table->string('username')->nullable(); // Snapshot walikelas/pencatat
            $table->enum('jenis_pelanggaran', [
                'Terlambat',
                'Seragam Tidak Lengkap',
                'Baju Tidak Dimasukkan',
                'Mewarnai Rambut',
            ]);
            $table->integer('poin');
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->string('bukti')->nullable();
            $table->timestamps();

            $table->foreign('username')->references('username')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggaran');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggaran extends Model
{
    use HasFactory;

    protected $table = "pelanggaran";

    protected $fillable = [
        "NIS",
        "nama_murid",
        "gender",
        "kelas",
        "username",
        "jenis_pelanggaran",
        "poin",
        "tanggal",
        "keterangan",
        "bukti",
        "ttd_murid",
        "semester",
        "is_excluded_from_reset",
    ];

    protected $casts = [
        "tanggal"                => "date",
        "poin"                   => "integer",
        "is_excluded_from_reset" => "boolean",
    ];

    /**
     * Get the murid that owns the pelanggaran.
     */
    public function murid()
    {
        return $this->belongsTo(Murid::class, "NIS", "NIS");
    }

    /**
     * Get the user (pencatat/walikelas) that recorded the pelanggaran.
     */
    public function pencatat()
    {
        return $this->belongsTo(User::class, "username", "username");
    }

    /**
     * Get the JenisPelanggaran record for this violation (matched by snapshot name).
     */
    public function jenisPelanggaranRelation()
    {
        return $this->belongsTo(
            JenisPelanggaran::class,
            "jenis_pelanggaran",
            "nama",
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisPelanggaran extends Model
{
    use HasFactory;

    protected $table = 'jenis_pelanggaran';

    protected $fillable = [
        'nama',
        'deskripsi',
        'poin',
        'kategori',
    ];

    protected $casts = [
        'poin'     => 'integer',
        'kategori' => 'string',
    ];

    /**
     * Get all pelanggaran records that use this jenis.
     * Matched by snapshot nama string.
     */
    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class, 'jenis_pelanggaran', 'nama');
    }

    /**
     * Count how many pelanggaran records use this jenis.
     */
    public function getPelanggaranCountAttribute(): int
    {
        return Pelanggaran::where('jenis_pelanggaran', $this->nama)->count();
    }

    /**
     * Check if this jenis is currently in use by any pelanggaran record.
     */
    public function isInUse(): bool
    {
        return Pelanggaran::where('jenis_pelanggaran', $this->nama)->exists();
    }
}

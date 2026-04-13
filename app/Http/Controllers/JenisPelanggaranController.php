<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisPelanggaranController extends Controller
{
    /**
     * Only Admin and TATIB may manage violation types.
     */
    private function checkAccess()
    {
        $role = auth()->user()?->role;
        if ($role === 'Walikelas') {
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }
        return null;
    }

    /**
     * Display a listing of all jenis pelanggaran.
     */
    public function index()
    {
        if ($redirect = $this->checkAccess()) return $redirect;

        $jenisList = JenisPelanggaran::orderBy('nama')->paginate(10);

        // Append usage_count to each item without breaking the paginator
        $jenisList->getCollection()->transform(function ($jenis) {
            $jenis->usage_count = \App\Models\Pelanggaran::where('jenis_pelanggaran', $jenis->nama)->count();
            return $jenis;
        });

        return view('jenis-pelanggaran.index', compact('jenisList'));
    }

    /**
     * Show the form for creating a new jenis pelanggaran.
     */
    public function create()
    {
        if ($redirect = $this->checkAccess()) return $redirect;

        return view('jenis-pelanggaran.create');
    }

    /**
     * Store a newly created jenis pelanggaran.
     */
    public function store(Request $request)
    {
        if ($redirect = $this->checkAccess()) return $redirect;

        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:100', 'unique:jenis_pelanggaran,nama'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'poin'     => ['required', 'integer', 'min:1', 'max:1000'],
            'kategori' => ['nullable', 'string', 'in:Ringan,Sedang,Berat,Sangat Berat'],
        ], [
            'nama.required' => 'Nama jenis pelanggaran wajib diisi.',
            'nama.unique'   => 'Jenis pelanggaran dengan nama ini sudah ada.',
            'nama.max'      => 'Nama jenis pelanggaran maksimal 100 karakter.',
            'poin.required' => 'Poin wajib diisi.',
            'poin.integer'  => 'Poin harus berupa angka.',
            'poin.min'      => 'Poin minimal adalah 1.',
            'poin.max'      => 'Poin maksimal adalah 1000.',
            'kategori.in'   => 'Kategori harus salah satu dari: Ringan, Sedang, Berat, Sangat Berat.',
        ]);

        // Default kategori to Ringan if not provided
        $validated['kategori'] = $validated['kategori'] ?? 'Ringan';

        JenisPelanggaran::create($validated);

        return redirect()->route('jenis-pelanggaran.index')
            ->with('success', "Jenis pelanggaran \"{$validated['nama']}\" berhasil ditambahkan.");
    }

    /**
     * Show the form for editing a jenis pelanggaran.
     */
    public function edit(JenisPelanggaran $jenisPelanggaran)
    {
        if ($redirect = $this->checkAccess()) return $redirect;

        $usageCount = \App\Models\Pelanggaran::where('jenis_pelanggaran', $jenisPelanggaran->nama)->count();

        return view('jenis-pelanggaran.edit', compact('jenisPelanggaran', 'usageCount'));
    }

    /**
     * Update the specified jenis pelanggaran.
     * If the name changes and it is already in use, we update the snapshot strings too
     * so historical records stay consistent.
     */
    public function update(Request $request, JenisPelanggaran $jenisPelanggaran)
    {
        if ($redirect = $this->checkAccess()) return $redirect;

        $validated = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('jenis_pelanggaran', 'nama')->ignore($jenisPelanggaran->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'poin'      => ['required', 'integer', 'min:1', 'max:1000'],
            'kategori'  => ['nullable', 'string', 'in:Ringan,Sedang,Berat,Sangat Berat'],
        ], [
            'nama.required' => 'Nama jenis pelanggaran wajib diisi.',
            'nama.unique'   => 'Jenis pelanggaran dengan nama ini sudah ada.',
            'nama.max'      => 'Nama jenis pelanggaran maksimal 100 karakter.',
            'poin.required' => 'Poin wajib diisi.',
            'poin.integer'  => 'Poin harus berupa angka.',
            'poin.min'      => 'Poin minimal adalah 1.',
            'poin.max'      => 'Poin maksimal adalah 1000.',
            'kategori.in'   => 'Kategori harus salah satu dari: Ringan, Sedang, Berat, Sangat Berat.',
        ]);

        $validated['kategori'] = $validated['kategori'] ?? 'Ringan';

        $oldNama = $jenisPelanggaran->nama;
        $newNama = $validated['nama'];
        $newPoin = (int) $validated['poin'];

        // If the name changed, cascade-update existing pelanggaran snapshots
        if ($oldNama !== $newNama) {
            \App\Models\Pelanggaran::where('jenis_pelanggaran', $oldNama)
                ->update(['jenis_pelanggaran' => $newNama]);
        }

        // If the poin changed, cascade-update existing pelanggaran records too
        // so the stored poin reflects the corrected value on historical records.
        // (Only update records whose poin still matches the OLD definition to avoid
        //  overwriting records that were manually adjusted.)
        if ((int) $jenisPelanggaran->poin !== $newPoin) {
            \App\Models\Pelanggaran::where('jenis_pelanggaran', $newNama)
                ->where('poin', $jenisPelanggaran->poin)
                ->update(['poin' => $newPoin]);
        }

        $jenisPelanggaran->update($validated);

        return redirect()->route('jenis-pelanggaran.index')
            ->with('success', "Jenis pelanggaran berhasil diperbarui.");
    }

    /**
     * Remove the specified jenis pelanggaran.
     * Blocked if any pelanggaran record still references it.
     */
    public function destroy(JenisPelanggaran $jenisPelanggaran)
    {
        if ($redirect = $this->checkAccess()) return $redirect;

        if ($jenisPelanggaran->isInUse()) {
            $count = \App\Models\Pelanggaran::where('jenis_pelanggaran', $jenisPelanggaran->nama)->count();
            return redirect()->route('jenis-pelanggaran.index')
                ->with('error', "Jenis pelanggaran \"{$jenisPelanggaran->nama}\" tidak dapat dihapus karena masih digunakan oleh {$count} data pelanggaran.");
        }

        $nama = $jenisPelanggaran->nama;
        $jenisPelanggaran->delete();

        return redirect()->route('jenis-pelanggaran.index')
            ->with('success', "Jenis pelanggaran \"{$nama}\" berhasil dihapus.");
    }
}

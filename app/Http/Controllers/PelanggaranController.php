<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggaran;
use App\Models\Pelanggaran;
use App\Models\Murid;
use App\Helpers\SemesterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PelanggaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $tanggal          = $request->input('tanggal');
        $namaMurid        = $request->input('nama_murid');
        $jenisPelanggaran = $request->input('jenis_pelanggaran');
        
        $semesterAktif    = SemesterHelper::current();
        // Split semester aktif for defaults
        $parsedAktif  = SemesterHelper::parse($semesterAktif);
        $defaultTahun = $parsedAktif['tahun_ajaran'];
        $defaultTipe  = $parsedAktif['semester'] === 1 ? 'Semester Ganjil' : 'Semester Genap';

        $tahunPembelajaran = $request->input('tahun_pembelajaran', $defaultTahun);
        $semesterTipe      = $request->input('semester', $defaultTipe);

        $query = Pelanggaran::with('pencatat');

        // Walikelas: filter berdasarkan kelas yang dia pegang (snapshot)
        if ($user && $user->role === 'Walikelas') {
            $kelasIds = \App\Models\Kelas::where('username', $user->username)->pluck('kelas');
            $query->whereIn('kelas', $kelasIds);
        }

        // Filter semester (Custom 'semua' filter removed to enforce strict period filtering as requested, or keep it if needed)
        if ($tahunPembelajaran && $semesterTipe) {
            $semNum = $semesterTipe === 'Semester Ganjil' ? 1 : 2;
            $assembledSemester = $tahunPembelajaran . '-' . $semNum;
            $query->where('semester', $assembledSemester);
        }

        if ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        }

        if ($namaMurid) {
            $query->where('nama_murid', 'like', '%' . $namaMurid . '%');
        }

        if ($jenisPelanggaran) {
            $query->where('jenis_pelanggaran', $jenisPelanggaran);
        }

        $pelanggaran = $query
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $violationTypes = JenisPelanggaran::orderBy('nama')->get();
        $semesterList   = SemesterHelper::list();

        // Extract unique Tahun Pembelajaran from semester list
        $tahunList = [];
        foreach ($semesterList as $sem) {
            $parsed = SemesterHelper::parse($sem);
            $tahun = $parsed['tahun_ajaran'];
            if ($tahun && !in_array($tahun, $tahunList)) {
                $tahunList[] = $tahun;
            }
        }
        rsort($tahunList); // Descending order

        return view('pelanggaran.index', compact(
            'pelanggaran',
            'tanggal',
            'namaMurid',
            'jenisPelanggaran',
            'violationTypes',
            'tahunList',
            'tahunPembelajaran',
            'semesterTipe'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk menambahkan data pelanggaran.",
                );
        }

        $murids = Murid::where("status", "Aktif")
            ->orderBy("nama_lengkap")
            ->get();
        $violationTypes = JenisPelanggaran::orderBy("nama")->get();

        return view("pelanggaran.create", compact("murids", "violationTypes"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk menambahkan data pelanggaran.",
                );
        }

        $validated = $request->validate([
            "NIS" => ["required", "exists:murid,NIS"],
            "jenis_pelanggaran" => [
                "required",
                "exists:jenis_pelanggaran,nama",
            ],
            "tanggal" => ["required", "date"],
            "keterangan" => ["nullable", "string", "max:1000"],
            "bukti" => ["nullable", "image", "max:2048"],
            "ttd_murid" => ["nullable", "string"],
        ]);

        // Auto-stamp semester berdasarkan tanggal hari ini
        $validated['semester'] = SemesterHelper::current();

        // Auto-calculate points from the jenis_pelanggaran table
        $jenis = JenisPelanggaran::where('nama', $validated['jenis_pelanggaran'])->firstOrFail();
        $validated['poin'] = $jenis->poin;

        // Set the username of the current logged-in user as the recorder
        $validated["username"] = auth()->user()->username;

        // Build snapshot
        $murid = Murid::where("NIS", $validated["NIS"])->first();
        if ($murid) {
            $validated["nama_murid"] = $murid->nama_lengkap;
            $validated["gender"] = $murid->gender;
            $validated["kelas"] = $murid->kelas;
        }

        if ($request->hasFile("bukti")) {
            $safeName = preg_replace(
                "/[^a-zA-Z0-9]/",
                "_",
                $validated["nama_murid"] ?? "murid",
            );
            $date = \Carbon\Carbon::parse($validated["tanggal"])->format(
                "Y-m-d",
            );
            $extension = $request->file("bukti")->getClientOriginalExtension();
            $filename = "{$safeName}_{$date}.{$extension}";

            $validated["bukti"] = $request
                ->file("bukti")
                ->storeAs("bukti-pelanggaran", $filename, "public");
        }

        $ttdSaved = false;
        if (! empty($validated["ttd_murid"])) {
            $image_parts = explode(";base64,", $validated["ttd_murid"]);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1], true);
                if ($image_base64 !== false && strlen($image_base64) > 0 && getimagesizefromstring($image_base64) !== false) {
                    $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? "murid");
                    $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                    // Extension is always .png — never derived from attacker-supplied input.
                    $filename = "{$safeName}_{$date}_ttd.png";

                    // Simpan ke storage/app/public/ttd-pelanggaran/
                    $storagePath = storage_path('app/public/ttd-pelanggaran');
                    if (!file_exists($storagePath)) {
                        mkdir($storagePath, 0755, true);
                    }

                    $fullPath = $storagePath . '/' . $filename;
                    $saved = file_put_contents($fullPath, $image_base64);

                    if ($saved !== false) {
                        $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                        $ttdSaved = true;
                    }
                } else {
                    unset($validated["ttd_murid"]);
                }
            } else {
                unset($validated["ttd_murid"]);
            }
        } else {
            unset($validated["ttd_murid"]);
        }

        $pelanggaran = Pelanggaran::create($validated);

        // Notify Walikelas via WhatsApp
        if (!empty($validated['kelas'])) {
            $kelas = \App\Models\Kelas::where('kelas', $validated['kelas'])->with('walikelas')->first();
            if ($kelas && $kelas->walikelas && $kelas->walikelas->nomor_telepon) {
                $this->notifyWalikelas($pelanggaran, $kelas->walikelas);
            }
        }

        $message = $ttdSaved
            ? "Data pelanggaran berhasil ditambahkan (dengan tanda tangan siswa)."
            : "Data pelanggaran berhasil ditambahkan.";

        return redirect()
            ->route("pelanggaran.index")
            ->with("success", $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pelanggaran $pelanggaran)
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk melihat detail data pelanggaran.",
                );
        }

        return view("pelanggaran.show", compact("pelanggaran"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pelanggaran $pelanggaran)
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk mengubah data pelanggaran.",
                );
        }

        $murids = Murid::where("status", "Aktif")
            ->orderBy("nama_lengkap")
            ->get();
        $violationTypes = JenisPelanggaran::orderBy("nama")->get();

        return view(
            "pelanggaran.edit",
            compact("pelanggaran", "murids", "violationTypes"),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pelanggaran $pelanggaran)
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk mengubah data pelanggaran.",
                );
        }

        $validated = $request->validate([
            "NIS" => ["required", "exists:murid,NIS"],
            "jenis_pelanggaran" => [
                "required",
                "exists:jenis_pelanggaran,nama",
            ],
            "tanggal" => ["required", "date"],
            "keterangan" => ["nullable", "string", "max:1000"],
            "bukti" => ["nullable", "image", "max:2048"],
            "ttd_murid" => ["nullable", "string"],
        ]);

        // Always recalculate points from DB when jenis changes
        $jenis = JenisPelanggaran::where(
            "nama",
            $validated["jenis_pelanggaran"],
        )->firstOrFail();
        $validated["poin"] = $jenis->poin;

        // Always use current logged-in user as recorder
        $validated["username"] = auth()->user()->username;

        // Refresh snapshot only when NIS changes, or snapshot is incomplete
        if ($pelanggaran->NIS != $validated["NIS"]) {
            $murid = Murid::where("NIS", $validated["NIS"])->first();
            if ($murid) {
                $validated["nama_murid"] = $murid->nama_lengkap;
                $validated["gender"] = $murid->gender;
                $validated["kelas"] = $murid->kelas;

                if ($murid->kelas) {
                    $kelasData = \App\Models\Kelas::where(
                        "kelas",
                        $murid->kelas,
                    )->first();
                    $validated["username"] =
                        $kelasData && $kelasData->username
                            ? $kelasData->username
                            : null;
                } else {
                    $validated["username"] = null;
                }
            }
        } elseif (!$pelanggaran->nama_murid || !$pelanggaran->kelas) {
            $murid = Murid::where("NIS", $validated["NIS"])->first();
            if ($murid) {
                if (!$pelanggaran->nama_murid) {
                    $validated["nama_murid"] = $murid->nama_lengkap;
                }
                if (!$pelanggaran->gender) {
                    $validated["gender"] = $murid->gender;
                }
                if (!$pelanggaran->kelas) {
                    $validated["kelas"] = $murid->kelas;
                    if ($murid->kelas) {
                        $kelasData = \App\Models\Kelas::where(
                            "kelas",
                            $murid->kelas,
                        )->first();
                        $validated["username"] =
                            $kelasData && $kelasData->username
                                ? $kelasData->username
                                : null;
                    }
                }
            }
        } else {
            // NIS unchanged and snapshot intact — keep existing snapshot
            unset(
                $validated["nama_murid"],
                $validated["gender"],
                $validated["kelas"],
                $validated["username"],
            );
        }

        if ($request->hasFile("bukti")) {
            if ($pelanggaran->bukti) {
                Storage::disk("public")->delete($pelanggaran->bukti);
            }
            $safeName = preg_replace(
                "/[^a-zA-Z0-9]/",
                "_",
                $pelanggaran->nama_murid ?? "murid",
            );
            $date = \Carbon\Carbon::parse($validated["tanggal"])->format(
                "Y-m-d",
            );
            $extension = $request->file("bukti")->getClientOriginalExtension();
            $filename = "{$safeName}_{$date}.{$extension}";

            $validated["bukti"] = $request
                ->file("bukti")
                ->storeAs("bukti-pelanggaran", $filename, "public");
        }

        // Handle signature update
        if (isset($validated["ttd_murid"])) {
            if (!empty($validated["ttd_murid"])) {
                // Check if it's new base64 data (contains ";base64,")
                if (strpos($validated["ttd_murid"], ';base64,') !== false) {
                    $image_parts = explode(";base64,", $validated["ttd_murid"]);
                    $image_base64 = count($image_parts) === 2
                        ? base64_decode($image_parts[1], true)
                        : false;

                    if ($image_base64 !== false && strlen($image_base64) > 0 && getimagesizefromstring($image_base64) !== false) {
                        // Hapus file lama jika ada
                        $this->safeDeleteTtdMurid($pelanggaran->ttd_murid);

                        $safeName = preg_replace("/[^a-zA-Z0-9]/", "_", $validated["nama_murid"] ?? $pelanggaran->nama_murid ?? "murid");
                        $date = \Carbon\Carbon::parse($validated["tanggal"])->format("Y-m-d_H-i-s");
                        // Extension is always .png — never derived from attacker-supplied input.
                        $filename = "{$safeName}_{$date}_ttd.png";

                        // Simpan ke storage/app/public/ttd-pelanggaran/
                        $storagePath = storage_path('app/public/ttd-pelanggaran');
                        if (!file_exists($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $fullPath = $storagePath . '/' . $filename;
                        if (file_put_contents($fullPath, $image_base64) !== false) {
                            $validated["ttd_murid"] = "ttd-pelanggaran/" . $filename;
                        } else {
                            unset($validated["ttd_murid"]);
                        }
                    } else {
                        // Base64 tidak valid, pertahankan nilai lama
                        unset($validated["ttd_murid"]);
                    }
                } elseif ($validated["ttd_murid"] === "CLEAR") {
                    // Hapus file lama jika ada
                    $this->safeDeleteTtdMurid($pelanggaran->ttd_murid);
                    $validated["ttd_murid"] = null;
                } else {
                    // Ini adalah path file yang sudah ada, dikirim balik oleh form edit lewat
                    // hidden input. Klien hanya boleh mengirim balik nilai yang sudah tersimpan
                    // di record ini — nilai lain (termasuk path file valid milik record lain)
                    // ditolak, supaya request PUT tidak bisa dipakai untuk memicu penghapusan
                    // file record lain lewat request "CLEAR" berikutnya.
                    if ($validated["ttd_murid"] !== $pelanggaran->ttd_murid) {
                        unset($validated["ttd_murid"]);
                    }
                }
            } else {
                // Nilai kosong, hapus dari array agar tidak menimpa nilai lama
                unset($validated["ttd_murid"]);
            }
        }
        // Jika ttd_murid tidak ada di $validated sama sekali, pertahankan nilai lama

        $pelanggaran->update($validated);

        return redirect()
            ->route("pelanggaran.index")
            ->with("success", "Data pelanggaran berhasil diperbarui.");
    }

    /**
     * Delete a stored ttd_murid file, refusing anything that isn't a plain
     * filename inside the ttd-pelanggaran directory (hyphens allowed for the
     * "{name}_{Y-m-d_H-i-s}_ttd.png" timestamp format Task 1 generates; .jpg
     * and .jpeg are also accepted to allow cleanup of legacy pre-Task-1
     * rows). This is the guard that stands between the DB value and the
     * filesystem at delete time — it does not trust the write-time check
     * alone, in case a value ever reaches the DB some other way (e.g. a row
     * written before validation existed).
     */
    private function safeDeleteTtdMurid(?string $relativePath): void
    {
        if (! $relativePath || ! preg_match('/^ttd-pelanggaran\/[A-Za-z0-9_-]+\.(?:png|jpe?g)\z/', $relativePath)) {
            return;
        }

        $fullPath = storage_path('app/public/' . $relativePath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pelanggaran $pelanggaran)
    {
        if (auth()->user()->role === "Walikelas") {
            return redirect()
                ->route("pelanggaran.index")
                ->with(
                    "error",
                    "Anda tidak memiliki izin untuk menghapus data pelanggaran.",
                );
        }

        if ($pelanggaran->bukti) {
            Storage::disk("public")->delete($pelanggaran->bukti);
        }

        if ($pelanggaran->ttd_murid) {
            Storage::disk("public")->delete($pelanggaran->ttd_murid);
        }

        $pelanggaran->delete();

        return redirect()
            ->route("pelanggaran.index")
            ->with("success", "Data pelanggaran berhasil dihapus.");
    }

    /**
     * Get murid data for AJAX (reused on create/edit forms).
     */
    public function getMurid(string $nis)
    {
        $murid = Murid::where('NIS', $nis)->first();

        if ($murid) {
            $user = auth()->user();
            if ($user && $user->role === 'Walikelas') {
                $ownKelas = \App\Models\Kelas::where('username', $user->username)->pluck('kelas');
                if (! $murid->kelas || ! $ownKelas->contains($murid->kelas)) {
                    return response()->json(['success' => false]);
                }
            }

            $semesterAktif     = SemesterHelper::current();
            $totalPoin         = Pelanggaran::where('NIS', $nis)->sum('poin');
            $totalPoinSemester = Pelanggaran::where('NIS', $nis)
                ->where('semester', $semesterAktif)
                ->sum('poin');

            return response()->json([
                'success'             => true,
                'gender'              => $murid->gender,
                'kelas'               => $murid->kelas,
                'nama_lengkap'        => $murid->nama_lengkap,
                'total_poin'          => $totalPoin,
                'total_poin_semester' => $totalPoinSemester,
                'semester_aktif'      => $semesterAktif,
                'semester_label'      => SemesterHelper::label($semesterAktif),
            ]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * Show student violation summary (points leaderboard).
     */
    public function rekap(Request $request)
    {
        $user           = auth()->user();
        $kelas          = $request->input('kelas');
        $nama           = $request->input('nama');
        
        $semesterAktif  = SemesterHelper::current();
        // Split semester aktif for defaults
        $parsedAktif  = SemesterHelper::parse($semesterAktif);
        $defaultTahun = $parsedAktif['tahun_ajaran'];
        $defaultTipe  = $parsedAktif['semester'] === 1 ? 'Semester Ganjil' : 'Semester Genap';

        $tahunPembelajaran = $request->input('tahun_pembelajaran', $defaultTahun);
        $semesterTipe      = $request->input('semester', $defaultTipe);

        // Build the base query
        $query = Murid::where('status', 'Aktif');

        if ($user && $user->role === 'Walikelas') {
            $kelasIds = \App\Models\Kelas::where('username', $user->username)->pluck('kelas');
            $query->whereIn('kelas', $kelasIds);
        }

        if ($kelas) {
            $query->where('kelas', $kelas);
        }

        if ($nama) {
            $query->where('nama_lengkap', 'like', '%' . $nama . '%');
        }

        // Scope poin and count ke semester yang dipilih
        $query->withSum(
            ['pelanggaran' => fn($q) => $q->where('semester', $semesterFilter)],
            'poin'
        )->withCount(
            ['pelanggaran' => fn($q) => $q->where('semester', $semesterFilter)]
        );

        $students = $query
            ->orderByDesc('pelanggaran_sum_poin')
            ->paginate(10)
            ->withQueryString();

        // For filter dropdowns
        if ($user && $user->role === 'Walikelas') {
            $kelasList = \App\Models\Kelas::where('username', $user->username)->orderByNatural();
        } else {
            $kelasList = \App\Models\Kelas::orderByNatural();
        }

        $semesterList = SemesterHelper::list();
        $tahunList = [];
        foreach ($semesterList as $sem) {
            $parsed = SemesterHelper::parse($sem);
            $tahun = $parsed['tahun_ajaran'];
            if ($tahun && !in_array($tahun, $tahunList)) {
                $tahunList[] = $tahun;
            }
        }
        rsort($tahunList);

        return view('pelanggaran.rekap', compact(
            'students',
            'kelas',
            'nama',
            'kelasList',
            'semesterAktif',
            'tahunList',
            'tahunPembelajaran',
            'semesterTipe'
        ));
    }

    /**
     * Notify Walikelas via WhatsApp when a violation is recorded.
     */
    private function notifyWalikelas(Pelanggaran $pelanggaran, \App\Models\User $walikelas)
    {
        try {
            $phoneNumber = preg_replace('/[^0-9]/', '', $walikelas->nomor_telepon);
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '62' . substr($phoneNumber, 1);
            }
            $phoneNumber .= '@c.us';

            $tanggal = \Carbon\Carbon::parse($pelanggaran->tanggal)->format('d/m/Y');
            $waktu = $pelanggaran->created_at ? $pelanggaran->created_at->format('H:i \W\I\B') : now()->format('H:i \W\I\B');

            $message = "Halo Bapak/Ibu *{$walikelas->nama_lengkap}*,\n\n"
                . "Ijin melaporkan bahwa ada pelanggaran yang dilakukan siswa/i atas nama:\n\n"
                . "📄 *Detail Pelanggaran*\n"
                . "👤 *Nama*: {$pelanggaran->nama_murid}\n"
                . "🏷️ *NIS*: {$pelanggaran->NIS}\n"
                . "🏫 *Kelas*: {$pelanggaran->kelas}\n"
                . "⚠️ *Pelanggaran*: {$pelanggaran->jenis_pelanggaran}\n"
                . "🔴 *Poin*: {$pelanggaran->poin}\n"
                . "📅 *Tanggal*: {$tanggal}\n"
                . "⏰ *Waktu*: {$waktu}\n\n"
                . "Demikian yang dapat kami sampaikan, Terimakasih";

            $whatsappHost = env('WHATSAPP_HOST', 'http://whatsapp:3000');

            \Illuminate\Support\Facades\Http::timeout(5)
                ->withToken(env('WHATSAPP_SHARED_SECRET', ''))
                ->post("{$whatsappHost}/send-message", [
                    'id' => $phoneNumber,
                    'message' => $message,
                ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim WA notifikasi ke walikelas: ' . $e->getMessage());
        }
    }
}

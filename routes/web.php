<?php

use App\Http\Controllers\KelasController;
use App\Http\Controllers\JenisPelanggaranController;
use App\Http\Controllers\MuridController;
use App\Http\Controllers\NaikKelasController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return redirect()->route("login");
});

Route::get("/dashboard", function () {
    $user = auth()->user();

    // Hitung tanggal untuk filter
    $today = \Carbon\Carbon::today();
    $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
    $endOfMonth = \Carbon\Carbon::now()->endOfMonth();

    // Jika user adalah Walikelas, filter berdasarkan kelas yang dia pegang
    if ($user && $user->role === "Walikelas") {
        // Ambil semua kelas yang dipegang oleh Walikelas
        $kelasIds = \App\Models\Kelas::where(
            "username",
            $user->username,
        )->pluck("kelas");

        // Query base untuk pelanggaran (Walikelas)
        $pelanggaranQuery = \App\Models\Pelanggaran::whereIn(
            "kelas",
            $kelasIds,
        );

        // Top 5 murid dengan poin pelanggaran terbanyak bulan ini (Walikelas)
        $topPelanggaran = \App\Models\Pelanggaran::whereIn("kelas", $kelasIds)
            ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
            ->whereNotNull("NIS")
            ->selectRaw(
                "NIS, nama_murid, kelas, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin",
            )
            ->groupBy("NIS", "nama_murid", "kelas")
            ->orderByDesc("total_poin")
            ->take(5)
            ->get();

        // Top 5 kelas dengan pelanggaran terbanyak bulan ini (Walikelas)
        $topKelasPelanggaran = \App\Models\Pelanggaran::whereIn("kelas", $kelasIds)
            ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
            ->whereNotNull("kelas")
            ->selectRaw(
                "kelas, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin",
            )
            ->groupBy("kelas")
            ->orderByDesc("total_poin")
            ->take(5)
            ->get();

        // Statistik untuk Walikelas - gunakan snapshot kelas
        $stats = [
            "total_kelas" => $kelasIds->count(),
            "total_murid" => \App\Models\Murid::whereIn(
                "kelas",
                $kelasIds,
            )->count(),
            "pelanggaran_hari_ini" => (clone $pelanggaranQuery)
                ->whereDate("tanggal", $today)
                ->count(),
            "pelanggaran_bulan_ini" => (clone $pelanggaranQuery)
                ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
                ->count(),
            "recent_pelanggaran" => \App\Models\Pelanggaran::whereIn(
                "kelas",
                $kelasIds,
            )
                ->latest()
                ->take(5)
                ->get(),
            "top_pelanggaran" => $topPelanggaran,
            "top_kelas_pelanggaran" => $topKelasPelanggaran,
        ];
    } else {
        // Query base untuk pelanggaran (Admin/TATIB)
        $pelanggaranQuery = \App\Models\Pelanggaran::query();

        // Top 5 murid dengan poin pelanggaran terbanyak bulan ini (Admin/TATIB)
        $topPelanggaran = \App\Models\Pelanggaran::query()
            ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
            ->whereNotNull("NIS")
            ->selectRaw(
                "NIS, nama_murid, kelas, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin",
            )
            ->groupBy("NIS", "nama_murid", "kelas")
            ->orderByDesc("total_poin")
            ->take(5)
            ->get();

        // Top 5 kelas dengan pelanggaran terbanyak bulan ini (Admin/TATIB)
        $topKelasPelanggaran = \App\Models\Pelanggaran::query()
            ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
            ->whereNotNull("kelas")
            ->selectRaw(
                "kelas, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin",
            )
            ->groupBy("kelas")
            ->orderByDesc("total_poin")
            ->take(5)
            ->get();

        // Statistik untuk Admin dan TATIB (semua data)
        $stats = [
            "total_users" => \App\Models\User::count(),
            "total_kelas" => \App\Models\Kelas::count(),
            "total_murid" => \App\Models\Murid::count(),
            "pelanggaran_hari_ini" => (clone $pelanggaranQuery)
                ->whereDate("tanggal", $today)
                ->count(),
            "pelanggaran_bulan_ini" => (clone $pelanggaranQuery)
                ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
                ->count(),
            "recent_pelanggaran" => \App\Models\Pelanggaran::latest()
                ->take(5)
                ->get(),
            "top_pelanggaran" => $topPelanggaran,
            "top_kelas_pelanggaran" => $topKelasPelanggaran,
        ];
    }

    return view("dashboard", compact("stats"));
})
    ->middleware(["auth", "verified"])
    ->name("dashboard");

Route::middleware("auth")->group(function () {
    Route::get("/profile", [ProfileController::class, "edit"])->name(
        "profile.edit",
    );
    Route::patch("/profile", [ProfileController::class, "update"])->name(
        "profile.update",
    );
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy'); // Dinonaktifkan

    // Autentikasi Dua Faktor (Google Authenticator / TOTP)
    Route::post('/profile/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable'])
        ->name('two-factor.enable');
    Route::post('/profile/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])
        ->name('two-factor.confirm');
    Route::post('/profile/two-factor/cancel', [TwoFactorAuthenticationController::class, 'cancel'])
        ->name('two-factor.cancel');
    Route::delete('/profile/two-factor', [TwoFactorAuthenticationController::class, 'disable'])
        ->name('two-factor.disable');
    Route::post('/profile/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes');

    // User Management
    // Reset 2FA user lain (HP hilang / kehabisan recovery code). Didaftarkan sebelum
    // resource route supaya urutannya jelas; path-nya sendiri tidak bentrok.
    Route::post("/users/{user}/two-factor/reset", [UserController::class, "resetTwoFactor"])
        ->name("users.two-factor.reset");
    Route::resource("users", UserController::class);

    // Data Kelas
    Route::resource("kelas", KelasController::class);

    // Data Murid
    // Import routes harus diletakkan sebelum resource route agar tidak tertangkap sebagai parameter
    Route::get("/murid/import", [MuridController::class, "showImport"])->name(
        "murid.import",
    );
    Route::post("/murid/import", [MuridController::class, "import"])->name(
        "murid.import.store",
    );
    Route::get("/murid/import/template", [
        MuridController::class,
        "downloadTemplate",
    ])->name("murid.import.template");
    Route::resource("murid", MuridController::class);


    // Data Pelanggaran
    Route::get("/pelanggaran/rekap", [
        PelanggaranController::class,
        "rekap",
    ])->name("pelanggaran.rekap");
    Route::resource("pelanggaran", PelanggaranController::class);
    Route::get("/api/pelanggaran/murid/{nis}", [
        PelanggaranController::class,
        "getMurid",
    ])->name("api.pelanggaran.murid");

    // Jenis Pelanggaran Management (Admin & TATIB only)
    Route::resource("jenis-pelanggaran", JenisPelanggaranController::class);

    // Report
    Route::get("/report", [ReportController::class, "index"])->name(
        "report.index",
    );
    Route::post("/report/generate", [ReportController::class, "generate"])->name(
        "report.generate",
    );

    // Naik Kelas
    Route::get('/naik-kelas', [NaikKelasController::class, 'index'])->name('naik-kelas.index');
    Route::post('/naik-kelas/process', [NaikKelasController::class, 'processNaikKelas'])->name('naik-kelas.process');

    // Perubahan Kelas (upload massal, independen dari Naik Kelas)
    Route::get('/naik-kelas/perubahan-kelas/template', [NaikKelasController::class, 'downloadTemplatePerubahanKelas'])->name('naik-kelas.perubahan-kelas.template');
    Route::post('/naik-kelas/perubahan-kelas/preview', [NaikKelasController::class, 'previewPerubahanKelas'])->name('naik-kelas.perubahan-kelas.preview');
    Route::post('/naik-kelas/perubahan-kelas/proses', [NaikKelasController::class, 'prosesPerubahanKelas'])->name('naik-kelas.perubahan-kelas.proses');
    Route::post('/naik-kelas/perubahan-kelas/batal', [NaikKelasController::class, 'batalPerubahanKelas'])->name('naik-kelas.perubahan-kelas.batal');

});

require __DIR__ . "/auth.php";

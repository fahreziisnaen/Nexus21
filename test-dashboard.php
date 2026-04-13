<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "User: {$user->username} (Role: {$user->role})\n";
    $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
    $endOfMonth = \Carbon\Carbon::now()->endOfMonth();

    if ($user->role === "Walikelas") {
        $kelasIds = \App\Models\Kelas::where("username", $user->username)->pluck("kelas");
        $topKelas = \App\Models\Pelanggaran::whereIn("kelas", $kelasIds)
            ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
            ->whereNotNull("kelas")
            ->selectRaw("kelas, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin")
            ->groupBy("kelas")
            ->orderByDesc("total_poin")
            ->take(5)
            ->get();
    } else {
        $topKelas = \App\Models\Pelanggaran::query()
            ->whereBetween("tanggal", [$startOfMonth, $endOfMonth])
            ->whereNotNull("kelas")
            ->selectRaw("kelas, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin")
            ->groupBy("kelas")
            ->orderByDesc("total_poin")
            ->take(5)
            ->get();
    }
    echo "  Found " . $topKelas->count() . " top kelas records.\n";
}

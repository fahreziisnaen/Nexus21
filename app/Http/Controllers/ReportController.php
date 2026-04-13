<?php

namespace App\Http\Controllers;

use App\Helpers\SemesterHelper;
use App\Models\Kelas;
use App\Models\Murid;
use App\Models\Pelanggaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Halaman form report.
     */
    public function index()
    {
        $kelasList = Kelas::orderByRaw("
            CASE
                WHEN kelas LIKE 'X-%' THEN 1
                WHEN kelas LIKE 'XI-%' THEN 2
                WHEN kelas LIKE 'XII-%' THEN 3
                ELSE 4
            END,
            CAST(SUBSTRING_INDEX(kelas, '-', -1) AS UNSIGNED)
        ")->pluck('kelas');

        $semesterList = SemesterHelper::list();

        return view('report.index', compact('kelasList', 'semesterList'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'mode'     => 'required|in:kelas,murid',
            'semester' => 'required|string',
        ]);

        // Perbesar limit untuk report besar
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $mode     = $request->input('mode');
        $semester = $request->input('semester');
        $semesterLabel = SemesterHelper::label($semester);

        if ($mode === 'kelas') {
            return $this->generatePerKelas($request, $semester, $semesterLabel);
        }

        return $this->generatePerMurid($request, $semester, $semesterLabel);
    }

    /**
     * Generate PDF per kelas.
     */
    private function generatePerKelas(Request $request, string $semester, string $semesterLabel)
    {
        $selectedKelas = $request->input('kelas_list', []);
        $selectedLevel = $request->input('selected_level'); // X, XI, or XII

        // If level is selected, resolve it to all classes in that level
        if ($selectedLevel) {
            $selectedKelas = Kelas::where('kelas', 'LIKE', $selectedLevel . '-%')
                ->pluck('kelas')
                ->toArray();
        }

        if (empty($selectedKelas)) {
            return back()->with('error', 'Silakan pilih setidaknya satu kelas atau tingkat.');
        }

        // Sort classes naturally
        $sortedKelas = collect($selectedKelas)->sort(function ($a, $b) {
            return $this->kelasSort($a) <=> $this->kelasSort($b);
        })->values();

        // Batch fetch all active students for target classes
        $allStudents = Murid::whereIn('kelas', $sortedKelas)
            ->where('status', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get()
            ->groupBy('kelas');

        // Batch fetch all violations for all target classes with reporter info
        $allViolations = Pelanggaran::with('pencatat')->whereIn('kelas', $sortedKelas)
            ->where('semester', $semester)
            ->whereNotNull('NIS')
            ->get()
            ->groupBy(['kelas', 'NIS']);

        $kelasData = [];
        foreach ($sortedKelas as $kelasName) {
            $students = $allStudents->get($kelasName, collect());

            if ($students->isEmpty()) {
                continue;
            }

            $classViolations = $allViolations->get($kelasName, collect());

            // Build student summary for this class
            $muridSummary = $students->map(function ($student) use ($classViolations) {
                $studentViolations = $classViolations->get($student->NIS, collect());
                
                return (object) [
                    'NIS'         => $student->NIS,
                    'nama_murid'  => $student->nama_lengkap,
                    'gender'      => $student->gender,
                    'total_poin'  => $studentViolations->sum('poin'),
                    'jumlah'      => $studentViolations->count(),
                    'pelanggaran' => $studentViolations->map(function ($r) {
                        return (object) [
                            'tanggal'           => $r->tanggal,
                            'jenis_pelanggaran' => $r->jenis_pelanggaran,
                            'poin'              => $r->poin,
                            'pelapor'           => $r->pencatat->nama_lengkap ?? $r->username,
                        ];
                    })->values(),
                ];
            });

            $kelasData[] = (object) [
                'kelas'         => $kelasName,
                'murid'         => $muridSummary,
                'total_poin'    => $muridSummary->sum('total_poin'),
                'total_records' => $muridSummary->sum('jumlah'),
            ];
        }

        $logoPath = public_path('images/sman21surabaya.png');

        $pdf = Pdf::loadView('report.pdf.per_kelas', compact(
            'kelasData', 'semesterLabel', 'semester', 'logoPath'
        ));

        $pdf->setPaper('a4', 'portrait');

        $parsed = SemesterHelper::parse($semester);
        $levelSuffix = $selectedLevel ? '_Kelas_' . $selectedLevel : '';
        $filename = 'Laporan_PerKelas' . $levelSuffix . '_' . str_replace('/', '-', $parsed['tahun_ajaran']) . '_Sem' . $parsed['semester'] . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate PDF per murid.
     */
    private function generatePerMurid(Request $request, string $semester, string $semesterLabel)
    {
        $selectedKelas = $request->input('kelas_list', []);
        $kelasList = \App\Models\Kelas::pluck('kelas')->toArray();

        if (empty($selectedKelas)) {
            return back()->with('error', 'Silakan pilih setidaknya satu kelas.');
        }

        // Determine filename suffix based on selection
        if (count($selectedKelas) === count($kelasList)) {
            $classSuffix = '_Semua_Kelas';
        } elseif (count($selectedKelas) <= 3) {
            $classSuffix = '_Kelas_' . implode('_', $selectedKelas);
        } else {
            $classSuffix = '_Multiple_Kelas';
        }

        // Get target students from selected classes
        $students = Murid::whereIn('kelas', $selectedKelas)
            ->where('status', 'Aktif')
            ->orderBy('kelas')
            ->orderBy('nama_lengkap')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada data murid ditemukan pada kelas yang dipilih.');
        }

        // Batch violations
        $nisList = $students->pluck('NIS');
        $violations = collect();
        
        foreach ($nisList->chunk(500) as $chunk) {
            $chunkViolations = Pelanggaran::with('pencatat')->whereIn('NIS', $chunk)
                ->where('semester', $semester)
                ->get();
            $violations = $violations->concat($chunkViolations);
        }
        
        $violationGroups = $violations->groupBy('NIS');

        // Build data per murid, but ONLY if they have violations
        $muridData = $students->map(function ($student) use ($violationGroups) {
            $items = $violationGroups->get($student->NIS, collect());
            
            // Skip students with no violations
            if ($items->isEmpty()) return null;

            $totalPoin = $items->sum('poin');

            return (object) [
                'NIS'         => $student->NIS,
                'nama_murid'  => $student->nama_lengkap,
                'gender'      => $student->gender,
                'kelas'       => $student->kelas,
                'total_poin'  => $totalPoin,
                'penanganan'  => $this->getPenanganan($totalPoin),
                'pelanggaran' => $items->map(function ($r) {
                    return (object) [
                        'tanggal'           => $r->tanggal,
                        'jenis_pelanggaran' => $r->jenis_pelanggaran,
                        'poin'              => $r->poin,
                        'keterangan'        => $r->keterangan,
                        'pelapor'           => $r->pencatat->nama_lengkap ?? $r->username,
                    ];
                })->values(),
            ];
        })->filter()->values(); // Remove nulls and reset indices

        if ($muridData->isEmpty()) {
            return back()->with('error', 'Tidak ada murid dengan catatan pelanggaran pada kelas dan semester yang dipilih.');
        }

        $logoPath = public_path('images/sman21surabaya.png');

        $pdf = Pdf::loadView('report.pdf.per_murid', compact(
            'muridData', 'semesterLabel', 'semester', 'logoPath'
        ));

        $pdf->setPaper('a4', 'portrait');

        $parsed = SemesterHelper::parse($semester);
        $filename = 'Laporan_PerMurid' . $classSuffix . '_' . str_replace('/', '-', $parsed['tahun_ajaran']) . '_Sem' . $parsed['semester'] . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * API: list murid for select2 / search.
     */
    public function muridList(Request $request)
    {
        $search = $request->input('q', '');
        $query = Murid::where('status', 'Aktif');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('NIS', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nama_lengkap')
            ->take(50)
            ->get(['NIS', 'nama_lengkap', 'kelas']);
    }

    /**
     * Natural sort helper for kelas.
     */
    private function kelasSort(string $kelas): int
    {
        $parts = explode('-', $kelas);
        $prefix = $parts[0] ?? '';
        $num = (int) ($parts[1] ?? 0);

        $prefixOrder = match ($prefix) {
            'X'   => 100,
            'XI'  => 200,
            'XII' => 300,
            default => 400,
        };

        return $prefixOrder + $num;
    }

    /**
     * Get penanganan by total poin.
     */
    private function getPenanganan(int $totalPoin): object
    {
        if ($totalPoin <= 10) {
            return (object) [
                'level' => 'Ringan',
                'color' => '#16a34a',
                'label' => 'Teguran Lisan / Tertulis',
                'desc'  => 'Teguran lisan atau tertulis bagi yang melakukan pelanggaran ringan.',
            ];
        }
        if ($totalPoin <= 25) {
            return (object) [
                'level' => 'Sedang',
                'color' => '#ca8a04',
                'label' => 'Hukuman Mendidik',
                'desc'  => 'Hukuman pemberian tugas yang sifatnya mendidik, misalnya membuat rangkuman buku tertentu.',
            ];
        }
        if ($totalPoin <= 50) {
            return (object) [
                'level' => 'Berat',
                'color' => '#ea580c',
                'label' => 'Pemanggilan Orang Tua',
                'desc'  => 'Memanggil yang bersangkutan bersama orang tuanya untuk membuat surat pernyataan.',
            ];
        }
        if ($totalPoin <= 85) {
            return (object) [
                'level' => 'Sangat Berat',
                'color' => '#dc2626',
                'label' => 'Skorsing (5 Hari)',
                'desc'  => 'Siswa belajar di rumah selama 5 hari (skorsing).',
            ];
        }
        return (object) [
            'level' => 'Dikeluarkan',
            'color' => '#7f1d1d',
            'label' => 'Dikembalikan ke Orang Tua',
            'desc'  => 'Siswa yang bersangkutan dikeluarkan dari sekolah.',
        ];
    }
}

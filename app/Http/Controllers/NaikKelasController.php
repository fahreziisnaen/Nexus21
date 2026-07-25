<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Murid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class NaikKelasController extends Controller
{
    /**
     * Check if user is Admin, block access for other roles
     */
    private function checkAdminAccess()
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'Admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki izin untuk mengakses halaman ini. Hanya Admin yang dapat mengakses.');
        }

        return null;
    }

    /**
     * Display the naik kelas page.
     */
    public function index()
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        // Ambil semua murid aktif dari semua kelas untuk ditampilkan di form
        // Sort kelas dengan natural sort (X-1, X-2, ..., X-10, XI-1, ...)
        $murids = Murid::where('status', 'Aktif')
            ->whereNotNull('kelas')
            ->orderByRaw("
                CASE 
                    WHEN kelas LIKE 'X-%' THEN 1
                    WHEN kelas LIKE 'XI-%' THEN 2
                    WHEN kelas LIKE 'XII-%' THEN 3
                    ELSE 4
                END,
                CAST(SUBSTRING_INDEX(kelas, '-', -1) AS UNSIGNED)
            ")
            ->orderBy('nama_lengkap')
            ->get();

        return view('naik-kelas.index', compact('murids'));
    }

    /**
     * Process naik kelas untuk semua murid di semua kelas sekaligus.
     */
    public function processNaikKelas(Request $request)
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        $request->validate([
            'ada_murid_tetap' => ['required', 'in:ya,tidak'],
            'murid_tetap' => ['nullable', 'array'],
            'murid_tetap.*' => ['exists:murid,NIS'],
        ]);

        $adaMuridTetap = $request->ada_murid_tetap;
        $muridTetap = $request->murid_tetap ?? [];

        // Validasi: jika pilih "ya" tapi tidak ada murid yang dipilih
        if ($adaMuridTetap === 'ya' && empty($muridTetap)) {
            return redirect()->back()
                ->with('error', 'Anda memilih "Iya" tetapi belum memilih murid tetap. Silakan pilih murid tetap terlebih dahulu.')
                ->withInput();
        }

        // Ambil semua murid aktif dari semua kelas
        $allMurids = Murid::where('status', 'Aktif')
            ->whereNotNull('kelas')
            ->get();

        $currentYear = date('Y');
        $processedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            // Group murid by kelas untuk efisiensi
            $muridsByKelas = $allMurids->groupBy('kelas');

            foreach ($muridsByKelas as $currentKelas => $murids) {
                // Tentukan kelas baru berdasarkan kelas saat ini
                $newKelas = $this->getNextKelas($currentKelas);

                if (! $newKelas) {
                    // Skip jika format kelas tidak valid
                    continue;
                }

                // Jika kelas baru adalah "Lulus"
                if ($newKelas === 'Lulus') {
                    foreach ($murids as $murid) {
                        // Jika murid termasuk dalam murid tetap, skip
                        if (in_array($murid->NIS, $muridTetap)) {
                            $skippedCount++;

                            continue;
                        }

                        $murid->update([
                            'status' => 'Lulus',
                            'tahun_lulus' => $currentYear,
                            'kelas' => null,
                        ]);
                        $processedCount++;
                    }
                } else {
                    // Cek apakah kelas baru sudah ada di database
                    $kelasExists = Kelas::where('kelas', $newKelas)->exists();

                    if (! $kelasExists) {
                        // Jika kelas baru belum ada, buat kelas baru dengan username yang sama
                        $oldKelas = Kelas::where('kelas', $currentKelas)->first();
                        if ($oldKelas) {
                            Kelas::create([
                                'kelas' => $newKelas,
                                'username' => $oldKelas->username,
                            ]);
                        } else {
                            // Jika kelas lama tidak ada, buat kelas baru tanpa wali
                            Kelas::create([
                                'kelas' => $newKelas,
                                'username' => null,
                            ]);
                        }
                    }

                    // Update kelas murid
                    foreach ($murids as $murid) {
                        // Jika murid termasuk dalam murid tetap, skip
                        if (in_array($murid->NIS, $muridTetap)) {
                            $skippedCount++;

                            continue;
                        }

                        $murid->update([
                            'kelas' => $newKelas,
                        ]);
                        $processedCount++;
                    }
                }
            }

            DB::commit();

            $message = 'Proses naik kelas berhasil dilakukan. ';
            $message .= $processedCount.' murid dinaikkan/luluskan.';
            if ($skippedCount > 0) {
                $message .= ' '.$skippedCount.' murid tetap di kelas yang sama.';
            }

            return redirect()->route('naik-kelas.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memproses naik kelas: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get next kelas based on current kelas.
     * X-* → XI-*
     * XI-* → XII-*
     * XII-* → Lulus
     */
    private function getNextKelas($currentKelas)
    {
        // Cek apakah kelas adalah XII
        if (preg_match('/^XII-(\d+)$/', $currentKelas, $matches)) {
            return 'Lulus';
        }

        // Cek apakah kelas adalah XI
        if (preg_match('/^XI-(\d+)$/', $currentKelas, $matches)) {
            return 'XII-'.$matches[1];
        }

        // Cek apakah kelas adalah X
        if (preg_match('/^X-(\d+)$/', $currentKelas, $matches)) {
            return 'XI-'.$matches[1];
        }

        // Jika format tidak sesuai, return null
        return null;
    }

    /**
     * Download template Excel untuk perubahan kelas (NIS + Kelas Baru).
     */
    public function downloadTemplatePerubahanKelas()
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        $filename = 'template_perubahan_kelas.xlsx';
        $tempPath = storage_path('app/temp/'.$filename);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        try {
            $options = new Options;
            $options->setTempFolder(storage_path('app/temp'));
            $writer = new Writer($options);
            $writer->openToFile($tempPath);

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11);

            $writer->addRow(Row::fromValues(['NIS', 'Kelas Baru'], $headerStyle));
            $writer->addRow(Row::fromValues(['1234567890', 'XI-2']));
            $writer->addRow(Row::fromValues(['1234567891', 'XI-3']));

            $writer->close();

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return redirect()->route('naik-kelas.index')
                ->with('error', 'Gagal download template: '.$e->getMessage());
        }
    }

    /**
     * Parse file Excel perubahan kelas menjadi baris [row_number, nis, kelas_baru].
     * Baris header (baris pertama) dan baris yang sepenuhnya kosong dilewati.
     */
    private function parsePerubahanKelasFile(string $path): array
    {
        $rows = [];
        $reader = new Reader;
        $reader->open($path);

        $rowIndex = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                if ($rowIndex === 1) {
                    continue;
                }

                // getCellAtIndex() dipakai (bukan getCells()[$i]) karena array cell OpenSpout
                // mengikuti "spans" baris tsb, bukan lebar penuh sheet — baris yang kolom
                // terakhirnya benar-benar kosong (bukan cuma string kosong) bisa punya cell
                // yang lebih sedikit dari yang diharapkan.
                $nis = trim((string) ($row->getCellAtIndex(0)?->getValue() ?? ''));
                $kelasBaru = trim((string) ($row->getCellAtIndex(1)?->getValue() ?? ''));

                if ($nis === '' && $kelasBaru === '') {
                    continue;
                }

                $rows[] = [
                    'row_number' => $rowIndex,
                    'nis' => $nis,
                    'kelas_baru' => $kelasBaru,
                ];
            }
            break;
        }

        $reader->close();

        return $rows;
    }

    /**
     * Validasi semua baris perubahan kelas. Tidak ada perubahan yang dianggap
     * "siap disimpan" kalau ada satu saja baris error (all-or-nothing di level caller).
     */
    private function validatePerubahanKelasRows(array $rows): array
    {
        $errors = [];
        $changes = [];
        $skippedNoop = 0;

        $nisCounts = [];
        foreach ($rows as $row) {
            $nisCounts[$row['nis']] = ($nisCounts[$row['nis']] ?? 0) + 1;
        }

        $activeMurids = Murid::where('status', 'Aktif')->get()->keyBy('NIS');
        $validKelas = Kelas::pluck('kelas')->flip();

        $netChange = [];

        foreach ($rows as $row) {
            $rowNumber = $row['row_number'];
            $nis = $row['nis'];
            $kelasBaru = $row['kelas_baru'];

            if ($nis === '' || $kelasBaru === '') {
                $errors[] = "Baris {$rowNumber}: NIS dan Kelas Baru wajib diisi";

                continue;
            }

            if ($nisCounts[$nis] > 1) {
                $errors[] = "Baris {$rowNumber}: NIS {$nis} muncul lebih dari sekali dalam file";

                continue;
            }

            $murid = $activeMurids->get($nis);
            if (! $murid) {
                $errors[] = "Baris {$rowNumber}: NIS {$nis} tidak ditemukan atau bukan murid aktif";

                continue;
            }

            if (! $validKelas->has($kelasBaru)) {
                $errors[] = "Baris {$rowNumber}: Kelas '{$kelasBaru}' tidak ditemukan";

                continue;
            }

            $kelasLama = $murid->kelas;
            if ($kelasLama === $kelasBaru) {
                $skippedNoop++;

                continue;
            }

            $netChange[$kelasLama] = ($netChange[$kelasLama] ?? 0) - 1;
            $netChange[$kelasBaru] = ($netChange[$kelasBaru] ?? 0) + 1;

            $changes[] = [
                'NIS' => $nis,
                'nama_lengkap' => $murid->nama_lengkap,
                'kelas_lama' => $kelasLama,
                'kelas_baru' => $kelasBaru,
            ];
        }

        if (! empty($netChange)) {
            $currentCounts = Murid::where('status', 'Aktif')
                ->whereIn('kelas', array_keys($netChange))
                ->selectRaw('kelas, count(*) as total')
                ->groupBy('kelas')
                ->pluck('total', 'kelas');

            foreach ($netChange as $kelas => $delta) {
                $current = (int) ($currentCounts[$kelas] ?? 0);
                $projected = $current + $delta;

                if ($projected > 40) {
                    $errors[] = "Kelas '{$kelas}' akan melebihi kapasitas 40 murid (saat ini {$current}, akan menjadi {$projected})";
                }
            }
        }

        return [
            'errors' => $errors,
            'changes' => $changes,
            'skipped_noop' => $skippedNoop,
        ];
    }

    /**
     * Preview perubahan kelas dari file yang diupload. Kalau semua baris valid,
     * simpan hasilnya ke session untuk tahap konfirmasi (prosesPerubahanKelas).
     */
    public function previewPerubahanKelas(Request $request)
    {
        $redirect = $this->checkAdminAccess();
        if ($redirect) {
            return $redirect;
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'file.required' => 'File harus diupload.',
            'file.file' => 'File tidak valid.',
            'file.mimes' => 'File harus berformat Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        session()->forget('perubahan_kelas_preview');

        try {
            $rows = $this->parsePerubahanKelasFile($request->file('file')->getRealPath());

            if (empty($rows)) {
                return redirect()->route('naik-kelas.index')
                    ->with('error_perubahan_kelas', 'File tidak berisi data. Pastikan file memiliki baris data selain header.');
            }

            $result = $this->validatePerubahanKelasRows($rows);
        } catch (\Throwable $e) {
            // Tangkap file corrupt/rusak yang lolos validasi mimes:xlsx, maupun
            // error tak terduga lain saat parsing/validasi, supaya user selalu
            // dapat pesan flash yang ramah alih-alih HTTP 500 mentah.
            return redirect()->route('naik-kelas.index')
                ->with('error_perubahan_kelas', 'Gagal membaca file. Pastikan file berformat Excel (.xlsx) yang valid.');
        }

        if (! empty($result['errors'])) {
            $displayedErrors = array_slice($result['errors'], 0, 10);

            return redirect()->route('naik-kelas.index')
                ->with('perubahan_kelas_errors', $displayedErrors)
                ->with('perubahan_kelas_errors_remaining', count($result['errors']) - count($displayedErrors));
        }

        if (empty($result['changes'])) {
            return redirect()->route('naik-kelas.index')
                ->with('error_perubahan_kelas', 'Tidak ada perubahan kelas yang perlu diproses (semua baris kelas barunya sama dengan kelas saat ini).');
        }

        session(['perubahan_kelas_preview' => $result['changes']]);

        return redirect()->route('naik-kelas.index')
            ->with('perubahan_kelas_preview_ready', true)
            ->with('perubahan_kelas_skipped_noop', $result['skipped_noop']);
    }
}

<?php

namespace App\Helpers;

use Carbon\Carbon;

class SemesterHelper
{
    /**
     * Semester aktif dihitung otomatis dari tanggal hari ini:
     *   Jul–Des → Semester 1 (Ganjil) tahun ajaran berjalan
     *   Jan–Jun → Semester 2 (Genap) tahun ajaran berjalan
     */
    public static function current(): string
    {
        return self::deriveFromDate(Carbon::now());
    }

    /**
     * Derive semester string dari tanggal tertentu.
     */
    public static function deriveFromDate(Carbon $date): string
    {
        $month = $date->month;
        $year  = $date->year;

        if ($month >= 7) {
            $tahunAjaran = $year . '/' . ($year + 1);
            $sem = 1;
        } else {
            $tahunAjaran = ($year - 1) . '/' . $year;
            $sem = 2;
        }

        return $tahunAjaran . '-' . $sem;
    }

    /**
     * Parse "2024/2025-1" → ['tahun_ajaran' => '2024/2025', 'semester' => 1]
     */
    public static function parse(string $semesterStr): array
    {
        $parts = explode('-', $semesterStr);
        return [
            'tahun_ajaran' => $parts[0] ?? '',
            'semester'     => (int) ($parts[1] ?? 1),
        ];
    }

    /**
     * "2024/2025-1" → "Semester 1 (Ganjil) — 2024/2025"
     */
    public static function label(string $semesterStr): string
    {
        $parsed   = self::parse($semesterStr);
        $semLabel = $parsed['semester'] === 1 ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
        return $semLabel . ' — ' . $parsed['tahun_ajaran'];
    }

    /**
     * Semester berikutnya.
     * "2024/2025-1" → "2024/2025-2"
     * "2024/2025-2" → "2025/2026-1"
     */
    public static function next(string $semesterStr): string
    {
        $parsed     = self::parse($semesterStr);
        $tahunParts = explode('/', $parsed['tahun_ajaran']);

        if ($parsed['semester'] === 1) {
            return $parsed['tahun_ajaran'] . '-2';
        } else {
            $nextStart = (int) ($tahunParts[1] ?? (int) $tahunParts[0] + 1);
            return $nextStart . '/' . ($nextStart + 1) . '-1';
        }
    }

    /**
     * Daftar semester yang ada di tabel pelanggaran, sorted descending.
     */
    public static function list(): array
    {
        $semesters = \App\Models\Pelanggaran::whereNotNull('semester')
            ->distinct()
            ->pluck('semester')
            ->toArray();

        // Pastikan semester aktif selalu ada
        $current = self::current();
        if (!in_array($current, $semesters)) {
            $semesters[] = $current;
        }

        usort($semesters, fn($a, $b) => strcmp($b, $a));

        return $semesters;
    }
}

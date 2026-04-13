<x-app-layout>
    <x-slot name="header">
        <!-- Mobile View -->
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Detail Pelanggaran
                </h2>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Detail Pelanggaran
            </h2>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ itemToDelete: null }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl rounded-2xl">

                {{-- Header Section --}}
                @php
                    $headerColor = match($pelanggaran->jenis_pelanggaran) {
                        'Terlambat'             => 'from-indigo-600 to-indigo-700',
                        'Seragam Tidak Lengkap' => 'from-yellow-500 to-yellow-600',
                        'Baju Tidak Dimasukkan' => 'from-indigo-500 to-indigo-600',
                        'Mewarnai Rambut'       => 'from-purple-600 to-purple-700',
                        default                 => 'from-indigo-600 to-indigo-700',
                    };
                    $badgeClass = match($pelanggaran->jenis_pelanggaran) {
                        'Terlambat'             => 'bg-red-100 text-red-800 border border-red-200',
                        'Seragam Tidak Lengkap' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                        'Baju Tidak Dimasukkan' => 'bg-orange-100 text-orange-800 border border-orange-200',
                        'Mewarnai Rambut'       => 'bg-purple-100 text-purple-800 border border-purple-200',
                        default                 => 'bg-gray-100 text-gray-800 border border-gray-200',
                    };
                    $iconPath = match($pelanggaran->jenis_pelanggaran) {
                        'Terlambat'             => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                        'Seragam Tidak Lengkap' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'Baju Tidak Dimasukkan' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'Mewarnai Rambut'       => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                        default                 => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    };
                @endphp

                <div class="bg-gradient-to-r {{ $headerColor }} px-4 sm:px-6 py-4 sm:py-5">
                    <div class="flex flex-row items-center justify-between gap-3">
                        {{-- Student Info --}}
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-2 shrink-0">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-bold text-white leading-tight truncate">{{ $pelanggaran->nama_murid ?? '-' }}</h3>
                                <p class="text-white/80 text-xs sm:text-sm whitespace-nowrap">
                                    <span class="hidden sm:inline">NIS:</span> {{ $pelanggaran->NIS ?? '-' }}
                                    <span class="hidden sm:inline mx-1">&bull;</span>
                                    {{ $pelanggaran->kelas ?? '-' }}
                                    <span class="hidden sm:inline mx-1">&bull;</span>
                                    {{ $pelanggaran->gender ?? '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Points Badge --}}
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <div class="bg-white/15 backdrop-blur-sm rounded-lg sm:rounded-xl px-2 sm:px-4 py-1.5 sm:py-2 text-center">
                                <p class="text-white/80 text-[10px] sm:text-xs font-medium uppercase tracking-wide">Poin</p>
                                <p class="text-white font-black text-xl sm:text-3xl leading-tight">+{{ $pelanggaran->poin }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">

                        {{-- Left Column --}}
                        <div class="space-y-6">

                            {{-- Violation Type --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Jenis Pelanggaran</h4>
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <span class="text-gray-900 font-semibold text-sm">{{ $pelanggaran->jenis_pelanggaran }}</span>
                                </div>
                            </div>

                            {{-- Violation Type Description --}}
                            @php $deskripsiJenis = $pelanggaran->jenisPelanggaranRelation?->deskripsi; @endphp
                            @if($deskripsiJenis)
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Deskripsi Jenis Pelanggaran</h4>
                                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100 flex gap-3">
                                    <div class="shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-indigo-700 leading-relaxed">{{ $deskripsiJenis }}</p>
                                </div>
                            </div>
                            @endif

                            {{-- Date --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tanggal Pelanggaran</h4>
                                <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-gray-900 font-semibold text-sm">
                                        {{ \Carbon\Carbon::parse($pelanggaran->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Recorded By --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Dicatat Oleh</h4>
                                <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-gray-700 text-sm font-medium">
                                        {{ $pelanggaran->pencatat?->nama_lengkap ?? ($pelanggaran->username ?? '-') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Keterangan</h4>
                                @if($pelanggaran->keterangan)
                                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                        <p class="text-gray-700 text-sm leading-relaxed break-words whitespace-pre-line">{{ trim($pelanggaran->keterangan) }}</p>
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-xl p-4 border border-dashed border-gray-200 flex items-center gap-3">
                                        <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="text-gray-400 text-sm italic">Tidak ada keterangan</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Total Points for this student --}}
                            @if($pelanggaran->NIS)
                            @php
                                $query = \App\Models\Pelanggaran::where('NIS', $pelanggaran->NIS);
                                $semesterLabel = 'Semua pelanggaran yang tercatat';
                                
                                if (!empty($pelanggaran->semester)) {
                                    $query->where('semester', $pelanggaran->semester);
                                    $semesterLabel = 'Semester: ' . \App\Helpers\SemesterHelper::label($pelanggaran->semester);
                                }
                                
                                $totalPoin = (clone $query)->sum('poin');
                                $totalPelanggaran = (clone $query)->count();
                            @endphp
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Akumulasi Poin Siswa</h4>
                                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-indigo-800 font-semibold text-sm">Total poin dari {{ $totalPelanggaran }} pelanggaran</p>
                                        <p class="text-indigo-600 text-xs mt-0.5">{{ $semesterLabel }}</p>
                                    </div>
                                    <span class="text-3xl font-black text-indigo-700">{{ $totalPoin }}</span>
                                </div>
                            </div>

                            {{-- Penanganan berdasarkan total poin --}}
                            @php
                                if ($totalPoin <= 10) {
                                    $penangananLevel = 'a';
                                    $penangananLabel = 'Teguran Lisan/Tertulis';
                                    $penangananDesc = 'Teguran lisan atau tertulis bagi yang melakukan pelanggaran ringan.';
                                    $penangananColor = 'bg-green-50 border-green-200';
                                    $penangananTextColor = 'text-green-800';
                                    $penangananSubColor = 'text-green-600';
                                    $penangananBadge = 'bg-green-100 text-green-700';
                                    $penangananIcon = 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                                } elseif ($totalPoin <= 25) {
                                    $penangananLevel = 'b';
                                    $penangananLabel = 'Hukuman Mendidik';
                                    $penangananDesc = 'Hukuman pemberian tugas yang sifatnya mendidik, misalnya membuat rangkuman buku tertentu, menterjemahkan tulisan berbahasa Inggris.';
                                    $penangananColor = 'bg-yellow-50 border-yellow-200';
                                    $penangananTextColor = 'text-yellow-800';
                                    $penangananSubColor = 'text-yellow-600';
                                    $penangananBadge = 'bg-yellow-100 text-yellow-700';
                                    $penangananIcon = 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
                                } elseif ($totalPoin <= 50) {
                                    $penangananLevel = 'c';
                                    $penangananLabel = 'Pemanggilan Orang Tua';
                                    $penangananDesc = 'Memanggil yang bersangkutan bersama orang tuanya untuk membuat surat pernyataan agar tidak mengulangi pelanggaran, bersama Wali Kelas, Tim Tatib dibantu Guru BK dan Kesiswaan.';
                                    $penangananColor = 'bg-orange-50 border-orange-200';
                                    $penangananTextColor = 'text-orange-800';
                                    $penangananSubColor = 'text-orange-600';
                                    $penangananBadge = 'bg-orange-100 text-orange-700';
                                    $penangananIcon = 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z';
                                } elseif ($totalPoin <= 85) {
                                    $penangananLevel = 'd';
                                    $penangananLabel = 'Skorsing (Belajar di Rumah)';
                                    $penangananDesc = 'Siswa belajar di rumah selama 5 hari apabila yang bersangkutan melakukan pelanggaran peraturan sekolah berkali-kali dan cukup berat, siswa di skors (belajar di rumah).';
                                    $penangananColor = 'bg-red-50 border-red-200';
                                    $penangananTextColor = 'text-red-800';
                                    $penangananSubColor = 'text-red-600';
                                    $penangananBadge = 'bg-red-100 text-red-700';
                                    $penangananIcon = 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6';
                                } else {
                                    $penangananLevel = 'e';
                                    $penangananLabel = 'Dikembalikan ke Orang Tua';
                                    $penangananDesc = 'Mengembalikan kepada orang tua murid, misalnya yang bersangkutan tersangkut perkara pidana dan perdata yang dibuktikan oleh pengadilan. Siswa yang bersangkutan dikeluarkan dari sekolah.';
                                    $penangananColor = 'bg-red-100 border-red-300';
                                    $penangananTextColor = 'text-red-900';
                                    $penangananSubColor = 'text-red-700';
                                    $penangananBadge = 'bg-red-200 text-red-800';
                                    $penangananIcon = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
                                }
                            @endphp
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Penanganan</h4>
                                <div class="{{ $penangananColor }} rounded-xl p-4 border">
                                    <div class="flex items-start gap-3">
                                        <div class="shrink-0 mt-0.5">
                                            <svg class="w-5 h-5 {{ $penangananSubColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $penangananIcon }}"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-bold {{ $penangananBadge }} px-2 py-0.5 rounded-full uppercase">{{ $penangananLevel }}</span>
                                                <span class="{{ $penangananTextColor }} font-semibold text-sm">{{ $penangananLabel }}</span>
                                            </div>
                                            <p class="{{ $penangananSubColor }} text-xs leading-relaxed">{{ $penangananDesc }}</p>
                                            <p class="text-gray-400 text-[10px] mt-1.5">Berdasarkan poin {{ $totalPoin }} (rentang: 0–10, 10–25, 26–50, 51–85, 86–100)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Right Column: Evidence & Signature --}}
                        <div class="space-y-6">
                            {{-- Evidence --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Bukti Foto</h4>
                                @if($pelanggaran->bukti)
                                    <div class="rounded-xl overflow-hidden border-2 border-dashed border-gray-200 bg-gray-50 p-2">
                                        <div class="relative group cursor-pointer" onclick="openModal('{{ asset('storage/' . $pelanggaran->bukti) }}')">
                                            <img src="{{ asset('storage/' . $pelanggaran->bukti) }}"
                                                 alt="Bukti Pelanggaran"
                                                 class="w-full h-40 sm:h-64 object-cover rounded-lg shadow-sm group-hover:opacity-90 transition-opacity">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/20 rounded-lg">
                                                <span class="bg-white/90 text-gray-900 px-3 py-1 rounded-full text-xs font-semibold shadow-lg backdrop-blur-sm">
                                                    Klik untuk memperbesar
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-center text-xs text-gray-400">
                                            Klik gambar untuk melihat ukuran penuh
                                        </div>
                                    </div>
                                @else
                                    <div class="h-48 sm:h-64 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm font-medium">Tidak ada bukti foto</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Tanda Tangan Siswa --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tanda Tangan Siswa</h4>
                                @if($pelanggaran->ttd_murid)
                                    <div class="rounded-xl border-2 border-indigo-200 bg-indigo-50/50 p-4 text-center flex flex-col items-center justify-center">
                                        <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-3 inline-block">
                                            <img src="{{ asset('storage/' . $pelanggaran->ttd_murid) }}"
                                                 alt="Tanda Tangan Siswa"
                                                 class="h-32 w-auto max-w-full object-contain"
                                                 style="image-rendering: auto;">
                                        </div>
                                        <span class="text-xs text-gray-600 mt-3 font-medium flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Dikonfirmasi oleh siswa
                                        </span>
                                    </div>
                                @else
                                    <div class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-6 text-center flex flex-col items-center justify-center min-h-[140px]">
                                        <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        <span class="text-sm text-gray-400 font-medium">Tanda tangan tidak tersedia</span>
                                        <span class="text-xs text-gray-400 mt-1">Siswa belum memberikan tanda tangan konfirmasi</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                    {{-- Footer Actions --}}
                    <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-4">
                        <a href="{{ route('pelanggaran.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200 w-full sm:w-auto order-2 sm:order-1">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Kembali
                        </a>

                        @if(Auth::user()->role !== 'Walikelas')
                            <div class="flex gap-2 sm:gap-3 w-full sm:w-auto order-1 sm:order-2">
                                <a href="{{ route('pelanggaran.edit', $pelanggaran->id) }}"
                                   class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-sm font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span class="sm:hidden">✎</span>
                                    <span class="hidden sm:inline">Edit</span>
                                </a>
                                <form style="display:contents;" class="flex-1 sm:flex-none">
                                    <button type="button" @click="itemToDelete = {{ $pelanggaran->id }}; $dispatch('open-modal', 'confirm-pelanggaran-deletion')"'"
                                            class="w-full sm:w-auto inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium rounded-lg transition-colors duration-200"
                                            title="Hapus">
                                        <svg class="w-4 h-4 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span class="sm:hidden">🗑</span>
                                        <span class="hidden sm:inline">Hapus</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <x-modal name="confirm-pelanggaran-deletion" :show="false" focusable>
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-4 mx-auto md:mx-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                
                <h2 class="text-lg font-bold text-gray-900 text-center md:text-left">
                    Hapus Data Pelanggaran
                </h2>

                <p class="mt-2 text-sm text-gray-600 text-center md:text-left">
                    Apakah Anda yakin ingin menghapus data pelanggaran <span class="font-semibold text-gray-800">{{ $pelanggaran->jenis_pelanggaran }}</span> dari <span class="font-semibold text-gray-800">{{ $pelanggaran->nama_murid }}</span>? Data dan bukti foto yang telah dihapus tidak dapat dikembalikan.
                </p>

                <div class="mt-6 flex flex-col-reverse md:flex-row justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')" class="w-full md:w-auto justify-center">
                        Batal
                    </x-secondary-button>

                    <form :action="`/pelanggaran/${itemToDelete}`" method="POST" class="w-full md:w-auto">
                        @csrf
                        @method('DELETE')
                        <x-danger-button class="w-full md:w-auto justify-center">
                            Ya, Hapus
                        </x-danger-button>
                    </form>
                </div>
            </div>
        </x-modal>
    </div>

    {{-- Image Lightbox Modal --}}
    <div id="imageModal" class="fixed inset-0 z-50 hidden" onclick="closeModal()">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <img id="modalImage" src="" alt="Full size"
                 class="max-w-full max-h-[90vh] rounded-lg shadow-2xl transform transition-transform scale-100">
            <button class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors p-2 rounded-full bg-black/20 hover:bg-black/40">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
        function openModal(src) {
            document.getElementById('modalImage').src = src;
            const modal = document.getElementById('imageModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</x-app-layout>

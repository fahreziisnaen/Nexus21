<x-app-layout>
    <x-slot name="header">
        <!-- Mobile View -->
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Rekap Poin
                </h2>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Rekap Poin Pelanggaran Siswa
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Akumulasi poin pelanggaran seluruh siswa aktif</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Back Button --}}
            <div class="mb-4">
                <a href="{{ route('pelanggaran.index') }}"
                   class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Data Pelanggaran
                </a>
            </div>


            {{-- Filter --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5">
                    <h3 class="text-sm font-semibold text-white flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Data
                    </h3>
                </div>
                <div class="p-4">
                    <form method="GET" action="{{ route('pelanggaran.rekap') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        {{-- Nama --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Siswa</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input type="text" name="nama" value="{{ $nama ?? '' }}"
                                    placeholder="Cari nama siswa..."
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200">
                            </div>
                        </div>

                        {{-- Kelas --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kelas</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <select name="kelas"
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200 appearance-none bg-white">
                                    <option value="">Semua Kelas</option>
                                    @foreach ($kelasList as $k)
                                        <option value="{{ $k->kelas }}" {{ ($kelas ?? '') === $k->kelas ? 'selected' : '' }}>
                                            {{ $k->kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Tahun Pembelajaran Filter --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tahun Pembelajaran</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <select name="tahun_pembelajaran"
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200 appearance-none bg-white">
                                    @foreach ($tahunList as $tahun)
                                        <option value="{{ $tahun }}" {{ ($tahunPembelajaran ?? '') === $tahun ? 'selected' : '' }}>
                                            {{ $tahun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Semester Filter --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Semester</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                                <select name="semester"
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200 appearance-none bg-white">
                                    <option value="Semester Ganjil" {{ ($semesterTipe ?? '') === 'Semester Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="Semester Genap" {{ ($semesterTipe ?? '') === 'Semester Genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow transition-all duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Cari
                            </button>
                            <a href="{{ route('pelanggaran.rekap') }}"
                               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-all duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>


            {{-- Table Header (shared label) --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Daftar Poin Pelanggaran Siswa
                    </h3>
                    <span class="text-white/80 text-xs">Total: {{ $students->total() }} siswa</span>
                </div>

                {{-- ═══ MOBILE CARD VIEW ═══ --}}
                <div class="md:hidden divide-y divide-gray-100">
                    @forelse ($students as $index => $student)
                        @php
                            $totalPoin  = $student->pelanggaran_sum_poin ?? 0;
                            $totalCount = $student->pelanggaran_count ?? 0;
                            $rank       = $students->firstItem() + $index;

                            if ($totalPoin === 0) {
                                $poinClass   = 'bg-green-100 text-green-600 border-2 border-green-200';
                            } elseif ($totalPoin <= 10) {
                                $poinClass   = 'bg-yellow-100 text-yellow-700 border-2 border-yellow-300';
                            } elseif ($totalPoin <= 25) {
                                $poinClass   = 'bg-orange-100 text-orange-700 border-2 border-orange-300';
                            } else {
                                $poinClass   = 'bg-red-100 text-red-700 border-2 border-red-300';
                            }
                            $cardBg = $totalPoin > 25 ? 'bg-red-50' : 'bg-white';
                        @endphp
                        <div class="{{ $cardBg }} px-4 py-3">
                            <div class="flex items-center gap-3">
                                {{-- Rank badge --}}
                                <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-black text-sm
                                    {{ $rank <= 3 && $totalPoin > 0
                                        ? ($rank === 1 ? 'bg-yellow-400 text-yellow-900' : ($rank === 2 ? 'bg-gray-300 text-gray-700' : 'bg-amber-600 text-white'))
                                        : 'bg-gray-100 text-gray-500' }}">
                                    {{ $rank }}
                                </div>

                                {{-- Avatar --}}
                                <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center
                                    {{ $student->gender === 'Laki-laki' ? 'bg-blue-100' : 'bg-pink-100' }}">
                                    <svg class="w-4 h-4 {{ $student->gender === 'Laki-laki' ? 'text-blue-600' : 'text-pink-500' }}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>

                                {{-- Name & info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ $student->nama_lengkap }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 rounded px-1.5 py-0.5">{{ $student->kelas ?? '-' }}</span>
                                        <span class="text-xs text-gray-400">{{ $student->NIS }}</span>
                                    </div>
                                </div>

                                {{-- Poin --}}
                                <div class="shrink-0 flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full font-black text-base {{ $poinClass }}">
                                        {{ $totalPoin }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 mt-0.5">poin</span>
                                </div>
                            </div>

                            {{-- Row 2: jml pelanggaran + aksi --}}
                            <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    @if ($totalCount > 0)
                                        <span class="text-xs text-gray-400">{{ $totalCount }}x pelanggaran</span>
                                    @endif
                                </div>
                                <a href="{{ route('pelanggaran.index', ['nama_murid' => $student->nama_lengkap]) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Riwayat
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-sm font-semibold text-gray-500">Tidak ada data siswa</p>
                                <p class="text-xs text-gray-400 mt-1">Coba ubah filter pencarian.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- ═══ DESKTOP TABLE VIEW ═══ --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider w-12">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Nama Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Kelas</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider">Jml Pelanggaran</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider">Total Poin</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($students as $index => $student)
                                @php
                                    $totalPoin    = $student->pelanggaran_sum_poin ?? 0;
                                    $totalCount   = $student->pelanggaran_count ?? 0;
                                    $rank         = $students->firstItem() + $index;

                                    $rowClass = $totalPoin > 25 ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-indigo-50';
                                @endphp
                                <tr class="{{ $rowClass }} transition-colors duration-150">
                                    <td class="px-4 py-3 text-center">
                                        @if ($rank <= 3 && $totalPoin > 0)
                                            <div class="inline-flex items-center justify-center w-7 h-7 rounded-full font-black text-sm
                                                {{ $rank === 1 ? 'bg-yellow-400 text-yellow-900' : ($rank === 2 ? 'bg-gray-300 text-gray-700' : 'bg-amber-600 text-white') }}">
                                                {{ $rank }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500">{{ $rank }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                                {{ $student->gender === 'Laki-laki' ? 'bg-blue-100' : 'bg-pink-100' }}">
                                                <svg class="w-4 h-4 {{ $student->gender === 'Laki-laki' ? 'text-blue-600' : 'text-pink-500' }}"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ $student->nama_lengkap }}</p>
                                                <p class="text-xs text-gray-500">{{ $student->NIS }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block text-xs bg-indigo-100 text-indigo-700 rounded px-2 py-0.5 font-semibold">
                                            {{ $student->kelas ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($totalCount > 0)
                                            <span class="inline-flex items-center justify-center min-w-[2rem] h-7 px-2 rounded-full bg-indigo-100 text-indigo-800 text-sm font-bold border border-indigo-200">
                                                {{ $totalCount }}x
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-sm">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($totalPoin > 0)
                                            <span class="inline-flex items-center justify-center min-w-[2.5rem] h-9 px-3 rounded-full font-black text-base border-2
                                                {{ $totalPoin > 25 ? 'bg-red-100 text-red-700 border-red-300' : ($totalPoin > 10 ? 'bg-orange-100 text-orange-700 border-orange-300' : 'bg-yellow-100 text-yellow-700 border-yellow-300') }}">
                                                {{ $totalPoin }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-green-100 text-green-600 border-2 border-green-200 font-bold text-sm">
                                                0
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('pelanggaran.index', ['nama_murid' => $student->nama_lengkap]) }}"
                                           class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 hover:text-indigo-800 text-xs font-semibold rounded-lg transition-colors duration-150"
                                           title="Lihat Riwayat Pelanggaran">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Riwayat
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <p class="text-base font-semibold text-gray-500">Tidak ada data siswa</p>
                                            <p class="text-sm text-gray-400 mt-1">Coba ubah filter pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($students->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $students->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

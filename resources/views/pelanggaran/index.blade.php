<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<x-app-layout>
    <x-slot name="header">
        <!-- Mobile View -->
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Pelanggaran
                </h2>
            </div>
        </div>
        <!-- Desktop View -->
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Data Pelanggaran Siswa
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Kelola data pelanggaran dan poin siswa</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ itemToDelete: null, selectedItemLabel: '' }">

            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <div class="mb-4 sm:mb-6 flex flex-row gap-2 justify-between items-center">
                <a href="{{ route('pelanggaran.rekap') }}"
                   class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Rekap Poin Siswa
                </a>
                
                @if(Auth::user()->role !== 'Walikelas')
                <a href="{{ route('pelanggaran.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm sm:text-base font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Pelanggaran
                </a>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2.5">
                    <h3 class="text-sm font-semibold text-white flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Data
                    </h3>
                </div>
                <div class="p-4">
                    <form method="GET" action="{{ route('pelanggaran.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                @php
                                    $tglDisplay = '';
                                    if (!empty($tanggal)) {
                                        $tglDisplay = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggal)->format('d/m/Y');
                                    }
                                @endphp
                                <input id="tanggal_display" type="text" name="tanggal_display"
                                    value="{{ $tglDisplay }}" placeholder="Pilih tanggal..."
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200">
                                <input type="hidden" id="tanggal_hidden" name="tanggal" value="{{ $tanggal ?? '' }}">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Siswa</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input type="text" name="nama_murid" value="{{ $namaMurid ?? '' }}"
                                    placeholder="Cari nama siswa..."
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Jenis Pelanggaran</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <select name="jenis_pelanggaran"
                                    class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition duration-200 appearance-none bg-white">
                                    <option value="">Semua Jenis</option>
                                    @foreach ($violationTypes as $jenis)
                                        <option value="{{ $jenis->nama }}" {{ ($jenisPelanggaran ?? '') === $jenis->nama ? 'selected' : '' }}>
                                            {{ $jenis->nama }} (+{{ $jenis->poin }} poin)
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

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow transition-all duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Cari
                            </button>
                            <a href="{{ route('pelanggaran.index') }}"
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

            <!-- Mobile Card View -->
            <div class="mb-6 md:hidden">
                @forelse ($pelanggaran as $item)

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-3 overflow-hidden">
                        <div class="p-4">
                            {{-- Siswa Info --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-full shrink-0 flex items-center justify-center
                                        {{ $item->gender === 'Laki-laki' ? 'bg-blue-100' : 'bg-pink-100' }}">
                                        @if($item->gender === 'Laki-laki')
                                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ $item->nama_murid ?? '-' }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-md px-2 py-0.5">{{ $item->kelas ?? '-' }}</span>
                                            <span class="text-xs text-gray-400">{{ $item->NIS ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                                {{-- Poin Badge --}}
                                <div class="shrink-0 ml-2 flex flex-col items-center">
                                    <span class="text-xl font-black text-red-600 leading-none">+{{ $item->poin }}</span>
                                </div>
                            </div>

                            {{-- Jenis Pelanggaran --}}
                            <div class="mb-1.5">
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ $item->jenis_pelanggaran }}
                                </span>
                            </div>

                            <div class="mb-3">
                                @if(!empty(trim($item->keterangan ?? '')))
                                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-2">
                                        {{ trim($item->keterangan) }}
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400 italic">Tidak ada keterangan</p>
                                @endif
                            </div>

                            {{-- Tanggal & Waktu --}}
                            <div class="flex items-center gap-1 mb-3">
                                <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->isoFormat('D MMM Y') }}
                                    @if($item->waktu)
                                        · {{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }}
                                    @endif
                                </span>
                            </div>

                            {{-- Pencatat & Actions --}}
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-400 truncate">
                                    Oleh: <span class="font-medium text-gray-600">{{ $item->pencatat?->nama_lengkap ?? ($item->username ?? '-') }}</span>
                                </p>
                                <div class="flex items-center gap-2 shrink-0 ml-2">
                                    @if(Auth::user()->role !== 'Walikelas')
                                        <a href="{{ route('pelanggaran.show', $item->id) }}"
                                           class="w-8 h-8 inline-flex items-center justify-center rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition-colors"
                                           title="Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('pelanggaran.edit', $item->id) }}"
                                           class="w-8 h-8 inline-flex items-center justify-center rounded-xl bg-yellow-50 hover:bg-yellow-100 text-yellow-600 transition-colors"
                                           title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <button type="button" @click="itemToDelete = '{{ $item->id }}'; selectedItemLabel = '{{ addslashes($item->jenis_pelanggaran) }} dari {{ addslashes($item->nama_murid) }}'; $dispatch('open-modal', 'confirm-pelanggaran-deletion')"
                                                class="w-8 h-8 inline-flex items-center justify-center rounded-xl bg-red-50 hover:bg-red-100 text-red-500 transition-colors"
                                                title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center">
                        <div class="flex flex-col items-center text-gray-400">
                            <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm font-semibold text-gray-500">Tidak ada data pelanggaran</p>
                            <p class="text-xs text-gray-400 mt-1">Coba ubah filter pencarian.</p>
                        </div>
                    </div>
                @endforelse
                @if ($pelanggaran->hasPages())
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3 mt-1">
                        {{ $pelanggaran->links() }}
                    </div>
                @endif
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-indigo-500 to-purple-600">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Jenis Pelanggaran</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Poin</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal & Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider hidden md:table-cell">Dicatat Oleh</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($pelanggaran as $item)
                                @php
                                    $badgeClass = match($item->jenis_pelanggaran) {
                                        'Terlambat'             => 'bg-red-100 text-red-800 border border-red-200',
                                        'Seragam Tidak Lengkap' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                        'Baju Tidak Dimasukkan' => 'bg-orange-100 text-orange-800 border border-orange-200',
                                        'Mewarnai Rambut'       => 'bg-purple-100 text-purple-800 border border-purple-200',
                                        default                 => 'bg-gray-100 text-gray-800 border border-gray-200',
                                    };
                                    $iconPath = match($item->jenis_pelanggaran) {
                                        'Terlambat'             => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                                        'Seragam Tidak Lengkap' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                                        'Baju Tidak Dimasukkan' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                                        'Mewarnai Rambut'       => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                                        default                 => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                                    };
                                @endphp

                                <tr class="hover:bg-indigo-50 transition-colors duration-150" style="vertical-align: middle;">

                                {{-- Siswa --}}
                                <td class="px-4 py-3" style="vertical-align: middle;">
                                    <div class="flex items-center gap-2">
                                        <div class="bg-indigo-100 rounded-full p-1.5 shrink-0">
                                            @if($item->gender === 'Laki-laki')
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <p class="text-sm font-semibold text-gray-900">{{ $item->nama_murid ?? '-' }}</p>
                                                <span class="text-xs bg-indigo-100 text-indigo-700 rounded px-1.5 py-0.5 font-medium">
                                                    {{ $item->kelas ?? '-' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ $item->NIS ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>

                                    {{-- Jenis Pelanggaran --}}
                                    <td class="px-4 py-3" style="vertical-align: middle;">
                                        <span class="text-sm text-gray-800">{{ $item->jenis_pelanggaran }}</span>
                                    </td>

                                    {{-- Poin --}}
                                    <td class="px-4 py-3 text-center" style="vertical-align: middle;">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 border-2 border-red-300 text-red-700 font-black text-xs">
                                            +{{ $item->poin }}
                                        </span>
                                    </td>

                                    {{-- Tanggal & Waktu --}}
                                    <td class="px-4 py-3" style="vertical-align: middle;">
                                        <span class="text-sm text-gray-700 font-medium">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->isoFormat('D MMM Y') }}
                                        </span>
                                        @if($item->waktu)
                                            <span class="text-xs text-gray-400 block">{{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }}</span>
                                        @endif
                                    </td>

                                    {{-- Dicatat Oleh --}}
                                    <td class="px-4 py-3 hidden md:table-cell" style="vertical-align: middle;">
                                        <span class="text-sm text-gray-600">
                                            {{ $item->pencatat?->nama_lengkap ?? ($item->username ?? '-') }}
                                        </span>
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="px-4 py-3 text-center" style="vertical-align: middle;">
                                        <div class="flex items-center justify-center gap-1 whitespace-nowrap">
                                            @if(Auth::user()->role !== 'Walikelas')
                                                <a href="{{ route('pelanggaran.show', $item->id) }}"
                                                   class="inline-flex items-center justify-center p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 hover:text-indigo-800 transition-colors duration-150"
                                                   title="Lihat Detail">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('pelanggaran.edit', $item->id) }}"
                                                   class="inline-flex items-center justify-center p-1.5 rounded-lg bg-yellow-50 hover:bg-yellow-100 text-yellow-600 hover:text-yellow-800 transition-colors duration-150"
                                                   title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                <button type="button" @click="itemToDelete = '{{ $item->id }}'; selectedItemLabel = '{{ addslashes($item->jenis_pelanggaran) }} dari {{ addslashes($item->nama_murid) }}'; $dispatch('open-modal', 'confirm-pelanggaran-deletion')"
                                                        class="inline-flex items-center justify-center p-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-800 transition-colors duration-150"
                                                        title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="hidden md:table-row">
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-base font-semibold text-gray-500">Tidak ada data pelanggaran</p>
                                            <p class="text-sm text-gray-400 mt-1">Belum ada pelanggaran yang tercatat, atau coba ubah filter pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($pelanggaran->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $pelanggaran->links() }}
                    </div>
                @endif
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
                        Apakah Anda yakin ingin menghapus data <span class="font-semibold text-gray-800" x-text="selectedItemLabel"></span>? Data pelanggaran dan bukti foto yang telah dihapus tidak dapat dikembalikan.
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
    </div>

    {{-- Flatpickr JS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script>
        const tanggalInput  = document.getElementById('tanggal_display');
        const tanggalHidden = document.getElementById('tanggal_hidden');

        flatpickr(tanggalInput, {
            dateFormat: 'd/m/Y',
            locale: 'id',
            defaultDate: tanggalHidden.value ? (() => {
                const [y, m, d] = tanggalHidden.value.split('-');
                return new Date(y, m - 1, d);
            })() : null,
            allowInput: true,
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    const d = selectedDates[0];
                    const year  = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day   = String(d.getDate()).padStart(2, '0');
                    tanggalHidden.value = `${year}-${month}-${day}`;
                } else {
                    tanggalHidden.value = '';
                }
            }
        });
    </script>

    <style>
        tbody tr td {
            vertical-align: middle !important;
        }
    </style>
</x-app-layout>

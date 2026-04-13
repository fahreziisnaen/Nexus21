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
                    Jenis Pelanggaran
                </h2>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Manajemen Jenis Pelanggaran
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Kelola jenis pelanggaran dan poin yang berlaku</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-indigo-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            {{-- Info Banner --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-4 mb-6 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-0.5">Tentang Jenis Pelanggaran</p>
                    <p class="text-blue-700">Setiap jenis pelanggaran memiliki poin yang akan ditambahkan ke total poin siswa saat pelanggaran dicatat. Jenis yang sudah digunakan tidak dapat dihapus, tetapi nama dan poinnya dapat diubah.</p>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mt-8" x-data="{ confirmingJenisDeletion: false, itemToDelete: null, selectedItemLabel: '' }">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Daftar Jenis Pelanggaran
                        <span class="ml-2 text-white/80 text-xs font-normal">({{ $jenisList->count() }} jenis terdaftar)</span>
                    </h3>
                    <a href="{{ route('jenis-pelanggaran.create') }}"
                       class="inline-flex items-center px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-semibold rounded-lg transition-all duration-200 backdrop-blur-sm border border-white/30">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah
                    </a>
                </div>

                {{-- Mobile Card View --}}
                <div class="md:hidden divide-y divide-gray-100">
                    @forelse ($jenisList as $i => $jenis)
                        <div class="px-4 py-3 hover:bg-indigo-50/40 transition-colors duration-150">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $jenis->nama }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($jenis->kategori)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold
                                                @if($jenis->kategori === 'Ringan') bg-green-100 text-green-700
                                                @elseif($jenis->kategori === 'Sedang') bg-amber-100 text-amber-700
                                                @elseif($jenis->kategori === 'Berat') bg-red-100 text-red-700
                                                @elseif($jenis->kategori === 'Sangat Berat') bg-purple-100 text-purple-700
                                                @else bg-gray-100 text-gray-700 @endif">
                                                {{ $jenis->kategori }}
                                            </span>
                                        @endif
                                        @if($jenis->usage_count > 0)
                                            <span class="text-[11px] text-indigo-600 font-medium">Digunakan {{ $jenis->usage_count }}x</span>
                                        @else
                                            <span class="text-[11px] text-gray-400">Belum digunakan</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-black bg-red-100 text-red-700 border border-red-200">
                                        +{{ $jenis->poin }}
                                    </span>
                                    <a href="{{ route('jenis-pelanggaran.edit', $jenis->id) }}"
                                       class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-yellow-50 hover:bg-yellow-100 text-yellow-600 transition-colors"
                                       title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @if ($jenis->usage_count === 0)
                                        <button type="button" @click="itemToDelete = '{{ $jenis->id }}'; selectedItemLabel = '{{ addslashes($jenis->nama) }}'; $dispatch('open-modal', 'confirm-jenis-deletion')"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors"
                                                title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-50 text-gray-300 cursor-not-allowed" title="Tidak dapat dihapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="text-sm font-semibold text-gray-500">Belum ada jenis pelanggaran</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider w-10">No</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Nama Pelanggaran</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-indigo-800 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider w-32">Kategori</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider w-20">Poin</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider w-36">Digunakan</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-indigo-800 uppercase tracking-wider w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($jenisList as $i => $jenis)
                                <tr class="hover:bg-indigo-50 transition-colors duration-150">
                                    <td class="px-5 py-4 text-sm text-gray-500">{{ $jenisList->firstItem() + $i }}</td>

                                    <td class="px-5 py-4 align-top">
                                        <p class="text-sm font-semibold text-gray-900">{{ $jenis->nama }}</p>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        @if($jenis->deskripsi)
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $jenis->deskripsi }}</p>
                                        @else
                                            <span class="text-xs italic text-gray-400">Tidak ada deskripsi</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if($jenis->kategori)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border
                                                @if($jenis->kategori === 'Ringan') bg-green-100 text-green-700 border-green-200
                                                @elseif($jenis->kategori === 'Sedang') bg-amber-100 text-amber-700 border-amber-200
                                                @elseif($jenis->kategori === 'Berat') bg-red-100 text-red-700 border-red-200
                                                @elseif($jenis->kategori === 'Sangat Berat') bg-purple-100 text-purple-700 border-purple-200
                                                @else bg-gray-100 text-gray-700 border-gray-200 @endif">
                                                {{ $jenis->kategori }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                    
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] h-9 px-3 rounded-full font-black text-base bg-red-100 text-red-700 border-2 border-red-300">
                                            {{ $jenis->poin }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if ($jenis->usage_count > 0)
                                            <a href="{{ route('pelanggaran.index', ['jenis_pelanggaran' => $jenis->nama]) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200 hover:bg-indigo-200 transition-colors duration-150">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                {{ $jenis->usage_count }}x digunakan
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                                Belum digunakan
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('jenis-pelanggaran.edit', $jenis->id) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-lg transition-colors duration-150 border border-yellow-200"
                                               title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </a>

                                            @if ($jenis->usage_count === 0)
                                                <button type="button" @click="itemToDelete = '{{ $jenis->id }}'; selectedItemLabel = '{{ addslashes($jenis->nama) }}'; $dispatch('open-modal', 'confirm-jenis-deletion')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg transition-colors duration-150 border border-indigo-200"
                                                        title="Hapus">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 text-gray-400 text-xs font-semibold rounded-lg border border-gray-200 cursor-not-allowed"
                                                      title="Tidak dapat dihapus karena sedang digunakan">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                    Terkunci
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <p class="text-base font-semibold text-gray-500">Belum ada jenis pelanggaran</p>
                                            <p class="text-sm text-gray-400 mt-1">Klik tombol "Tambah Jenis Pelanggaran" untuk menambahkan.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Delete Confirmation Modal -->
                <x-modal name="confirm-jenis-deletion" :show="false" focusable>
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-4 mx-auto md:mx-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        
                        <h2 class="text-lg font-bold text-gray-900 text-center md:text-left">
                            Hapus Jenis Pelanggaran
                        </h2>

                        <p class="mt-2 text-sm text-gray-600 text-center md:text-left">
                            Apakah Anda yakin ingin menghapus jenis pelanggaran <span class="font-semibold text-gray-800" x-text="selectedItemLabel"></span>?
                        </p>

                        <div class="mt-6 flex flex-col-reverse md:flex-row justify-end gap-3">
                            <x-secondary-button x-on:click="$dispatch('close')" class="w-full md:w-auto justify-center">
                                Batal
                            </x-secondary-button>

                            <form :action="`/jenis-pelanggaran/${itemToDelete}`" method="POST" class="w-full md:w-auto">
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

            {{-- Pagination --}}
            @if ($jenisList->hasPages())
                <div class="mt-4">
                    {{ $jenisList->links() }}
                </div>
            @endif

            {{-- Back Link --}}
            <div class="mt-5">
                <a href="{{ route('pelanggaran.index') }}"
                   class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Data Pelanggaran
                </a>
            </div>

        </div>
    </div>
</x-app-layout>

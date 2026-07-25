<x-app-layout>
    <x-slot name="header">
        <!-- Mobile View - Left Aligned and Uppercase -->
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Naik Kelas
                </h2>
            </div>
        </div>
        
        <!-- Desktop View - Original Layout -->
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Naik Kelas
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Proses Kenaikan Kelas dan Kelulusan Siswa</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Form Naik Kelas
                    </h3>
                </div>
                <div class="p-6 text-gray-900" x-data="naikKelasHandler()">
                    <form id="naikKelasForm" method="POST" action="{{ route('naik-kelas.process') }}" @submit.prevent>
                        @csrf
                        
                        <!-- Pertanyaan: Apakah ada murid yang tinggal kelas? -->
                        <div class="mb-6">
                            <label class="block text-base font-semibold text-gray-800 mb-4">
                                Apakah ada siswa yang tinggal kelas?
                            </label>
                            <div class="flex gap-6">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="ada_murid_tetap" value="ya" id="radioYa" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <span class="ml-3 text-gray-700 font-medium text-lg group-hover:text-indigo-600">Iya</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="ada_murid_tetap" value="tidak" id="radioTidak" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <span class="ml-3 text-gray-700 font-medium text-lg group-hover:text-indigo-600">Tidak</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('ada_murid_tetap')" class="mt-2" />
                        </div>

                        <!-- Section Pilih Murid Tetap (muncul jika pilih IYA) -->
                        <div id="muridTetapSection" class="hidden mb-6">
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-yellow-800 mb-1">Pilih Siswa Tetap (Tinggal Kelas)</h4>
                                        <p class="text-xs text-yellow-700">Pilih siswa yang tidak naik kelas/lulus</p>
                                    </div>
                                    <button type="button" id="btnBukaModal" class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        Buka Daftar
                                    </button>
                                </div>
                            </div>
                            
                            <div id="selectedMuridInfo" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm text-gray-700">
                                        <span class="font-semibold text-indigo-600" id="selectedCount">0</span> siswa dipilih sebagai siswa tetap (tinggal kelas)
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Input untuk Murid Tetap -->
                        <div id="muridTetapInputs">
                            @if(old('murid_tetap'))
                                @foreach(old('murid_tetap') as $nis)
                                    <input type="hidden" name="murid_tetap[]" value="{{ $nis }}">
                                @endforeach
                            @endif
                        </div>

                        <!-- Info Proses -->
                        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 text-blue-700 px-6 py-4 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="font-semibold text-blue-900 mb-1">Informasi Proses:</p>
                                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                        <li>Semua siswa di semua kelas akan dinaikkan/luluskan</li>
                                        <li>Kelas X-* akan naik ke XI-*</li>
                                        <li>Kelas XI-* akan naik ke XII-*</li>
                                        <li>Kelas XII-* akan lulus</li>
                                        <li id="excludeInfo" class="hidden">Siswa yang dipilih sebagai siswa tetap akan tetap di kelas yang sama</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="mt-6 pt-6 border-t">
                            <button type="button" id="btnSubmit" @click="openConfirmation()"
                                class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                Proses Naik Kelas / Lulus untuk Semua Kelas
                            </button>
                        </div>
                    </form>
                    
                    <!-- Alert Modal (Untuk validasi murid tetap) -->
                    <x-modal name="alert-modal" :show="false" focusable>
                        <div class="p-6">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-4 mx-auto md:mx-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 text-center md:text-left mb-2">Peringatan</h2>
                            <p class="text-sm text-gray-600 text-center md:text-left" x-text="alertMessage"></p>
                            <div class="mt-6 flex justify-end">
                                <x-primary-button x-on:click="$dispatch('close')">
                                    Tutup
                                </x-primary-button>
                            </div>
                        </div>
                    </x-modal>

                    <!-- Confirmation Modal Tahap 1 -->
                    <x-modal name="confirm-step-1" :show="false" focusable>
                        <div class="p-6 border-t-8 border-yellow-500">
                            <h2 class="text-xl font-bold text-red-600 flex items-center mb-4">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                PERINGATAN PENTING
                            </h2>
                            <div class="text-base text-gray-700 space-y-3 whitespace-pre-line" x-html="confirmMessage"></div>
                            <div class="mt-8 flex flex-col-reverse md:flex-row justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">
                                    Batal
                                </x-secondary-button>
                                <x-danger-button x-on:click="proceedToConfirm2()">
                                    Lanjutkan Proses
                                </x-danger-button>
                            </div>
                        </div>
                    </x-modal>

                    <!-- Confirmation Modal Tahap 2 -->
                    <x-modal name="confirm-step-2" :show="false" focusable>
                        <div class="p-6 border-t-8 border-red-600">
                            <h2 class="text-xl font-bold text-red-600 flex items-center mb-4">
                                <svg class="w-6 h-6 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                KONFIRMASI AKHIR
                            </h2>
                            <p class="text-base text-gray-800 font-medium">Apakah Anda benar-benar yakin?</p>
                            <p class="text-red-600 mt-2 font-bold mb-6">Proses ini akan mempengaruhi SEMUA KELAS dan TIDAK DAPAT DIBATALKAN!</p>
                            <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">
                                    Batal
                                </x-secondary-button>
                                <x-danger-button x-on:click="submitForm()">
                                    YA, PROSES SEKARANG
                                </x-danger-button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mt-8" x-data="{}">
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Form Perubahan Kelas
                    </h3>
                </div>
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-6">
                        Ubah kelas beberapa murid aktif sekaligus dengan upload file. Fitur ini berdiri sendiri, bisa dipakai kapan saja tanpa perlu menjalankan proses Naik Kelas terlebih dahulu.
                    </p>

                    @if (session('error_perubahan_kelas'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md" role="alert">
                            <p class="font-medium">{{ session('error_perubahan_kelas') }}</p>
                        </div>
                    @endif

                    @if (session('perubahan_kelas_errors'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md" role="alert">
                            <p class="font-semibold mb-2">File berisi data yang tidak valid. Tidak ada perubahan yang disimpan:</p>
                            <ul class="text-sm list-disc list-inside space-y-1">
                                @foreach (session('perubahan_kelas_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            @if (session('perubahan_kelas_errors_remaining', 0) > 0)
                                <p class="text-sm mt-2">dan {{ session('perubahan_kelas_errors_remaining') }} error lainnya.</p>
                            @endif
                            <p class="text-sm mt-3">Perbaiki file lalu upload ulang.</p>
                        </div>
                    @endif

                    @if (session('perubahan_kelas_preview'))
                        @php $previewData = session('perubahan_kelas_preview'); @endphp
                        <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 px-6 py-4 rounded-lg">
                            <p class="font-semibold">{{ count($previewData) }} murid akan dipindah kelasnya.</p>
                            @if (session('perubahan_kelas_skipped_noop', 0) > 0)
                                <p class="text-sm mt-1">{{ session('perubahan_kelas_skipped_noop') }} baris dilewati karena kelas baru sama dengan kelas saat ini.</p>
                            @endif
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-lg mb-6">
                            <table class="w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">NIS</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">Nama</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">Kelas Lama</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase">Kelas Baru</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($previewData as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-mono">{{ $item['NIS'] }}</td>
                                            <td class="px-4 py-2 text-sm">{{ $item['nama_lengkap'] }}</td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-semibold">{{ $item['kelas_lama'] }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">{{ $item['kelas_baru'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                            <form method="POST" action="{{ route('naik-kelas.perubahan-kelas.batal') }}">
                                @csrf
                                <x-secondary-button type="submit" class="w-full md:w-auto justify-center">
                                    Batal
                                </x-secondary-button>
                            </form>
                            <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'confirm-perubahan-kelas')" class="w-full md:w-auto justify-center">
                                Konfirmasi & Simpan Perubahan
                            </x-primary-button>
                        </div>

                        <x-modal name="confirm-perubahan-kelas" :show="false" focusable>
                            <div class="p-6 border-t-8 border-yellow-500">
                                <h2 class="text-xl font-bold text-red-600 flex items-center mb-4">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    KONFIRMASI PERUBAHAN KELAS
                                </h2>
                                <p class="text-base text-gray-800 font-medium">
                                    Anda akan memindahkan {{ count($previewData) }} murid ke kelas baru sesuai tabel preview.
                                </p>
                                <p class="text-red-600 mt-2 font-bold mb-6">Proses ini tidak dapat dibatalkan!</p>
                                <div class="flex flex-col-reverse md:flex-row justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">
                                        Batal
                                    </x-secondary-button>
                                    <form method="POST" action="{{ route('naik-kelas.perubahan-kelas.proses') }}">
                                        @csrf
                                        <x-danger-button type="submit">
                                            YA, SIMPAN PERUBAHAN
                                        </x-danger-button>
                                    </form>
                                </div>
                            </div>
                        </x-modal>
                    @else
                        <div class="flex flex-col sm:flex-row gap-3 mb-4">
                            <a href="{{ route('naik-kelas.perubahan-kelas.template') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                                Download Template
                            </a>
                        </div>
                        <form method="POST" action="{{ route('naik-kelas.perubahan-kelas.preview') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label for="perubahan_kelas_file" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Upload File Perubahan Kelas (.xlsx)
                                </label>
                                <input type="file" name="file" id="perubahan_kelas_file" accept=".xlsx" required
                                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-teal-500 to-cyan-600 hover:from-teal-600 hover:to-cyan-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                                Preview Perubahan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih Murid Tetap -->
    <div id="modalMuridTetap" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 shadow-lg rounded-2xl bg-white max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Pilih Siswa Tetap (Tinggal Kelas) - Semua Kelas
                </h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Search -->
            <div class="mb-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="searchMurid" placeholder="Cari murid berdasarkan NIS, nama, atau kelas..." 
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>

            <!-- Daftar Murid -->
            <div class="flex-1 overflow-y-auto border border-gray-200 rounded-lg p-4">
                <div id="emptyState" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="mt-4 text-sm text-gray-500">Ketik nama, NIS, atau kelas untuk mencari siswa</p>
                </div>
                
                <div id="muridList" class="space-y-2 hidden">
                    @forelse($murids as $murid)
                        <label class="murid-item flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer" 
                            data-nis="{{ $murid->NIS }}" 
                            data-nama="{{ strtolower($murid->nama_lengkap) }}" 
                            data-kelas="{{ strtolower($murid->kelas) }}">
                            <input type="checkbox" name="murid_tetap[]" value="{{ $murid->NIS }}" 
                                class="murid-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-sm font-mono text-gray-600">{{ $murid->NIS }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $murid->nama_lengkap }}</span>
                                    <span class="text-xs text-gray-500">{{ $murid->gender }}</span>
                                    <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">{{ $murid->kelas }}</span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">Tidak ada siswa aktif</p>
                    @endforelse
                </div>
                
                <div id="noResults" class="hidden text-center py-8">
                    <p class="text-sm text-gray-500">Tidak ada siswa yang ditemukan</p>
                </div>
            </div>

            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Total dipilih: <span id="modalSelectedCountBottom" class="font-semibold text-indigo-600">0</span> siswa
                </div>
                <div class="flex gap-3">
                    <button type="button" id="cancelModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="button" id="saveMuridTetap" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                        Simpan Pilihan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function naikKelasHandler() {
            return {
                showAlertModal: false,
                alertMessage: '',
                showConfirm1Modal: false,
                confirmMessage: '',
                showConfirm2Modal: false,
                
                openConfirmation() {
                    const adaMuridTetap = document.querySelector('input[name="ada_murid_tetap"]:checked');
                    if (!adaMuridTetap) {
                        this.alertMessage = 'Pilih terlebih dahulu apakah ada murid yang tinggal kelas';
                        this.$dispatch('open-modal', 'alert-modal');
                        return;
                    }

                    let messageHTML = `<p>Anda akan memproses naik kelas untuk <strong>SEMUA KELAS</strong> sekaligus.</p>
<div class="mt-4"><p class="font-bold">Proses yang akan dilakukan:</p>
<ul class="list-disc pl-5 mt-2 space-y-1">
<li>Kelas X-* akan naik ke XI-*</li>
<li>Kelas XI-* akan naik ke XII-*</li>
<li>Kelas XII-* akan lulus</li>
</ul></div>`;
                    
                    if (adaMuridTetap.value === 'ya') {
                        const selectedCount = document.querySelectorAll('input[name="murid_tetap[]"]').length;
                        if (selectedCount === 0) {
                            this.alertMessage = 'Anda memilih "Iya" tetapi belum memilih murid tetap. Silakan pilih murid tetap terlebih dahulu atau pilih "Tidak".';
                            this.$dispatch('open-modal', 'alert-modal');
                            return;
                        }
                        messageHTML += `<div class="mt-4 p-3 bg-yellow-50 rounded-lg text-yellow-800"><strong>Siswa yang akan naik/lulus:</strong> Semua siswa kecuali <span class="font-bold underline">${selectedCount} siswa tetap</span>.</div>`;
                    } else {
                        messageHTML += `<div class="mt-4 p-3 bg-blue-50 rounded-lg text-blue-800">Semua siswa di semua kelas akan naik/lulus.</div>`;
                    }
                    
                    messageHTML += `<div class="mt-4 text-red-600 font-bold">⚠️ PERHATIAN: Setelah proses ini dilakukan, data TIDAK DAPAT dikembalikan!</div>`;
                    
                    this.confirmMessage = messageHTML;
                    this.$dispatch('open-modal', 'confirm-step-1');
                },
                
                proceedToConfirm2() {
                    this.$dispatch('close-modal', 'confirm-step-1');
                    setTimeout(() => {
                        this.$dispatch('open-modal', 'confirm-step-2');
                    }, 300); // slight delay to handle modal rendering nicely
                },
                
                submitForm() {
                    this.$dispatch('close-modal', 'confirm-step-2');
                    document.getElementById('naikKelasForm').submit();
                }
            }
        }

        // Enable submit button on page load if radio button is already selected
        document.addEventListener('DOMContentLoaded', function() {
            const radioYa = document.getElementById('radioYa');
            const radioTidak = document.getElementById('radioTidak');
            
            // Jika sudah ada radio yang terpilih (dari old input), enable submit button
            if (radioYa && radioYa.checked) {
                document.getElementById('btnSubmit').disabled = false;
            } else if (radioTidak && radioTidak.checked) {
                document.getElementById('btnSubmit').disabled = false;
            }
        });

        // Handle radio button selection
        document.getElementById('radioYa').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('muridTetapSection').classList.remove('hidden');
                document.getElementById('excludeInfo').classList.remove('hidden');
                // Disable submit button - harus pilih murid dulu
                document.getElementById('btnSubmit').disabled = true;
                
                // Cek apakah sudah ada murid yang dipilih sebelumnya (dari old input)
                const existingInputs = document.querySelectorAll('#muridTetapInputs input[name="murid_tetap[]"]');
                if (existingInputs.length > 0) {
                    // Jika sudah ada pilihan sebelumnya, enable tombol
                    document.getElementById('btnSubmit').disabled = false;
                    document.getElementById('selectedMuridInfo').classList.remove('hidden');
                    updateSelectedCount();
                }
            }
        });

        document.getElementById('radioTidak').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('muridTetapSection').classList.add('hidden');
                document.getElementById('excludeInfo').classList.add('hidden');
                document.getElementById('selectedMuridInfo').classList.add('hidden');
                // Clear all selections
                document.querySelectorAll('.murid-checkbox').forEach(cb => cb.checked = false);
                updateMuridTetapInputs();
                updateSelectedCount();
                // Enable submit button
                document.getElementById('btnSubmit').disabled = false;
            }
        });

        // Open modal
        document.getElementById('btnBukaModal').addEventListener('click', function() {
            // Pastikan semua checkbox tidak tercentang dulu
            document.querySelectorAll('.murid-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Sync checkbox di modal dengan hidden inputs yang sudah ada (jika ada)
            const existingInputs = document.querySelectorAll('#muridTetapInputs input[name="murid_tetap[]"]');
            const selectedNIS = Array.from(existingInputs).map(input => input.value);
            
            // Update checkbox di modal - hanya centang yang sudah dipilih sebelumnya
            if (selectedNIS.length > 0) {
                document.querySelectorAll('.murid-checkbox').forEach(checkbox => {
                    if (selectedNIS.includes(checkbox.value)) {
                        checkbox.checked = true;
                    }
                });
            }
            
            // Reset search dan tampilkan empty state
            document.getElementById('searchMurid').value = '';
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('muridList').classList.add('hidden');
            document.getElementById('noResults').classList.add('hidden');
            
            document.getElementById('modalMuridTetap').classList.remove('hidden');
            updateModalSelectedCount();
            
            // Focus ke search box
            setTimeout(() => {
                document.getElementById('searchMurid').focus();
            }, 100);
        });

        // Close modal
        document.getElementById('closeModal').addEventListener('click', closeModal);
        document.getElementById('cancelModal').addEventListener('click', closeModal);

        function closeModal() {
            document.getElementById('modalMuridTetap').classList.add('hidden');
        }

        // Search functionality
        document.getElementById('searchMurid').addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            const items = document.querySelectorAll('.murid-item');
            const emptyState = document.getElementById('emptyState');
            const muridList = document.getElementById('muridList');
            const noResults = document.getElementById('noResults');
            
            // Jika search kosong, sembunyikan semua
            if (searchTerm === '') {
                emptyState.classList.remove('hidden');
                muridList.classList.add('hidden');
                noResults.classList.add('hidden');
                return;
            }
            
            // Tampilkan list, sembunyikan empty state
            emptyState.classList.add('hidden');
            muridList.classList.remove('hidden');
            
            let visibleCount = 0;
            
            items.forEach(item => {
                const nis = item.dataset.nis.toLowerCase();
                const nama = item.dataset.nama;
                const kelas = item.dataset.kelas;
                
                if (nis.includes(searchTerm) || nama.includes(searchTerm) || kelas.includes(searchTerm)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Tampilkan "tidak ada hasil" jika tidak ada yang match
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });

        // Update count when checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('murid-checkbox')) {
                updateModalSelectedCount();
            }
        });

        // Update modal selected count
        function updateModalSelectedCount() {
            const checkboxes = document.querySelectorAll('.murid-checkbox:checked');
            const count = checkboxes.length;
            
            const countEl = document.getElementById('modalSelectedCount');
            const countElBottom = document.getElementById('modalSelectedCountBottom');
            if (countEl) countEl.textContent = count;
            if (countElBottom) countElBottom.textContent = count;
        }

        // Save murid tetap
        document.getElementById('saveMuridTetap').addEventListener('click', function() {
            closeModal();
            updateMuridTetapInputs();
            updateSelectedCount();
            // updateSelectedCount sudah handle enable/disable tombol berdasarkan jumlah murid yang dipilih
        });

        // Update hidden inputs untuk murid tetap
        function updateMuridTetapInputs() {
            const container = document.getElementById('muridTetapInputs');
            container.innerHTML = '';
            
            // Hanya ambil checkbox yang tercentang dan terlihat (tidak hidden)
            const checkedBoxes = Array.from(document.querySelectorAll('.murid-checkbox:checked')).filter(checkbox => {
                const item = checkbox.closest('.murid-item');
                return item && item.style.display !== 'none';
            });
            
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'murid_tetap[]';
                input.value = checkbox.value;
                container.appendChild(input);
            });
        }

        // Update info murid yang dipilih di form utama
        function updateSelectedCount() {
            // Gunakan hidden inputs untuk menghitung, bukan checkbox (karena checkbox mungkin tersembunyi saat search)
            const hiddenInputs = document.querySelectorAll('#muridTetapInputs input[name="murid_tetap[]"]');
            const count = hiddenInputs.length;
            const infoSection = document.getElementById('selectedMuridInfo');
            const countEl = document.getElementById('selectedCount');
            const radioYa = document.getElementById('radioYa');
            
            if (count > 0) {
                infoSection.classList.remove('hidden');
                if (countEl) countEl.textContent = count;
                // Jika pilih "Iya" dan sudah ada murid yang dipilih, enable submit button
                if (radioYa && radioYa.checked) {
                    document.getElementById('btnSubmit').disabled = false;
                }
            } else {
                infoSection.classList.add('hidden');
                // Jika pilih "Iya" tapi belum ada murid yang dipilih, disable submit button
                if (radioYa && radioYa.checked) {
                    document.getElementById('btnSubmit').disabled = true;
                }
            }
        }
    </script>
</x-app-layout>

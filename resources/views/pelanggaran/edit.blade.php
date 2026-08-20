<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<x-app-layout>
    <x-slot name="header">
        <!-- Mobile View -->
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Edit Pelanggaran
                </h2>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden md:flex md:items-center md:justify-between">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Data Pelanggaran
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl" x-data="{ cameraAlertMessage: '' }" @open-camera-alert.window="cameraAlertMessage = $event.detail.message; $dispatch('open-modal', 'camera-alert-modal')">

                {{-- Form Header --}}
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div>
                            <p class="text-white font-semibold text-base">{{ $pelanggaran->nama_murid ?? '-' }}</p>
                            <p class="text-white/80 text-sm">{{ $pelanggaran->NIS ?? '-' }} &bull; {{ $pelanggaran->kelas ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('pelanggaran.update', $pelanggaran->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Nama Siswa --}}
                        <div class="mb-5">
                            <x-input-label for="NIS" :value="__('Nama Siswa')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <select id="NIS" name="NIS"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-400 focus:ring-indigo-400 rounded-lg shadow-sm select2-murid"
                                required>
                                <option value="">-- Pilih Siswa --</option>
                                @foreach ($murids as $murid)
                                    <option value="{{ $murid->NIS }}"
                                        data-gender="{{ $murid->gender }}"
                                        data-kelas="{{ $murid->kelas }}"
                                        {{ old('NIS', $pelanggaran->NIS) == $murid->NIS ? 'selected' : '' }}>
                                        {{ $murid->nama_lengkap }} — {{ $murid->kelas }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('NIS')" class="mt-2" />
                        </div>

                        {{-- Student Info Row (auto-filled) --}}
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div>
                                <x-input-label for="gender_display" :value="__('Jenis Kelamin')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                                <x-text-input id="gender_display" class="block mt-1 w-full bg-gray-100 text-gray-600 cursor-not-allowed"
                                    type="text" readonly
                                    value="{{ old('NIS') ? '' : ($pelanggaran->gender ?? '') }}"
                                    placeholder="Otomatis terisi..." />
                            </div>
                            <div>
                                <x-input-label for="kelas_display" :value="__('Kelas')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                                <x-text-input id="kelas_display" class="block mt-1 w-full bg-gray-100 text-gray-600 cursor-not-allowed"
                                    type="text" readonly
                                    value="{{ old('NIS') ? '' : ($pelanggaran->kelas ?? '') }}"
                                    placeholder="Otomatis terisi..." />
                            </div>
                        </div>

                        {{-- Total Poin Siswa --}}
                        <div id="student-points-card" class="{{ $pelanggaran->NIS ? '' : 'hidden' }} mb-5">
                            <div class="flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3">
                                <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm text-indigo-800 font-medium">Total poin pelanggaran siswa saat ini:
                                        <span id="student-total-poin" class="font-black text-indigo-900 text-base ml-1">
                                            @if($pelanggaran->NIS)
                                                {{ \App\Models\Pelanggaran::where('NIS', $pelanggaran->NIS)->sum('poin') }}
                                            @else
                                                0
                                            @endif
                                        </span>
                                        <span class="text-xs font-normal"> poin</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Jenis Pelanggaran --}}
                        <div class="mb-5">
                            <x-input-label for="jenis_pelanggaran" :value="__('Jenis Pelanggaran')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <select id="jenis_pelanggaran" name="jenis_pelanggaran"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-400 focus:ring-indigo-400 rounded-lg shadow-sm"
                                required>
                                <option value="">-- Pilih Jenis Pelanggaran --</option>
                                @foreach ($violationTypes as $jenis)
                                    <option value="{{ $jenis->nama }}" data-poin="{{ $jenis->poin }}"
                                        {{ old('jenis_pelanggaran', $pelanggaran->jenis_pelanggaran) === $jenis->nama ? 'selected' : '' }}>
                                        {{ $jenis->nama }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('jenis_pelanggaran')" class="mt-2" />
                        </div>

                        {{-- Poin (auto-filled, readonly) --}}
                        <div class="mb-5">
                            <x-input-label for="poin_display" :value="__('Poin Pelanggaran')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <div id="poin-badge-container" class="mt-1">
                                <div id="poin-empty" class="hidden items-center gap-2 px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-gray-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Poin akan muncul setelah memilih jenis pelanggaran
                                </div>
                                <div id="poin-filled" class="flex items-center justify-between bg-indigo-50 border-2 border-indigo-300 rounded-lg px-4 py-3">
                                    <span class="text-sm font-semibold text-indigo-700">Poin yang akan ditambahkan:</span>
                                    <span id="poin-value" class="text-3xl font-black text-indigo-600">
                                        +{{ $pelanggaran->poin }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Tanggal --}}
                        <div class="mb-5">
                            <x-input-label for="tanggal" :value="__('Tanggal Pelanggaran')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            @php
                                $tanggalFormatted = old('tanggal')
                                    ? \Carbon\Carbon::parse(old('tanggal'))->format('d/m/Y')
                                    : \Carbon\Carbon::parse($pelanggaran->tanggal)->format('d/m/Y');
                                $tanggalYmd = old('tanggal', $pelanggaran->tanggal->format('Y-m-d'));
                            @endphp
                            <x-text-input id="tanggal" class="block mt-1 w-full" type="text"
                                name="tanggal_display" value="{{ $tanggalFormatted }}" placeholder="DD/MM/YYYY" required />
                            <input type="hidden" id="tanggal_hidden" name="tanggal" value="{{ $tanggalYmd }}" required />
                            <p class="mt-1 text-xs text-gray-500">Format: DD/MM/YYYY</p>
                            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                        </div>

                        {{-- Waktu --}}
                        <div class="mb-5">
                            <x-input-label for="waktu" :value="__('Waktu Pelanggaran (Opsional)')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <x-text-input id="waktu" class="block mt-1 w-full" type="text"
                                name="waktu" placeholder="HH:MM" value="{{ old('waktu', $pelanggaran->waktu) }}" />
                            <p class="mt-1 text-xs text-gray-500">Format: 24 jam (contoh: 07:30). Kosongkan jika tidak tahu jam kejadian.</p>
                            <x-input-error :messages="$errors->get('waktu')" class="mt-2" />
                        </div>

                        {{-- Keterangan --}}
                        <div class="mb-5">
                            <x-input-label for="keterangan" :value="__('Keterangan (Opsional)')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <textarea id="keterangan" name="keterangan" rows="3"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-400 focus:ring-indigo-400 rounded-lg shadow-sm text-sm"
                                placeholder="Tambahkan keterangan jika diperlukan...">{{ old('keterangan', $pelanggaran->keterangan) }}</textarea>
                            <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                        </div>

                        {{-- Bukti --}}
                        <div class="mb-6">
                            <x-input-label for="bukti" :value="__('Bukti Foto (Opsional)')" class="text-sm font-semibold text-gray-700 mb-1.5" />

                            {{-- Existing image --}}
                            @if($pelanggaran->bukti)
                                <div id="existingBukti" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-2 font-medium">Foto bukti saat ini:</p>
                                    <div class="relative inline-block">
                                        <img src="{{ asset('storage/' . $pelanggaran->bukti) }}"
                                             alt="Bukti Pelanggaran"
                                             class="h-40 w-auto rounded-lg border border-gray-300 shadow-sm object-cover">
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Upload foto baru di bawah untuk mengganti.</p>
                                </div>
                            @endif

                            {{-- Camera Modal --}}
                            <div id="cameraModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
                                <div class="bg-white rounded-lg max-w-2xl w-full p-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-800">Ambil Foto Bukti</h3>
                                        <button type="button" id="closeCamera" class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="relative bg-black rounded-lg overflow-hidden mb-4" style="aspect-ratio: 4/3;">
                                        <video id="video" autoplay playsinline class="w-full h-full object-cover"></video>
                                        <img id="capturedPreview" src="" alt="Captured" class="hidden w-full h-full object-cover">
                                        <canvas id="canvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-3 justify-center">
                                        <button type="button" id="captureBtn"
                                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Ambil Foto
                                        </button>
                                        <button type="button" id="retakeBtn"
                                            class="hidden px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                                            Ambil Ulang
                                        </button>
                                        <button type="button" id="usePhotoBtn"
                                            class="hidden px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                            Gunakan Foto Ini
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Image Preview (new upload) --}}
                            <div id="imagePreview" class="hidden mb-3">
                                <img id="previewImg" src="" alt="Preview"
                                    class="max-w-full h-auto max-h-64 rounded-lg border border-gray-300 shadow-sm">
                                <button type="button" id="removeImage"
                                    class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    Hapus Foto Baru
                                </button>
                            </div>

                            {{-- Camera / File Buttons --}}
                            <div id="cameraButtons" class="flex gap-2 mb-2">
                                <button type="button" id="openCameraBtn"
                                    class="flex-1 flex items-center justify-center px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Ambil Foto dari Kamera
                                </button>
                                <label for="bukti-gallery" class="flex-1 cursor-pointer">
                                    <input id="bukti-gallery" type="file" accept="image/*" class="hidden" />
                                    <div class="flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Pilih dari Galeri
                                    </div>
                                </label>
                            </div>

                            <input id="bukti" type="file" name="bukti" accept="image/*" class="hidden" />
                            <x-input-error :messages="$errors->get('bukti')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, GIF. Maksimal 2MB. Kosongkan jika tidak ingin mengganti foto.</p>
                        </div>

                        {{-- Signature Pad --}}
                        <div class="mb-10">
                            <x-input-label for="signature-pad" :value="__('Tanda Tangan Siswa (Opsional / Sebagai Bukti Konfirmasi)')" class="text-sm font-semibold text-gray-700 mb-1.5" />

                            @if($pelanggaran->ttd_murid)
                                <div id="existingSignature" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-2 font-medium">Tanda tangan konfirmasi saat ini:</p>
                                    <div class="bg-white border border-gray-300 rounded-lg p-2 inline-block">
                                        <img src="{{ asset('storage/' . $pelanggaran->ttd_murid) }}" alt="Tanda Tangan" class="h-24 w-auto object-contain mix-blend-multiply">
                                    </div>
                                    <div class="mt-2 text-sm">
                                        <button type="button" id="removeExistingSignature" class="text-red-500 hover:text-red-700 font-medium flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Hapus Tanda Tangan Ini
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div id="signaturePadContainer" class="{{ $pelanggaran->ttd_murid ? 'hidden' : '' }}">
                                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-2 text-center w-full">
                                    <canvas id="signature-pad" class="w-full bg-white border border-gray-200 shadow-inner rounded-md h-48 touch-none" style="touch-action: none;"></canvas>
                                    <div class="mt-2 flex justify-between items-center text-sm px-1">
                                        <span class="text-gray-500 text-xs text-left">Sign di dalam kotak menggunakan mouse atau sentuhan</span>
                                        <button type="button" id="clear-signature" class="text-red-500 hover:text-red-700 font-medium pl-2 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Bersihkan Kotak
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Biarkan kosong jika tidak ada perubahan tanda tangan.</p>
                            </div>

                            <input type="hidden" name="ttd_murid" id="ttd_murid" value="{{ $pelanggaran->ttd_murid ?? '' }}">
                            <x-input-error :messages="$errors->get('ttd_murid')" class="mt-2" />
                        </div>

                        {{-- Form Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a href="{{ route('pelanggaran.index') }}"
                               class="inline-flex items-center px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Batal
                            </a>
                            <button type="submit"
                                onclick="return processSignatureAndSubmit(this);"
                                class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>

                    <!-- Camera Error Alert Modal -->
                    <x-modal name="camera-alert-modal" :show="false" focusable>
                        <div class="p-6">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-4 mx-auto md:mx-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 text-center md:text-left mb-2">Peringatan Kamera</h2>
                            <p class="text-sm text-gray-600 text-center md:text-left" x-text="cameraAlertMessage"></p>
                            <div class="mt-6 flex justify-end">
                                <x-primary-button x-on:click="$dispatch('close')">
                                    Tutup
                                </x-primary-button>
                            </div>
                        </div>
                    </x-modal>

                </div>
            </div>
        </div>
    </div>

    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- Flatpickr JS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

    <style>
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.25rem 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 34px;
            color: #374151;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        .select2-container { width: 100% !important; }
    </style>

    <script>
        // ── Select2 ──────────────────────────────────────────────────────
        $(document).ready(function () {
            $('.select2-murid').select2({
                placeholder: '-- Pilih Siswa --',
                allowClear: true,
            });

            $('.select2-murid').on('change', function () {
                const nis = $(this).val();
                if (!nis) {
                    document.getElementById('gender_display').value = '';
                    document.getElementById('kelas_display').value  = '';
                    document.getElementById('student-points-card').classList.add('hidden');
                    return;
                }
                fetch(`/api/pelanggaran/murid/${nis}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('gender_display').value = data.gender ?? '';
                            document.getElementById('kelas_display').value  = data.kelas  ?? '';
                            document.getElementById('student-total-poin').textContent = data.total_poin ?? 0;
                            document.getElementById('student-points-card').classList.remove('hidden');
                        }
                    });
            });
        });

        // ── Jenis Pelanggaran → Poin ──────────────────────────────────────
        const jenisSelect = document.getElementById('jenis_pelanggaran');
        const poinEmptyEl = document.getElementById('poin-empty');
        const poinFilledEl = document.getElementById('poin-filled');
        const poinValueEl  = document.getElementById('poin-value');

        function updatePoinBadge() {
            const selected = jenisSelect.options[jenisSelect.selectedIndex];
            const poin     = selected ? selected.dataset.poin : null;
            if (poin) {
                poinEmptyEl.classList.add('hidden');
                poinFilledEl.classList.remove('hidden');
                poinFilledEl.classList.add('flex');
                poinValueEl.textContent = '+' + poin;
            } else {
                poinEmptyEl.classList.remove('hidden');
                poinEmptyEl.classList.add('flex');
                poinFilledEl.classList.add('hidden');
                poinFilledEl.classList.remove('flex');
            }
        }

        jenisSelect.addEventListener('change', updatePoinBadge);
        // Run on page load to show current value
        updatePoinBadge();

        // ── Flatpickr Date ────────────────────────────────────────────────
        const tanggalInput  = document.getElementById('tanggal');
        const tanggalHidden = document.getElementById('tanggal_hidden');

        flatpickr(tanggalInput, {
            dateFormat: 'd/m/Y',
            locale: 'id',
            defaultDate: tanggalHidden.value ? (() => {
                const [y, m, d] = tanggalHidden.value.split('-');
                return new Date(y, m - 1, d);
            })() : null,
            allowInput: true,
            onChange: function (selectedDates) {
                if (selectedDates.length > 0) {
                    const d     = selectedDates[0];
                    const year  = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day   = String(d.getDate()).padStart(2, '0');
                    tanggalHidden.value = `${year}-${month}-${day}`;
                } else {
                    tanggalHidden.value = '';
                }
            }
        });

        // ── Camera / File Upload ──────────────────────────────────────────
        let stream = null;

        const openCameraBtn   = document.getElementById('openCameraBtn');
        const closeCamera     = document.getElementById('closeCamera');
        const cameraModal     = document.getElementById('cameraModal');
        const video           = document.getElementById('video');
        const canvas          = document.getElementById('canvas');
        const captureBtn      = document.getElementById('captureBtn');
        const retakeBtn       = document.getElementById('retakeBtn');
        const usePhotoBtn     = document.getElementById('usePhotoBtn');
        const capturedPreview = document.getElementById('capturedPreview');
        const imagePreview    = document.getElementById('imagePreview');
        const previewImg      = document.getElementById('previewImg');
        const removeImage     = document.getElementById('removeImage');
        const cameraButtons   = document.getElementById('cameraButtons');
        const buktiInput      = document.getElementById('bukti');
        const galleryInput    = document.getElementById('bukti-gallery');

        openCameraBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                video.srcObject = stream;
                video.classList.remove('hidden');
                capturedPreview.classList.add('hidden');
                captureBtn.classList.remove('hidden');
                retakeBtn.classList.add('hidden');
                usePhotoBtn.classList.add('hidden');
                cameraModal.classList.remove('hidden');
            } catch (err) {
                window.dispatchEvent(new CustomEvent('open-camera-alert', { detail: { message: 'Tidak dapat mengakses kamera: ' + err.message }}));
            }
        });

        closeCamera.addEventListener('click', stopCamera);
        cameraModal.addEventListener('click', function (e) {
            if (e.target === cameraModal) stopCamera();
        });

        function stopCamera() {
            if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
            cameraModal.classList.add('hidden');
        }

        captureBtn.addEventListener('click', () => {
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            capturedPreview.src = canvas.toDataURL('image/jpeg', 0.9);
            capturedPreview.classList.remove('hidden');
            video.classList.add('hidden');
            captureBtn.classList.add('hidden');
            retakeBtn.classList.remove('hidden');
            usePhotoBtn.classList.remove('hidden');
        });

        retakeBtn.addEventListener('click', () => {
            capturedPreview.classList.add('hidden');
            video.classList.remove('hidden');
            captureBtn.classList.remove('hidden');
            retakeBtn.classList.add('hidden');
            usePhotoBtn.classList.add('hidden');
        });

        usePhotoBtn.addEventListener('click', () => {
            canvas.toBlob(blob => {
                const file = new File([blob], 'bukti-pelanggaran.jpg', { type: 'image/jpeg' });
                const dt   = new DataTransfer();
                dt.items.add(file);
                buktiInput.files = dt.files;
                previewImg.src   = canvas.toDataURL('image/jpeg', 0.9);
                imagePreview.classList.remove('hidden');
                cameraButtons.classList.add('hidden');
                stopCamera();
            }, 'image/jpeg', 0.9);
        });

        galleryInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const dt = new DataTransfer();
                dt.items.add(this.files[0]);
                buktiInput.files = dt.files;
                const reader = new FileReader();
                reader.onload = e => {
                    previewImg.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    cameraButtons.classList.add('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        removeImage.addEventListener('click', () => {
            buktiInput.value   = '';
            galleryInput.value = '';
            previewImg.src     = '';
            imagePreview.classList.add('hidden');
            cameraButtons.classList.remove('hidden');
        });

        // ── Signature Pad ──────────────────────────────────────────────────
        const canvasPad = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvasPad, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });

        function resizeCanvas() {
            if (document.getElementById('signaturePadContainer').classList.contains('hidden')) return;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const cssWidth  = canvasPad.offsetWidth;
            const cssHeight = canvasPad.offsetHeight;
            canvasPad.width  = cssWidth  * ratio;
            canvasPad.height = cssHeight * ratio;
            canvasPad.getContext('2d').scale(ratio, ratio);
            signaturePad.clear();
        }

        window.addEventListener('load', resizeCanvas);
        setTimeout(resizeCanvas, 300);
        window.addEventListener('resize', resizeCanvas);

        document.getElementById('removeExistingSignature')?.addEventListener('click', function() {
            document.getElementById('existingSignature').classList.add('hidden');
            document.getElementById('signaturePadContainer').classList.remove('hidden');
            document.getElementById('ttd_murid').value = 'CLEAR';
            setTimeout(resizeCanvas, 10);
        });

        document.getElementById('clear-signature').addEventListener('click', function () {
            signaturePad.clear();
            const existingHiddenVal = document.getElementById('ttd_murid').value;
            if(existingHiddenVal !== 'CLEAR') {
                document.getElementById('ttd_murid').value = '';
            }
        });

        // Fungsi untuk memproses signature sebelum submit
        function processSignatureAndSubmit(btn) {
            const ttdInput = document.getElementById('ttd_murid');
            const signatureContainer = document.getElementById('signaturePadContainer');

            if (!signaturePad.isEmpty() && signatureContainer && !signatureContainer.classList.contains('hidden')) {
                const srcCanvas = canvasPad;
                const maxW = 600;
                const scale = srcCanvas.width > maxW ? maxW / srcCanvas.width : 1;
                const w = Math.round(srcCanvas.width * scale);
                const h = Math.round(srcCanvas.height * scale);
                const off = document.createElement('canvas');
                off.width = w;
                off.height = h;
                const ctx = off.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(srcCanvas, 0, 0, srcCanvas.width, srcCanvas.height, 0, 0, w, h);
                ttdInput.value = off.toDataURL('image/png');
            }
            return true;
        }
    </script>
</x-app-layout>

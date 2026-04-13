<x-app-layout>
    <x-slot name="header">
        <!-- Mobile View -->
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Tambah Jenis
                </h2>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Tambah Jenis Pelanggaran
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Tambahkan jenis pelanggaran baru beserta poinnya</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl">

                {{-- Form Header --}}
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <p class="text-white text-sm font-medium">
                        Poin yang ditentukan di sini akan otomatis ditambahkan ke total poin siswa setiap kali pelanggaran jenis ini dicatat.
                    </p>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('jenis-pelanggaran.store') }}">
                        @csrf

                        {{-- Nama Pelanggaran --}}
                        <div class="mb-6">
                            <x-input-label for="nama" :value="__('Nama Jenis Pelanggaran')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <x-text-input
                                id="nama"
                                name="nama"
                                type="text"
                                class="block mt-1 w-full"
                                :value="old('nama')"
                                placeholder="Contoh: Membawa HP ke sekolah"
                                required
                                autofocus
                                maxlength="100"
                            />
                            <p class="mt-1.5 text-xs text-gray-500">Gunakan nama yang ringkas. Maksimal 100 karakter.</p>
                            <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                        </div>

                        {{-- Deskripsi Pelanggaran --}}
                        <div class="mb-6">
                            <x-input-label for="deskripsi" :value="__('Deskripsi Pelanggaran (Opsional)')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <textarea
                                id="deskripsi"
                                name="deskripsi"
                                rows="3"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:text-sm"
                                placeholder="Contoh: Membawa HP ke sekolah pada saat jam pelajaran tanpa izin dari guru mata pelajaran."
                                maxlength="500"
                            >{{ old('deskripsi') }}</textarea>
                            <p class="mt-1.5 text-xs text-gray-500">Penjelasan detail mengenai jenis pelanggaran ini. Maksimal 500 karakter.</p>
                            <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
                        </div>

                        {{-- Poin --}}
                        <div class="mb-6">
                            <x-input-label for="poin" :value="__('Poin Pelanggaran')" class="text-sm font-semibold text-gray-700 mb-1.5" />

                            {{-- Live preview --}}
                            <div id="poin-preview" class="mb-3 hidden">
                                <div class="flex items-center justify-between bg-red-50 border-2 border-red-300 rounded-xl px-5 py-3">
                                    <div>
                                        <p class="text-xs text-red-500 font-medium uppercase tracking-wide">Poin yang akan diberikan</p>
                                        <p class="text-sm text-red-700 font-semibold mt-0.5" id="poin-preview-label">—</p>
                                    </div>
                                    <span id="poin-preview-value" class="text-4xl font-black text-red-600">+0</span>
                                </div>
                            </div>

                            <x-text-input
                                id="poin"
                                name="poin"
                                type="number"
                                class="block mt-1 w-full"
                                :value="old('poin')"
                                placeholder="Contoh: 10"
                                required
                                min="1"
                                max="1000"
                            />
                            <p class="mt-1.5 text-xs text-gray-500">Masukkan angka antara 1 – 1000.</p>
                            <x-input-error :messages="$errors->get('poin')" class="mt-2" />
                        </div>

                        {{-- Kategori --}}
                        <div class="mb-8">
                            <x-input-label for="kategori" :value="__('Kategori Pelanggaran')" class="text-sm font-semibold text-gray-700 mb-1.5" />
                            <select id="kategori" name="kategori"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:text-sm">
                                <option value="Ringan" {{ old('kategori', 'Ringan') === 'Ringan' ? 'selected' : '' }}>Ringan (10-25)</option>
                                <option value="Sedang" {{ old('kategori') === 'Sedang' ? 'selected' : '' }}>Sedang (26-50)</option>
                                <option value="Berat"  {{ old('kategori') === 'Berat'  ? 'selected' : '' }}>Berat (51-85)</option>
                                <option value="Sangat Berat" {{ old('kategori') === 'Sangat Berat' ? 'selected' : '' }}>Sangat Berat (86-100)</option>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">Pelanggaran <strong>Berat</strong> memerlukan approval admin sebelum ikut direset.</p>
                            <x-input-error :messages="$errors->get('kategori')" class="mt-2" />
                        </div>


                        <div class="mb-8 p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Referensi Poin Default</p>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 border border-gray-200 shadow-sm">
                                    <span class="text-gray-700 font-medium">Terlambat</span>
                                    <span class="font-black text-indigo-600">10 poin</span>
                                </div>
                                <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 border border-gray-200 shadow-sm">
                                    <span class="text-gray-700 font-medium">Seragam Tidak Lengkap</span>
                                    <span class="font-black text-orange-600">5 poin</span>
                                </div>
                                <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 border border-gray-200 shadow-sm">
                                    <span class="text-gray-700 font-medium">Baju Tidak Dimasukkan</span>
                                    <span class="font-black text-orange-600">5 poin</span>
                                </div>
                                <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 border border-gray-200 shadow-sm">
                                    <span class="text-gray-700 font-medium">Mewarnai Rambut</span>
                                    <span class="font-black text-purple-600">10 poin</span>
                                </div>
                            </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a href="{{ route('jenis-pelanggaran.index') }}"
                               class="inline-flex items-center px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Batal
                            </a>
                            <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-100">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Jenis Pelanggaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const poinInput    = document.getElementById('poin');
        const namaInput    = document.getElementById('nama');
        const kategoriSelect = document.getElementById('kategori');
        const preview      = document.getElementById('poin-preview');
        const previewValue = document.getElementById('poin-preview-value');
        const previewLabel = document.getElementById('poin-preview-label');
        const previewCard  = preview.querySelector('.flex');

        function updatePreview() {
            const poin = parseInt(poinInput.value, 10);
            const nama = namaInput.value.trim();

            if (poin > 0) {
                preview.classList.remove('hidden');
                previewValue.textContent = '+' + poin;
                previewLabel.textContent = nama ? nama : '(nama belum diisi)';

                // Auto-select category and update colors
                if (poin >= 10 && poin <= 25) {
                    kategoriSelect.value = 'Ringan';
                    updatePreviewColors('Ringan');
                } else if (poin >= 26 && poin <= 50) {
                    kategoriSelect.value = 'Sedang';
                    updatePreviewColors('Sedang');
                } else if (poin >= 51 && poin <= 85) {
                    kategoriSelect.value = 'Berat';
                    updatePreviewColors('Berat');
                } else if (poin >= 86 && poin <= 100) {
                    kategoriSelect.value = 'Sangat Berat';
                    updatePreviewColors('Sangat Berat');
                } else {
                    // Outside standard ranges, just leave category as is but update card color to gray
                    updatePreviewColors('Default');
                }
            } else {
                preview.classList.add('hidden');
            }
        }

        function updatePreviewColors(category) {
            // Remove all possible color classes
            previewCard.className = 'flex items-center justify-between border-2 rounded-xl px-5 py-3 transition-colors duration-300';
            previewValue.className = 'text-4xl font-black transition-colors duration-300';
            
            const labelP = previewCard.querySelector('p:first-child');
            const subLabelP = previewCard.querySelector('p:last-child');
            
            labelP.className = 'text-xs font-medium uppercase tracking-wide transition-colors duration-300';
            subLabelP.className = 'text-sm font-semibold mt-0.5 transition-colors duration-300';

            switch(category) {
                case 'Ringan':
                    previewCard.classList.add('bg-green-50', 'border-green-300');
                    previewValue.classList.add('text-green-600');
                    labelP.classList.add('text-green-500');
                    subLabelP.classList.add('text-green-700');
                    break;
                case 'Sedang':
                    previewCard.classList.add('bg-amber-50', 'border-amber-300');
                    previewValue.classList.add('text-amber-600');
                    labelP.classList.add('text-amber-500');
                    subLabelP.classList.add('text-amber-700');
                    break;
                case 'Berat':
                    previewCard.classList.add('bg-red-50', 'border-red-300');
                    previewValue.classList.add('text-red-600');
                    labelP.classList.add('text-red-500');
                    subLabelP.classList.add('text-red-700');
                    break;
                case 'Sangat Berat':
                    previewCard.classList.add('bg-purple-50', 'border-purple-300');
                    previewValue.classList.add('text-purple-600');
                    labelP.classList.add('text-purple-500');
                    subLabelP.classList.add('text-purple-700');
                    break;
                default:
                    previewCard.className = 'flex items-center justify-between bg-gray-50 border-2 border-gray-300 rounded-xl px-5 py-3';
                    previewValue.className = 'text-4xl font-black text-gray-600';
                    labelP.className = 'text-xs text-gray-500 font-medium uppercase tracking-wide';
                    subLabelP.className = 'text-sm text-gray-700 font-semibold mt-0.5';
            }
        }

        poinInput.addEventListener('input', updatePreview);
        namaInput.addEventListener('input', updatePreview);

        // Update colors when category is manually changed
        kategoriSelect.addEventListener('change', () => {
            updatePreviewColors(kategoriSelect.value);
        });

        // Initialize on load
        if (poinInput.value > 0) {
            updatePreview();
        }
    </script>
</x-app-layout>

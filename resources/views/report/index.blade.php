<x-app-layout>
    <x-slot name="header">
        <div class="md:hidden">
            <div class="flex items-center">
                <div class="bg-white/25 backdrop-blur-md rounded-xl p-2.5 sm:p-3 mr-3 sm:mr-4 shrink-0 shadow-lg ring-2 ring-white/20">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white drop-shadow-2xl uppercase tracking-wide">
                    Laporan
                </h2>
            </div>
        </div>
        <div class="hidden md:block">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan Pelanggaran Siswa
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">Generate laporan pelanggaran per kelas atau per murid dalam format PDF</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md mb-6 flex items-center" role="alert">
                    <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 sm:px-6 py-3 sm:py-4">
                    <h3 class="text-base sm:text-lg font-semibold text-white flex items-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Generate Laporan PDF
                    </h3>
                </div>
                <div class="p-4 sm:p-6 text-gray-900" x-data="reportForm()">
                    <form method="POST" action="{{ route('report.generate') }}" id="reportForm">
                        @csrf

                        {{-- Hidden fields --}}
                        <input type="hidden" name="semester" :value="semesterValue">

                        {{-- Mode Selection --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Jenis Laporan <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="mode" value="kelas" x-model="mode" class="sr-only" required>
                                    <div :class="mode === 'kelas' ? 'border-indigo-500 bg-indigo-50 shadow-md ring-2 ring-indigo-200' : 'border-gray-200 hover:border-gray-300'"
                                         class="border-2 rounded-xl p-4 text-center transition-all duration-200">
                                        <div class="relative">
                                            <svg class="w-8 h-8 mx-auto mb-2" :class="mode === 'kelas' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <div x-show="mode === 'kelas'" x-cloak class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-600 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </div>
                                        <span class="font-semibold text-sm" :class="mode === 'kelas' ? 'text-indigo-700' : 'text-gray-700'">Per Kelas</span>
                                        <p class="text-xs mt-1" :class="mode === 'kelas' ? 'text-indigo-500' : 'text-gray-500'">Summary per kelas</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="mode" value="murid" x-model="mode" class="sr-only">
                                    <div :class="mode === 'murid' ? 'border-indigo-500 bg-indigo-50 shadow-md ring-2 ring-indigo-200' : 'border-gray-200 hover:border-gray-300'"
                                         class="border-2 rounded-xl p-4 text-center transition-all duration-200">
                                        <div class="relative">
                                            <svg class="w-8 h-8 mx-auto mb-2" :class="mode === 'murid' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <div x-show="mode === 'murid'" x-cloak class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-600 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </div>
                                        <span class="font-semibold text-sm" :class="mode === 'murid' ? 'text-indigo-700' : 'text-gray-700'">Per Murid</span>
                                        <p class="text-xs mt-1" :class="mode === 'murid' ? 'text-indigo-500' : 'text-gray-500'">Detail per murid</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Periode & Semester Selection (Shown after mode is selected) --}}
                        <div x-show="mode" x-cloak class="mb-6">
                            <div class="grid grid-cols-2 gap-4">
                                {{-- Periode (Tahun Ajaran) --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Tahun Pembelajaran <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="periode" required
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                        <option value="">Pilih Tahun Pembelajaran</option>
                                        <template x-for="p in periodeList" :key="p">
                                            <option :value="p" x-text="p" :selected="p === currentPeriode"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Semester Type (Ganjil / Genap) --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Semester <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="semesterType" required
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                        <option value="">Pilih Semester</option>
                                        <option value="1">Ganjil</option>
                                        <option value="2">Genap</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Class Selection (Shown after mode is selected) --}}
                        <div x-show="mode" x-cloak class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3" x-text="mode === 'kelas' ? 'Pilih Kelas (Tingkat)' : 'Pilih Lingkup Laporan'"></label>
                            
                            {{-- Info Notice for Per Murid --}}
                            <div x-show="mode === 'murid'" class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-blue-700 leading-tight">
                                    <strong>Catatan:</strong> Laporan detail per murid hanya akan memunculkan siswa yang memiliki catatan pelanggaran pada semester yang dipilih.
                                </p>
                            </div>

                            <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Kelompok Kelas <span class="text-red-500">*</span></label>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="t in ['X', 'XI', 'XII']" :key="t">
                                                <button type="button" @click="selectedKelasGroup = (selectedKelasGroup === t ? '' : t)"
                                                    :class="selectedKelasGroup === t ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'"
                                                    class="px-4 py-2 rounded-lg text-xs font-bold border transition-all duration-200"
                                                    x-text="'Kelas ' + t">
                                                </button>
                                            </template>
                                            
                                            {{-- Global select all only for Per Murid --}}
                                            <template x-if="mode === 'murid'">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-px h-8 bg-gray-200 mx-1 hidden sm:block"></div>
                                                    <button type="button" @click="selectedKelasList = [...kelasList]"
                                                        class="px-4 py-2 rounded-lg text-xs font-bold border border-indigo-200 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all duration-200">
                                                        Pilih Seluruh Kelas
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        {{-- For Per Kelas, just show if the level is selected --}}
                                        <div x-show="mode === 'kelas' && selectedKelasGroup" class="flex items-center gap-2 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Seluruh Kelas <span x-text="selectedKelasGroup"></span> Terpilih</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Individual Class Checkboxes only for Per Murid --}}
                                <div x-show="mode === 'murid'">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 max-h-64 overflow-y-auto p-1 border-t border-indigo-100 pt-4">
                                        <template x-for="kelas in filteredKelasList" :key="kelas">
                                            <label class="flex items-center space-x-2 cursor-pointer p-2 rounded-lg hover:bg-white hover:shadow-sm border border-transparent hover:border-indigo-100 transition group">
                                                <input type="checkbox" name="kelas_list[]" :value="kelas" x-model="selectedKelasList"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                                <span class="text-sm font-medium text-gray-600 group-hover:text-indigo-700" x-text="kelas"></span>
                                            </label>
                                        </template>
                                    </div>

                                    <div x-show="filteredKelasList.length === 0" class="text-center py-8">
                                        <p class="text-sm text-gray-400 italic" x-text="selectedKelasGroup ? 'Tidak ada kelas ditemukan untuk kelompok ' + selectedKelasGroup : 'Silakan pilih kelas terlebih dahulu.'"></p>
                                    </div>
                                </div>

                                {{-- Hidden input for Per Kelas mode to pass the level --}}
                                <template x-if="mode === 'kelas' && selectedKelasGroup">
                                    <input type="hidden" name="selected_level" :value="selectedKelasGroup">
                                </template>
                            </div>
                            
                            <div class="mt-3 flex items-center justify-between px-2" x-show="mode === 'murid'">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    Terpilih: <span class="text-indigo-600" x-text="selectedKelasList.length"></span> Kelas
                                </p>
                                <button type="button" x-show="selectedKelasList.length > 0" @click="selectedKelasList = []" 
                                    class="text-[10px] font-bold text-red-500 uppercase hover:underline">
                                    Reset Pilihan
                                </button>
                            </div>
                        </div>

                        {{-- Submit Button (only show after mode is selected) --}}
                        <div x-show="mode" x-cloak class="flex items-center justify-end gap-3 pt-4 border-t">
                            <button type="submit" :disabled="!semesterValue"
                                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Generate PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function reportForm() {
            const currentSemester = @json(\App\Helpers\SemesterHelper::current());
            const semesterList = @json($semesterList);

            // Extract unique periodes from semester list
            const periodeSet = new Set();
            semesterList.forEach(s => {
                const parts = s.split('-');
                if (parts[0]) periodeSet.add(parts[0]);
            });

            // Also add surrounding periodes for flexibility
            const currentParts = currentSemester.split('-');
            const currentPeriode = currentParts[0] || '';
            const currentType = currentParts[1] || '';

            // Add one period before and after current
            if (currentPeriode) {
                const years = currentPeriode.split('/');
                if (years.length === 2) {
                    const startYear = parseInt(years[0]);
                    // Previous period
                    periodeSet.add((startYear - 1) + '/' + startYear);
                    // Current period
                    periodeSet.add(currentPeriode);
                    // Next period
                    periodeSet.add((startYear + 1) + '/' + (startYear + 2));
                }
            }

            const periodeList = Array.from(periodeSet).sort().reverse();
            const kelasList = @json($kelasList);

            return {
                mode: '',
                selectedKelasGroup: '',
                selectedKelasList: [],
                periode: currentPeriode,
                semesterType: currentType,
                periodeList: periodeList,
                currentPeriode: currentPeriode,
                currentSemester: currentSemester,
                kelasList: kelasList,

                get filteredKelasList() {
                    if (!this.selectedKelasGroup) return [];
                    
                    return this.kelasList.filter(k => k.startsWith(this.selectedKelasGroup + '-'));
                },

                get pilihSemuaKelasFiltered() {
                    if (this.filteredKelasList.length === 0) return false;
                    return this.filteredKelasList.every(k => this.selectedKelasList.includes(k));
                },

                set pilihSemuaKelasFiltered(value) {
                    const currentFiltered = this.filteredKelasList;
                    if (value) {
                        // Tambahkan yang belum ada
                        currentFiltered.forEach(k => {
                            if (!this.selectedKelasList.includes(k)) {
                                this.selectedKelasList.push(k);
                            }
                        });
                    } else {
                        // Hapus yang ada di list filtered
                        this.selectedKelasList = this.selectedKelasList.filter(k => !currentFiltered.includes(k));
                    }
                },

                get semesterValue() {
                    if (!this.periode || !this.semesterType) return '';
                    return this.periode + '-' + this.semesterType;
                }
            };
        }
    </script>
    @endpush
</x-app-layout>

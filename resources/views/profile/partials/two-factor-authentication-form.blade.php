@php
    $totp = app(\App\Services\TotpService::class);
    $pendingSecret = session('two_factor_pending_secret');
    $setupMode = session('two_factor_setup') && $pendingSecret;
    $recoveryCodes = session('two_factor_recovery_codes');
    $enabled = $user->hasTwoFactorEnabled();
    $sisaRecovery = count($user->two_factor_recovery_codes ?? []);
@endphp

<section x-data="{ showDisable: false, showRegenerate: false }">

    {{-- Status --}}
    <div class="flex items-start gap-3 mb-6">
        <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $enabled ? 'bg-green-100' : 'bg-gray-100' }}">
            <svg class="w-5 h-5 {{ $enabled ? 'text-green-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-sm font-bold text-gray-900">Status</h4>
                @if ($enabled)
                    <span class="inline-flex items-center gap-1 text-xs font-bold bg-green-100 text-green-700 rounded-full px-2.5 py-0.5 border border-green-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-bold bg-gray-100 text-gray-600 rounded-full px-2.5 py-0.5 border border-gray-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-600 mt-1">
                @if ($enabled)
                    Login Anda diverifikasi lewat aplikasi authenticator. <span class="font-semibold text-gray-800">OTP WhatsApp sudah tidak dipakai</span> untuk akun ini.
                @else
                    Login Anda saat ini diverifikasi lewat <span class="font-semibold text-gray-800">OTP WhatsApp</span>. Aktifkan autentikasi dua faktor untuk memakai aplikasi authenticator (Google Authenticator, Authy, dan sejenisnya) yang bekerja tanpa pulsa dan tanpa sinyal.
                @endif
            </p>
        </div>
    </div>

    {{-- Recovery code: hanya tampil sekali, tepat setelah dibuat --}}
    @if ($recoveryCodes)
        <div class="mb-6 rounded-xl border-2 border-amber-300 bg-amber-50 p-4">
            <div class="flex items-start gap-2 mb-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-sm font-bold text-amber-900">Simpan recovery code ini sekarang</p>
                    <p class="text-xs text-amber-800 mt-0.5">Kode ini <span class="font-semibold">hanya ditampilkan satu kali</span>. Gunakan salah satunya untuk masuk kalau HP Anda hilang atau aplikasi authenticator terhapus. Setiap kode hanya bisa dipakai sekali.</p>
                </div>
            </div>
            <div id="recovery-codes-list" class="grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach ($recoveryCodes as $code)
                    <div class="bg-white border border-amber-200 rounded px-3 py-2 text-center tracking-wider text-gray-800">{{ $code }}</div>
                @endforeach
            </div>
            <button type="button" onclick="salinRecoveryCodes(this)"
                class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Salin semua kode
            </button>
        </div>
    @endif

    {{-- Mode setup: Modal Penyiapan 2FA --}}
    @if ($setupMode)
        <div x-data="{ showSetupModal: true }"
             x-show="showSetupModal"
             x-cloak
             x-init="$watch('showSetupModal', val => { if (val && window.renderQrCodes) setTimeout(window.renderQrCodes, 60); })"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto"
             @keydown.escape.window="document.getElementById('cancel-setup-form').submit()">

            <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full my-8 overflow-hidden transform transition-all"
                 @click.away="document.getElementById('cancel-setup-form').submit()"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                {{-- Modal Header --}}
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Aktifkan Autentikasi Dua Faktor</h3>
                            <p class="text-xs text-indigo-100">Setup aplikasi authenticator untuk akun Anda</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('two-factor.cancel') }}">
                        @csrf
                        <button type="submit" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors" title="Batal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </div>

                {{-- Modal Body --}}
                <div class="p-5 sm:p-6 space-y-5 max-h-[calc(100vh-12rem)] overflow-y-auto">
                    @if (session('error') || $errors->get('code'))
                        <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 flex items-start gap-2.5 text-red-800 text-sm">
                            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-red-900">{{ session('error') ?? $errors->first('code') }}</p>
                            </div>
                        </div>
                    @endif

                    <ol class="space-y-4 text-sm text-gray-700">
                        {{-- Langkah 1 --}}
                        <li class="flex gap-3">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                            <div>
                                <p class="font-semibold text-gray-900">Pasang aplikasi authenticator</p>
                                <p class="text-gray-600 text-xs mt-0.5">Misalnya <strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong>, atau <strong>Authy</strong> di Play Store / App Store.</p>
                            </div>
                        </li>

                        {{-- Langkah 2 --}}
                        <li class="flex gap-3">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">Scan QR code di bawah</p>
                                <div class="mt-3 flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                    <div class="shrink-0 bg-white p-3 rounded-xl border border-gray-200 shadow-sm self-center sm:self-start flex flex-col items-center">
                                        <canvas id="two-factor-qr" class="block rounded" width="200" height="200"
                                            data-qr-uri="{{ $totp->provisioningUri($pendingSecret, $user->username, $totp->issuer()) }}"
                                            data-qr-fallback="two-factor-qr-fallback"
                                            data-qr-size="200"></canvas>
                                        <p id="two-factor-qr-fallback" class="hidden text-xs text-red-600 max-w-[200px] text-center mt-2">
                                            QR code gagal dimuat. Silakan masukkan kunci di samping secara manual.
                                        </p>
                                    </div>
                                    <div class="min-w-0 flex-1 w-full">
                                        <p class="text-xs text-gray-600">Tidak bisa scan? Pilih <strong>&quot;Masukkan kunci penyiapan&quot;</strong> di aplikasi Anda, lalu ketik/salin kunci ini:</p>
                                        <div class="mt-2 bg-gray-50 border border-gray-300 rounded-lg p-2.5">
                                            <div class="font-mono text-sm tracking-wider font-semibold text-gray-800 break-all select-all">{{ $totp->formatSecretForDisplay($pendingSecret) }}</div>
                                            <div class="mt-2 pt-2 border-t border-gray-200 flex items-center justify-between">
                                                <button type="button" onclick="salinSecretKey('{{ $pendingSecret }}', this)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 rounded-md text-xs font-semibold text-gray-700 shadow-sm transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span>Salin Kunci</span>
                                                </button>
                                                <span class="text-[11px] text-gray-500">Berbasis waktu (TOTP)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        {{-- Langkah 3 --}}
                        <li class="flex gap-3">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">Masukkan kode 6 digit dari aplikasi</p>
                                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3">
                                    @csrf
                                    <div class="flex flex-col sm:flex-row gap-2.5">
                                        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus
                                            placeholder="000000"
                                            class="w-full sm:w-44 text-center tracking-[0.35em] font-mono font-bold text-xl border-gray-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-md hover:shadow-lg transition-all">
                                            Konfirmasi & Aktifkan
                                        </button>
                                    </div>
                                </form>
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>
                        </li>
                    </ol>
                </div>

                {{-- Modal Footer --}}
                <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-200 flex justify-end gap-2">
                    <form id="cancel-setup-form" method="POST" action="{{ route('two-factor.cancel') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                            Batalkan Penyiapan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @vite(['resources/js/qrcode.js'])
    @endif

    {{-- Tombol aktivasi (hanya saat 2FA belum aktif dan tidak sedang setup) --}}
    @if (! $enabled && ! $setupMode)
        <form method="POST" action="{{ route('two-factor.enable') }}">
            @csrf
            <button type="submit"
                class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Aktifkan Autentikasi Dua Faktor
            </button>
        </form>
    @endif

    {{-- Pengelolaan saat 2FA sudah aktif --}}
    @if ($enabled)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 mb-4">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Recovery code tersisa</p>
                    <p class="text-xs text-gray-600 mt-0.5">Dipakai kalau Anda kehilangan akses ke aplikasi authenticator.</p>
                </div>
                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-9 px-3 rounded-full font-black text-base border-2
                    {{ $sisaRecovery === 0 ? 'bg-red-100 text-red-700 border-red-300' : ($sisaRecovery <= 2 ? 'bg-amber-100 text-amber-700 border-amber-300' : 'bg-green-100 text-green-700 border-green-300') }}">
                    {{ $sisaRecovery }}
                </span>
            </div>

            @if ($sisaRecovery <= 2)
                <p class="text-xs text-red-600 font-medium mt-2">Recovery code Anda hampir habis. Sebaiknya buat ulang sekarang.</p>
            @endif

            <button type="button" @click="showRegenerate = ! showRegenerate; showDisable = false"
                class="mt-3 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                <span x-show="! showRegenerate">Buat ulang recovery code</span>
                <span x-show="showRegenerate" x-cloak>Batal</span>
            </button>

            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" x-show="showRegenerate" x-cloak class="mt-3 space-y-2">
                @csrf
                <p class="text-xs text-gray-600">Masukkan password Anda untuk konfirmasi. Recovery code yang lama akan langsung hangus.</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="password" name="password" required autocomplete="current-password" placeholder="Password Anda"
                        class="w-full sm:max-w-xs border-gray-300 rounded-lg shadow-sm text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Buat Ulang
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-900">Nonaktifkan autentikasi dua faktor</p>
            <p class="text-xs text-red-700 mt-0.5">Setelah dinonaktifkan, login Anda kembali memakai OTP WhatsApp.</p>

            <button type="button" @click="showDisable = ! showDisable; showRegenerate = false"
                class="mt-3 text-sm font-semibold text-red-600 hover:text-red-800">
                <span x-show="! showDisable">Nonaktifkan</span>
                <span x-show="showDisable" x-cloak>Batal</span>
            </button>

            <form method="POST" action="{{ route('two-factor.disable') }}" x-show="showDisable" x-cloak class="mt-3 space-y-2">
                @csrf
                @method('DELETE')
                <p class="text-xs text-red-700">Masukkan password Anda untuk konfirmasi.</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="password" name="password" required autocomplete="current-password" placeholder="Password Anda"
                        class="w-full sm:max-w-xs border-gray-300 rounded-lg shadow-sm text-sm focus:border-red-400 focus:ring-red-400">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Ya, Nonaktifkan
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Helper Scripts --}}
    <script>
        function salinSecretKey(secret, btn) {
            if (!secret) return;
            const span = btn.querySelector('span');
            const oriText = span ? span.textContent : 'Salin Kunci';

            const done = () => {
                if (span) span.textContent = 'Tersalin!';
                btn.classList.add('bg-green-50', 'text-green-700', 'border-green-300');
                setTimeout(() => {
                    if (span) span.textContent = oriText;
                    btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-300');
                }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(secret).then(done);
            } else {
                const ta = document.createElement('textarea');
                ta.value = secret;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(ta);
            }
        }

        function salinRecoveryCodes(btn) {
            const kode = Array.from(document.querySelectorAll('#recovery-codes-list > div'))
                .map(el => el.textContent.trim())
                .join('\n');

            const selesai = () => {
                const teksAsli = btn.innerHTML;
                btn.textContent = 'Tersalin!';
                setTimeout(() => { btn.innerHTML = teksAsli; }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(kode).then(selesai);
            } else {
                const ta = document.createElement('textarea');
                ta.value = kode;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); selesai(); } catch (e) {}
                document.body.removeChild(ta);
            }
        }
    </script>
</section>

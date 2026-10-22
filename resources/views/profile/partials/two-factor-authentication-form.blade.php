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

    {{-- Mode setup: scan QR lalu konfirmasi dengan satu kode --}}
    @if ($setupMode)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 sm:p-5">
            <h4 class="text-sm font-bold text-gray-900 mb-3">Aktifkan Autentikasi Dua Faktor</h4>

            <ol class="space-y-4 text-sm text-gray-700">
                <li class="flex gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                    <div>
                        <p class="font-semibold text-gray-900">Pasang aplikasi authenticator</p>
                        <p class="text-gray-600 mt-0.5">Misalnya Google Authenticator, Microsoft Authenticator, atau Authy &mdash; tersedia gratis di Play Store dan App Store.</p>
                    </div>
                </li>

                <li class="flex gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">Scan QR code di bawah</p>
                        <div class="mt-3 flex flex-col sm:flex-row sm:items-start gap-4">
                            <div class="shrink-0 bg-white p-3 rounded-lg border border-gray-200 shadow-sm self-center sm:self-start">
                                <canvas id="two-factor-qr" class="block" width="200" height="200"
                                    data-qr-uri="{{ $totp->provisioningUri($pendingSecret, $user->username, $totp->issuer()) }}"
                                    data-qr-fallback="two-factor-qr-fallback"
                                    data-qr-size="200"></canvas>
                                <p id="two-factor-qr-fallback" class="hidden text-xs text-red-600 max-w-[200px] text-center">
                                    QR code gagal dimuat. Silakan masukkan kunci di samping secara manual.
                                </p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-gray-600">Tidak bisa scan? Pilih <span class="font-semibold">&quot;Masukkan kunci penyiapan&quot;</span> di aplikasi, lalu ketik kunci ini:</p>
                                <div class="mt-2 bg-white border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm tracking-wider break-all text-gray-800">{{ $totp->formatSecretForDisplay($pendingSecret) }}</div>
                                <p class="text-xs text-gray-500 mt-1.5">Jenis kunci: <span class="font-semibold">Berbasis waktu (time-based)</span></p>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="flex gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">Masukkan kode 6 digit yang muncul di aplikasi</p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                            @csrf
                            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required
                                placeholder="000000"
                                class="w-full sm:w-40 text-center tracking-[0.3em] font-bold text-lg border-gray-300 rounded-lg shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                            <button type="submit"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow transition-all">
                                Aktifkan
                            </button>
                        </form>
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                </li>
            </ol>

            <form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-4 pt-4 border-t border-indigo-200">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 font-medium">Batalkan penyiapan</button>
            </form>
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

    @if ($recoveryCodes)
        <script>
            function salinRecoveryCodes(btn) {
                var kode = Array.from(document.querySelectorAll('#recovery-codes-list > div'))
                    .map(function (el) { return el.textContent.trim(); })
                    .join('\n');

                var selesai = function () {
                    var teksAsli = btn.innerHTML;
                    btn.textContent = 'Tersalin!';
                    setTimeout(function () { btn.innerHTML = teksAsli; }, 2000);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(kode).then(selesai);
                } else {
                    // Fallback untuk koneksi non-HTTPS, di mana Clipboard API tidak tersedia.
                    var ta = document.createElement('textarea');
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
    @endif
</section>

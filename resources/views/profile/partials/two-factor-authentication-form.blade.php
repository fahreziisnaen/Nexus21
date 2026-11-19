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
             x-init="$watch('showSetupModal', val => { if (val && typeof triggerQrRender === 'function') setTimeout(triggerQrRender, 60); })"
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

        @php
            try {
                echo app(\Illuminate\Foundation\Vite::class)(['resources/js/qrcode.js']);
            } catch (\Throwable $e) {
                // Mencegah crash HTTP 500 jika Vite belum selesai di-build di container server
            }
        @endphp

        <script>
            (function(){for(var v={MODE_NUMBER:1,MODE_ALPHA_NUM:2,MODE_8BIT_BYTE:4,MODE_KANJI:8},P={L:1,M:0,Q:3,H:2},T={PATTERN000:0,PATTERN001:1,PATTERN010:2,PATTERN011:3,PATTERN100:4,PATTERN101:5,PATTERN110:6,PATTERN111:7},l={glog:function(t){if(t<1)throw new Error("glog("+t+")");return l.LOG_TABLE[t]},gexp:function(t){for(;t<0;)t+=255;for(;t>=256;)t-=255;return l.EXP_TABLE[t]},EXP_TABLE:new Array(256),LOG_TABLE:new Array(256)},g=0;g<8;g++)l.EXP_TABLE[g]=1<<g;for(var g=8;g<256;g++)l.EXP_TABLE[g]=l.EXP_TABLE[g-4]^l.EXP_TABLE[g-5]^l.EXP_TABLE[g-6]^l.EXP_TABLE[g-8];for(var g=0;g<255;g++)l.LOG_TABLE[l.EXP_TABLE[g]]=g;var l=l;function B(t,e){if(t.length===void 0)throw new Error(t.length+"/"+e);for(var n=0;n<t.length&&t[n]===0;)n++;this.num=new Array(t.length-n+e);for(var r=0;r<t.length-n;r++)this.num[r]=t[r+n]}B.prototype={get:function(t){return this.num[t]},getLength:function(){return this.num.length},multiply:function(t){for(var e=new Array(this.getLength()+t.getLength()-1),n=0;n<this.getLength();n++)for(var r=0;r<t.getLength();r++)e[n+r]^=l.gexp(l.glog(this.get(n))+l.glog(t.get(r)));return new B(e,0)},mod:function(t){if(this.getLength()-t.getLength()<0)return this;for(var e=l.glog(this.get(0))-l.glog(t.get(0)),n=new Array(this.getLength()),r=0;r<this.getLength();r++)n[r]=this.get(r);for(var a=0;a<t.getLength();a++)n[a]^=l.gexp(l.glog(t.get(a))+e);return new B(n,0).mod(t)}};var B=B;function d(t,e){this.totalCount=t,this.dataCount=e}d.RS_BLOCK_TABLE=[[1,26,19],[1,26,16],[1,26,13],[1,26,9],[1,44,34],[1,44,28],[1,44,22],[1,44,16],[1,70,55],[1,70,44],[2,35,17],[2,35,13],[1,100,80],[2,50,32],[2,50,24],[4,25,9],[1,134,108],[2,67,43],[2,33,15,2,34,16],[2,33,11,2,34,12],[2,86,68],[4,43,27],[4,43,19],[4,43,15],[2,98,78],[4,49,31],[2,32,14,4,33,15],[4,39,13,1,40,14],[2,121,97],[2,60,38,2,61,39],[4,40,18,2,41,19],[4,40,14,2,41,15],[2,146,116],[3,58,36,2,59,37],[4,36,16,4,37,17],[4,36,12,4,37,13],[2,86,68,2,87,69],[4,69,43,1,70,44],[6,43,19,2,44,20],[6,43,15,2,44,16],[4,101,81],[1,80,50,4,81,51],[4,50,22,4,51,23],[3,36,12,8,37,13],[2,116,92,2,117,93],[6,58,36,2,59,37],[4,46,20,6,47,21],[7,42,14,4,43,15],[4,133,107],[8,59,37,1,60,38],[8,44,20,4,45,21],[12,33,11,4,34,12],[3,145,115,1,146,116],[4,64,40,5,65,41],[11,36,16,5,37,17],[11,36,12,5,37,13],[5,109,87,1,110,88],[5,65,41,5,66,42],[5,54,24,7,55,25],[11,36,12],[5,122,98,1,123,99],[7,73,45,3,74,46],[15,43,19,2,44,20],[3,45,15,13,46,16],[1,135,107,5,136,108],[10,74,46,1,75,47],[1,50,22,15,51,23],[2,42,14,17,43,15],[5,150,120,1,151,121],[9,69,43,4,70,44],[17,50,22,1,51,23],[2,42,14,19,43,15],[3,141,113,4,142,114],[3,70,44,11,71,45],[17,47,21,4,48,22],[9,39,13,16,40,14],[3,135,107,5,136,108],[3,67,41,13,68,42],[15,54,24,5,55,25],[15,43,15,10,44,16],[4,144,116,4,145,117],[17,68,42],[17,50,22,6,51,23],[19,46,16,6,47,17],[2,139,111,7,140,112],[17,74,46],[7,54,24,16,55,25],[34,37,13],[4,151,121,5,152,122],[4,75,47,14,76,48],[11,54,24,14,55,25],[16,45,15,14,46,16],[6,147,117,4,148,118],[6,73,45,14,74,46],[11,54,24,16,55,25],[30,46,16,2,47,17],[8,132,106,4,133,107],[8,75,47,13,76,48],[7,54,24,22,55,25],[22,45,15,13,46,16],[10,142,114,2,143,115],[19,74,46,4,75,47],[28,50,22,6,51,23],[33,46,16,4,47,17],[8,152,122,4,153,123],[22,73,45,3,74,46],[8,53,23,26,54,24],[12,45,15,28,46,16],[3,147,117,10,148,118],[3,73,45,23,74,46],[4,54,24,31,55,25],[11,45,15,31,46,16],[7,146,116,7,147,117],[21,73,45,7,74,46],[1,53,23,37,54,24],[19,45,15,26,46,16],[5,145,115,10,146,116],[19,75,47,10,76,48],[15,54,24,25,55,25],[23,45,15,25,46,16],[13,145,115,3,146,116],[2,74,46,29,75,47],[42,54,24,1,55,25],[23,45,15,28,46,16],[17,145,115],[10,74,46,23,75,47],[10,54,24,35,55,25],[19,45,15,35,46,16],[17,145,115,1,146,116],[14,74,46,21,75,47],[29,54,24,19,55,25],[11,45,15,46,46,16],[13,145,115,6,146,116],[14,74,46,23,75,47],[44,54,24,7,55,25],[59,46,16,1,47,17],[12,151,121,7,152,122],[12,75,47,26,76,48],[39,54,24,14,55,25],[22,45,15,41,46,16],[6,151,121,14,152,122],[6,75,47,34,76,48],[46,54,24,10,55,25],[2,45,15,64,46,16],[17,152,122,4,153,123],[29,74,46,14,75,47],[49,54,24,10,55,25],[24,45,15,46,46,16],[4,152,122,18,153,123],[13,74,46,32,75,47],[48,54,24,14,55,25],[42,45,15,32,46,16],[20,147,117,4,148,118],[40,75,47,7,76,48],[43,54,24,22,55,25],[10,45,15,67,46,16],[19,148,118,6,149,119],[18,75,47,31,76,48],[34,54,24,34,55,25],[20,45,15,61,46,16]],d.getRSBlocks=function(t,e){var n=d.getRsBlockTable(t,e);if(n===void 0)throw new Error("bad rs block @ typeNumber:"+t+"/errorCorrectLevel:"+e);for(var r=n.length/3,a=[],i=0;i<r;i++)for(var o=n[i*3+0],s=n[i*3+1],u=n[i*3+2],f=0;f<o;f++)a.push(new d(s,u));return a},d.getRsBlockTable=function(t,e){switch(e){case P.L:return d.RS_BLOCK_TABLE[(t-1)*4+0];case P.M:return d.RS_BLOCK_TABLE[(t-1)*4+1];case P.Q:return d.RS_BLOCK_TABLE[(t-1)*4+2];case P.H:return d.RS_BLOCK_TABLE[(t-1)*4+3];default:return}};var d=d;function A(){this.buffer=[],this.length=0}A.prototype={get:function(t){var e=Math.floor(t/8);return(this.buffer[e]>>>7-t%8&1)==1},put:function(t,e){for(var n=0;n<e;n++)this.putBit((t>>>e-n-1&1)==1)},getLengthInBits:function(){return this.length},putBit:function(t){var e=Math.floor(this.length/8);this.buffer.length<=e&&this.buffer.push(0),t&&(this.buffer[e]|=128>>>this.length%8),this.length++}};var A=A;function D(t){this.mode=v.MODE_8BIT_BYTE,this.data=t}D.prototype={getLength:function(){return this.data.length},write:function(t){for(var e=0;e<this.data.length;e++)t.put(this.data.charCodeAt(e),8)}};var D=D,h={PATTERN_POSITION_TABLE:[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,50],[6,30,54],[6,32,58],[6,34,62],[6,26,46,66],[6,26,48,70],[6,26,50,74],[6,30,54,78],[6,30,56,82],[6,30,58,86],[6,34,62,90],[6,28,50,72,94],[6,26,50,74,98],[6,30,54,78,102],[6,28,54,80,106],[6,32,58,84,110],[6,30,58,86,114],[6,34,62,90,118],[6,26,50,74,98,122],[6,30,54,78,102,126],[6,26,52,78,104,130],[6,30,56,82,108,134],[6,34,60,86,112,138],[6,30,58,86,114,142],[6,34,62,90,118,146],[6,30,54,78,102,126,150],[6,24,50,76,102,128,154],[6,28,54,80,106,132,158],[6,32,58,84,110,136,162],[6,26,54,82,110,138,166],[6,30,58,86,114,142,170]],G15:1335,G18:7973,G15_MASK:21522,getBCHTypeInfo:function(t){for(var e=t<<10;h.getBCHDigit(e)-h.getBCHDigit(h.G15)>=0;)e^=h.G15<<h.getBCHDigit(e)-h.getBCHDigit(h.G15);return(t<<10|e)^h.G15_MASK},getBCHTypeNumber:function(t){for(var e=t<<12;h.getBCHDigit(e)-h.getBCHDigit(h.G18)>=0;)e^=h.G18<<h.getBCHDigit(e)-h.getBCHDigit(h.G18);return t<<12|e},getBCHDigit:function(t){for(var e=0;t!==0;)e++,t>>>=1;return e},getPatternPosition:function(t){return h.PATTERN_POSITION_TABLE[t-1]},getMask:function(t,e,n){switch(t){case T.PATTERN000:return(e+n)%2===0;case T.PATTERN001:return e%2===0;case T.PATTERN010:return n%3===0;case T.PATTERN011:return(e+n)%3===0;case T.PATTERN100:return(Math.floor(e/2)+Math.floor(n/3))%2===0;case T.PATTERN101:return e*n%2+e*n%3===0;case T.PATTERN110:return(e*n%2+e*n%3)%2===0;case T.PATTERN111:return(e*n%3+(e+n)%2)%2===0;default:throw new Error("bad maskPattern:"+t)}},getErrorCorrectPolynomial:function(t){for(var e=new B([1],0),n=0;n<t;n++)e=e.multiply(new B([1,l.gexp(n)],0));return e},getLengthInBits:function(t,e){if(1<=e&&e<10)switch(t){case v.MODE_NUMBER:return 10;case v.MODE_ALPHA_NUM:return 9;case v.MODE_8BIT_BYTE:return 8;case v.MODE_KANJI:return 8;default:throw new Error("mode:"+t)}else if(e<27)switch(t){case v.MODE_NUMBER:return 12;case v.MODE_ALPHA_NUM:return 11;case v.MODE_8BIT_BYTE:return 16;case v.MODE_KANJI:return 10;default:throw new Error("mode:"+t)}else if(e<41)switch(t){case v.MODE_NUMBER:return 14;case v.MODE_ALPHA_NUM:return 13;case v.MODE_8BIT_BYTE:return 16;case v.MODE_KANJI:return 12;default:throw new Error("mode:"+t)}else throw new Error("type:"+e)},getLostPoint:function(t){var e=t.getModuleCount(),n=0,r=0,a=0;for(r=0;r<e;r++)for(a=0;a<e;a++){for(var i=0,o=t.isDark(r,a),s=-1;s<=1;s++)if(!(r+s<0||e<=r+s))for(var u=-1;u<=1;u++)a+u<0||e<=a+u||s===0&&u===0||o===t.isDark(r+s,a+u)&&i++;i>5&&(n+=3+i-5)}for(r=0;r<e-1;r++)for(a=0;a<e-1;a++){var f=0;t.isDark(r,a)&&f++,t.isDark(r+1,a)&&f++,t.isDark(r,a+1)&&f++,t.isDark(r+1,a+1)&&f++,(f===0||f===4)&&(n+=3)}for(r=0;r<e;r++)for(a=0;a<e-6;a++)t.isDark(r,a)&&!t.isDark(r,a+1)&&t.isDark(r,a+2)&&t.isDark(r,a+3)&&t.isDark(r,a+4)&&!t.isDark(r,a+5)&&t.isDark(r,a+6)&&(n+=40);for(a=0;a<e;a++)for(r=0;r<e-6;r++)t.isDark(r,a)&&!t.isDark(r+1,a)&&t.isDark(r+2,a)&&t.isDark(r+3,a)&&t.isDark(r+4,a)&&!t.isDark(r+5,a)&&t.isDark(r+6,a)&&(n+=40);var E=0;for(a=0;a<e;a++)for(r=0;r<e;r++)t.isDark(r,a)&&E++;var L=Math.abs(100*E/e/e-50)/5;return n+=L*10,n}},h=h;function m(t,e){this.typeNumber=t,this.errorCorrectLevel=e,this.modules=null,this.moduleCount=0,this.dataCache=null,this.dataList=[]}m.prototype={addData:function(t){var e=new D(t);this.dataList.push(e),this.dataCache=null},isDark:function(t,e){if(t<0||this.moduleCount<=t||e<0||this.moduleCount<=e)throw new Error(t+","+e);return this.modules[t][e]},getModuleCount:function(){return this.moduleCount},make:function(){if(this.typeNumber<1){var t=1;for(t=1;t<40;t++){for(var e=d.getRSBlocks(t,this.errorCorrectLevel),n=new A,r=0,a=0;a<e.length;a++)r+=e[a].dataCount;for(var i=0;i<this.dataList.length;i++){var o=this.dataList[i];n.put(o.mode,4),n.put(o.getLength(),h.getLengthInBits(o.mode,t)),o.write(n)}if(n.getLengthInBits()<=r*8)break}this.typeNumber=t}this.makeImpl(!1,this.getBestMaskPattern())},makeImpl:function(t,e){this.moduleCount=this.typeNumber*4+17,this.modules=new Array(this.moduleCount);for(var n=0;n<this.moduleCount;n++){this.modules[n]=new Array(this.moduleCount);for(var r=0;r<this.moduleCount;r++)this.modules[n][r]=null}this.setupPositionProbePattern(0,0),this.setupPositionProbePattern(this.moduleCount-7,0),this.setupPositionProbePattern(0,this.moduleCount-7),this.setupPositionAdjustPattern(),this.setupTimingPattern(),this.setupTypeInfo(t,e),this.typeNumber>=7&&this.setupTypeNumber(t),this.dataCache===null&&(this.dataCache=m.createData(this.typeNumber,this.errorCorrectLevel,this.dataList)),this.mapData(this.dataCache,e)},setupPositionProbePattern:function(t,e){for(var n=-1;n<=7;n++)if(!(t+n<=-1||this.moduleCount<=t+n))for(var r=-1;r<=7;r++)e+r<=-1||this.moduleCount<=e+r||(0<=n&&n<=6&&(r===0||r===6)||0<=r&&r<=6&&(n===0||n===6)||2<=n&&n<=4&&2<=r&&r<=4?this.modules[t+n][e+r]=!0:this.modules[t+n][e+r]=!1)},getBestMaskPattern:function(){for(var t=0,e=0,n=0;n<8;n++){this.makeImpl(!0,n);var r=h.getLostPoint(this);(n===0||t>r)&&(t=r,e=n)}return e},createMovieClip:function(t,e,n){var r=t.createEmptyMovieClip(e,n),a=1;this.make();for(var i=0;i<this.modules.length;i++)for(var o=i*a,s=0;s<this.modules[i].length;s++){var u=s*a,f=this.modules[i][s];f&&(r.beginFill(0,100),r.moveTo(u,o),r.lineTo(u+a,o),r.lineTo(u+a,o+a),r.lineTo(u,o+a),r.endFill())}return r},setupTimingPattern:function(){for(var t=8;t<this.moduleCount-8;t++)this.modules[t][6]===null&&(this.modules[t][6]=t%2===0);for(var e=8;e<this.moduleCount-8;e++)this.modules[6][e]===null&&(this.modules[6][e]=e%2===0)},setupPositionAdjustPattern:function(){for(var t=h.getPatternPosition(this.typeNumber),e=0;e<t.length;e++)for(var n=0;n<t.length;n++){var r=t[e],a=t[n];if(this.modules[r][a]===null)for(var i=-2;i<=2;i++)for(var o=-2;o<=2;o++)Math.abs(i)===2||Math.abs(o)===2||i===0&&o===0?this.modules[r+i][a+o]=!0:this.modules[r+i][a+o]=!1}},setupTypeNumber:function(t){for(var e=h.getBCHTypeNumber(this.typeNumber),n,r=0;r<18;r++)n=!t&&(e>>r&1)===1,this.modules[Math.floor(r/3)][r%3+this.moduleCount-8-3]=n;for(var a=0;a<18;a++)n=!t&&(e>>a&1)===1,this.modules[a%3+this.moduleCount-8-3][Math.floor(a/3)]=n},setupTypeInfo:function(t,e){for(var n=this.errorCorrectLevel<<3|e,r=h.getBCHTypeInfo(n),a,i=0;i<15;i++)a=!t&&(r>>i&1)===1,i<6?this.modules[i][8]=a:i<8?this.modules[i+1][8]=a:this.modules[this.moduleCount-15+i][8]=a;for(var o=0;o<15;o++)a=!t&&(r>>o&1)===1,o<8?this.modules[8][this.moduleCount-o-1]=a:o<9?this.modules[8][15-o-1+1]=a:this.modules[8][15-o-1]=a;this.modules[this.moduleCount-8][8]=!t},mapData:function(t,e){for(var n=-1,r=this.moduleCount-1,a=7,i=0,o=this.moduleCount-1;o>0;o-=2)for(o===6&&o--;;){for(var s=0;s<2;s++)if(this.modules[r][o-s]===null){var u=!1;i<t.length&&(u=(t[i]>>>a&1)===1);var f=h.getMask(e,r,o-s);f&&(u=!u),this.modules[r][o-s]=u,a--,a===-1&&(i++,a=7)}if(r+=n,r<0||this.moduleCount<=r){r-=n,n=-n;break}}}},m.PAD0=236,m.PAD1=17,m.createData=function(t,e,n){for(var r=d.getRSBlocks(t,e),a=new A,i=0;i<n.length;i++){var o=n[i];a.put(o.mode,4),a.put(o.getLength(),h.getLengthInBits(o.mode,t)),o.write(a)}for(var s=0,u=0;u<r.length;u++)s+=r[u].dataCount;if(a.getLengthInBits()>s*8)throw new Error("code length overflow. ("+a.getLengthInBits()+">"+s*8+")");for(a.getLengthInBits()+4<=s*8&&a.put(0,4);a.getLengthInBits()%8!==0;)a.putBit(!1);for(;!(a.getLengthInBits()>=s*8||(a.put(m.PAD0,8),a.getLengthInBits()>=s*8));)a.put(m.PAD1,8);return m.createBytes(a,r)},m.createBytes=function(t,e){for(var n=0,r=0,a=0,i=new Array(e.length),o=new Array(e.length),s=0;s<e.length;s++){var u=e[s].dataCount,f=e[s].totalCount-u;r=Math.max(r,u),a=Math.max(a,f),i[s]=new Array(u);for(var E=0;E<i[s].length;E++)i[s][E]=255&t.buffer[E+n];n+=u;var L=h.getErrorCorrectPolynomial(f),c=new B(i[s],L.getLength()-1),I=c.mod(L);o[s]=new Array(L.getLength()-1);for(var M=0;M<o[s].length;M++){var N=M+I.getLength()-o[s].length;o[s][M]=N>=0?I.get(N):0}}for(var b=0,C=0;C<e.length;C++)b+=e[C].totalCount;for(var k=new Array(b),O=0,_=0;_<r;_++)for(var p=0;p<e.length;p++)_<i[p].length&&(k[O++]=i[p][_]);for(var R=0;R<a;R++)for(var w=0;w<e.length;w++)R<o[w].length&&(k[O++]=o[w][R]);return k};var y=m;window.__drawQrToCanvas=function(t,e,n){if(!(!t||!e))try{var r=new m(0,1);r.addData(e),r.make();var a=r.getModuleCount();n=n||200,t.width=n,t.height=n;var i=t.getContext("2d");if(!i)return;var o=2,s=a+o*2,u=n/s;i.fillStyle="#ffffff",i.fillRect(0,0,n,n),i.fillStyle="#000000";for(var f=0;f<a;f++)for(var E=0;E<a;E++)r.isDark(f,E)&&i.fillRect(Math.round((E+o)*u),Math.round((f+o)*u),Math.ceil(u),Math.ceil(u));t.classList.remove("hidden");var L=document.getElementById(t.dataset.qrFallback);L&&L.classList.add("hidden")}catch(c){console.error("Fallback QR error:",c)}}})();

            function triggerQrRender() {
                var c = document.getElementById('two-factor-qr');
                if (!c) return;
                var uri = c.dataset.qrUri;
                if (!uri) return;
                if (window.renderQrCodes) {
                    window.renderQrCodes();
                } else if (window.__drawQrToCanvas) {
                    window.__drawQrToCanvas(c, uri, 200);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', triggerQrRender);
            } else {
                triggerQrRender();
            }
            setTimeout(triggerQrRender, 60);
            setTimeout(triggerQrRender, 200);
        </script>
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

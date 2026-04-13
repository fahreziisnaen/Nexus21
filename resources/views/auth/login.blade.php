<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS 21 - SMAN 21 KOTA SURABAYA</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    @php
        $timeLeft = 0;
        if (session('show_otp')) {
            $expiresAt = session('auth_otp_expires_at');
            if ($expiresAt) {
                // Parse and get diff in seconds
                $parsedExpiresAt = \Carbon\Carbon::parse($expiresAt);
                if ($parsedExpiresAt->isFuture()) {
                    $timeLeft = $parsedExpiresAt->diffInSeconds(now(), true); // absolute difference
                }
            } else {
                // Default to 5 minutes if it just got generated but session string failed
                $timeLeft = 300; 
            }
        }
    @endphp
<body class="bg-gradient-to-br from-indigo-500 to-purple-500 min-h-screen flex items-center justify-center p-4" 
      x-data="{ 
          showOtpModal: {{ session('show_otp') ? 'true' : 'false' }},
          timeLeft: {{ floor($timeLeft) }},
          timerInterval: null,
          startTimer() {
              if (this.timerInterval) clearInterval(this.timerInterval);
              this.timerInterval = setInterval(() => {
                  if (this.timeLeft > 0) {
                      this.timeLeft--;
                  } else {
                      clearInterval(this.timerInterval);
                      this.showOtpModal = false; // Auto close when expired
                  }
              }, 1000);
          },
          get formattedTime() {
              let m = Math.floor(this.timeLeft / 60);
              let s = this.timeLeft % 60;
              return m + ':' + (s < 10 ? '0' : '') + s;
          }
      }"
      x-init="if (showOtpModal && timeLeft > 0) startTimer(); $watch('showOtpModal', value => { if (value && timeLeft > 0) startTimer() })">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-transparent rounded-full mb-4">
                <img src="{{ asset('images/sman21surabaya.png') }}" alt="NEXUS-21" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">NEXUS 21</h1>
            <p class="text-indigo-100">Next Generation Student Discipline System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />
            
            <!-- Error Message -->
            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center" role="alert">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan username">
                    </div>
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan password">
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input id="remember_me" type="checkbox" name="remember"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                        Ingat saya
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition hover:scale-[1.02] duration-200 shadow-lg">
                    <span class="flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Masuk
                    </span>
                </button>
            </form>

            <!-- Footer Info -->
        </div>

        <!-- Copyright -->
        <div class="text-center mt-8">
            <p class="text-white text-sm">
                © {{ date('Y') }} - SMAN 21 Kota Surabaya. All rights reserved.
            </p>
        </div>
    </div>

    <!-- OTP Modal -->
    <div x-show="showOtpModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         style="display: none;">
        
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <!-- Background overlay -->
            <div x-show="showOtpModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 aria-hidden="true"></div>

            <!-- Modal panel -->
            <div x-show="showOtpModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md w-full p-8">
                
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2" id="modal-title">Verifikasi OTP</h3>
                    <p class="text-sm text-gray-500">Kode OTP telah dikirimkan ke WhatsApp Anda.</p>
                    <div class="text-sm font-semibold mt-2" :class="timeLeft > 60 ? 'text-indigo-600' : 'text-red-500'">
                        <template x-if="timeLeft > 0">
                            <span>Sisa waktu: <span x-text="formattedTime"></span></span>
                        </template>
                        <template x-if="timeLeft <= 0">
                            <span class="text-red-500">Kode OTP telah kedaluwarsa.</span>
                        </template>
                    </div>
                </div>

                <form method="POST" action="{{ route('verify-otp.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="otp" class="block text-sm font-semibold text-gray-700 mb-2">Kode OTP (6 digit)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                                </svg>
                            </div>
                            <input id="otp" type="text" name="otp" required autocomplete="one-time-code" maxlength="6" pattern="\d{6}"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200 tracking-widest text-center text-lg font-bold"
                                placeholder="••••••" x-ref="otpInput">
                        </div>
                        <x-input-error :messages="$errors->get('otp')" class="mt-2 text-center" />
                        @if(session('error'))
                            <p class="text-sm text-red-600 mt-2 text-center">{{ session('error') }}</p>
                        @endif
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Verifikasi
                        </button>
                        <button type="button" @click="showOtpModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

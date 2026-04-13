# NEXUS 21 - Sistem Data Pelanggaran Siswa

Aplikasi web berbasis Laravel untuk mengelola dan merekap data pelanggaran siswa di sekolah. Aplikasi ini dirancang untuk mencatat, memantau, dan melaporkan berbagai jenis pelanggaran siswa secara real-time dengan sistem poin, serta terintegrasi dengan notifikasi WhatsApp otomatis.

## 🚀 Fitur Utama

### 1. **Autentikasi & Manajemen User**

- Login menggunakan username dan password.
- **Role-based Access Control (RBAC)**:
    - **Admin**: Akses penuh ke seluruh sistem, termasuk Data Master, User Management, dan fitur Naik Kelas.
    - **Walikelas**: Hanya melihat data pelanggaran dan rekapan kelas sendiri, serta menerima notifikasi WA.
    - **TATIB**: Akses khusus operasional pencatatan pelanggaran, melihat laporan, tanpa akses mengubah konfigurasi sistem.

### 2. **Manajemen Data Master & Pelanggaran**

- **Sistem Poin Pelanggaran**: Mengelompokkan jenis pelanggaran (ringan, sedang, berat) beserta bobot poin masing-masing.
- **Data Murid dan Kelas**: Pengelolaan daftar siswa, kelas, dan riwayat mutasi/kenaikan kelas siswa secara massal.
- **Top 5 Statistik**: Dashboard interaktif yang menampilkan siswa dan kelas dengan pelanggaran/poin terbanyak pada bulan berjalan.

### 3. **Pencatatan Pelanggaran (Modern UX)**

- **Upload Foto dengan Progress Bar**:
    - Visualisasi progress upload (0-100%) menggunakan Axios & SweetAlert2 saat petugas memasukkan data pelanggaran.
    - **Validasi Client-side**: Mencegah upload file besar (>2MB) sebelum dikirim ke server.
- **Camera Timestamp Watermark**:
    - Fitur kamera terintegrasi di browser untuk mengambil bukti foto.
    - Otomatis memberi **Watermark Tanggal & Jam** pada hasil foto, tersinkronisasi dengan waktu pelanggaran yang diinput.
- **Warning Poin**:
    - Alert popup interaktif akan muncul saat mendata siswa yang sudah sering melanggar atau poinnya tinggi.

### 4. **Sistem Notifikasi Cerdas**

- **WhatsApp Gateway Lokal**:
    - Mengirim pesan WA otomatis ke Walikelas saat ada siswa di kelasnya yang tertangkap melanggar tata tertib.
    - **Zero Cost**: Menggunakan library `baileys` yang berjalan di server sendiri (tanpa API pihak ketiga berbayar).
    - Integrasi via script Node.js (`whatsapp-server.js`).

### 5. **Report & Export**

- **Laporan Fleksibel**: Filter data pelanggaran berdasarkan tanggal, bulan, kelas, dan jenis pelanggaran.
- **Export Semester (Excel)**:
    - Export data satu semester penuh menggunakan library `OpenSpout` untuk menghindari memory crash saat mengunduh ribuan baris data.
    - Rekap otomatis dikelompokkan berdasarkan siswa dan kelas.

## 🛠️ Teknologi Stack

- **Backend**: Laravel 12 (PHP 8.4+)
- **Frontend**: Blade Components, Tailwind CSS v4, Alpine.js
- **WhatsApp Service**: Node.js + @whiskeysockets/baileys + Express
- **Database**: MySQL/MariaDB
- **Build Tool**: Vite

## 📦 Panduan Instalasi (Development)

1. **Clone & Install Dependencies**

    ```bash
    git clone <repo-url>
    cd nexus-21
    composer install
    npm install # Pastikan Node.js versi 20+
    ```

2. **Setup Environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    _Sesuaikan konfigurasi database (`DB_DATABASE`, `DB_USERNAME`, dll) di dalam file `.env`._

3. **Database Migration**

    ```bash
    php artisan migrate:fresh --seed
    php artisan storage:link
    ```

4. **Jalankan Aplikasi (Auto-Start All Services)**
   Perintah ini akan menjalankan server Laravel, Vite untuk aset, dan WhatsApp Gateway secara bersamaan:

    ```bash
    npm run dev
    ```

5. **Koneksi WhatsApp**
    - Perhatikan terminal, akan muncul **QR Code**.
    - Scan menggunakan perangkat WhatsApp Anda (Menu "Linked Devices").
    - Setelah berhasil login, notifikasi otomatis akan mulai berjalan ketika ada pelanggaran baru dimasukkan.

## 🚀 Panduan Deployment (Production)

### 1. Build Aset Frontend

```bash
npm install
npm run build
```

### 2. Jalankan WhatsApp Gateway (Background Service)

Gunakan **PM2** agar server WA Service tetap menyala walau terminal server ditutup:

```bash
# Install PM2 Global
npm install pm2 -g

# Jalankan Service
pm2 start whatsapp-server.js --name "nexus-wa-gateway"

# Cek Log untuk Scan QR Code
pm2 logs nexus-wa-gateway
```

### 3. Konfigurasi .env Production

Pastikan endpoint diatur ke service node yang berjalan secara lokal:

```env
APP_ENV=production
APP_DEBUG=false
KETERLAMBATAN_NOTIFICATION_ENDPOINT=http://127.0.0.1:3000/send-message
```

### 4. Optimasi Laravel Cache

```bash
php artisan optimize
php artisan view:cache
```

## 📝 Struktur Folder Khusus

- `whatsapp-server.js`: Script Node.js utama penyedia Webhook & WA Gateway.
- `wa_auth/`: Folder yang otomatis mencatat sesi login WA agar Anda tidak perlu scan ulang setiap menyalakan server.
- `storage/app/public/bukti-keterlambatan/`: Tempat seluruh foto bukti pelanggaran disimpan (direferensikan via symlink).
- `docker/`: Konfigurasi kontainer docker untuk environment terisolasi.

## 👥 Kredit

Dikembangkan dengan ❤️ oleh **EXACONET**.

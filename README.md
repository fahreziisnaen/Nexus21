# NEXUS 21 — Sistem Data Pelanggaran Siswa

Aplikasi web Laravel untuk mencatat, merekap, dan melaporkan pelanggaran tata tertib siswa dengan sistem poin. Dikembangkan untuk SMAN 21 Surabaya, terintegrasi dengan WhatsApp gateway lokal untuk notifikasi walikelas dan verifikasi login.

---

## ✨ Fitur Utama

### Autentikasi dua tahap

Login **selalu** dua tahap: password dulu, lalu verifikasi. Metode tahap kedua dipilih otomatis per user:

- **OTP WhatsApp** — default untuk semua user. Kode 6 digit dikirim ke nomor WA user, berlaku 5 menit.
- **Aplikasi authenticator (2FA/TOTP)** — kalau user mengaktifkannya sendiri dari halaman Profile. Begitu aktif, **OTP WhatsApp dimatikan** untuk akun itu.

Keduanya dibatasi 5 kali percobaan salah sebelum sesi login dibatalkan.

### Pencatatan pelanggaran

- Poin dihitung otomatis dari jenis pelanggaran yang dipilih.
- **Bukti foto** lewat kamera browser, dengan **watermark tanggal & jam** otomatis, atau unggah dari galeri.
- **Tanda tangan siswa** langsung di layar (signature pad) sebagai bukti konfirmasi.
- Setiap catatan menyimpan *snapshot* nama, kelas, dan gender siswa saat kejadian — jadi riwayat tidak ikut berubah kalau siswa pindah kelas.
- Notifikasi WhatsApp otomatis ke walikelas siswa bersangkutan.

### Tahun ajaran & semester

Setiap pelanggaran otomatis dicap semester saat dicatat (Jul–Des = Ganjil, Jan–Jun = Genap). Rekap poin dan laporan selalu dibatasi per semester, sehingga **poin siswa ter-reset sendiri setiap pergantian semester/tahun ajaran** tanpa perlu tindakan manual.

### Data master & kenaikan kelas

- Kelola user, kelas, siswa, dan jenis pelanggaran beserta bobot poinnya.
- **Import siswa** massal dari Excel (.xlsx).
- **Naik kelas** otomatis (X → XI → XII → Lulus), dengan opsi menahan siswa tertentu.
- **Perubahan kelas** massal lewat unggah Excel, dengan pratinjau dan validasi kapasitas kelas sebelum disimpan.

### Laporan

- **PDF per kelas** — rekap seluruh siswa satu kelas dalam satu semester.
- **PDF per murid** — rincian pelanggaran tiap siswa, lengkap dengan rekomendasi penanganan berdasarkan total poin (Ringan → Dikembalikan ke Orang Tua).

### Dashboard

Statistik hari ini dan bulan berjalan, Top 5 siswa dan Top 5 kelas dengan poin tertinggi, serta aktivitas terbaru — semuanya otomatis menyesuaikan hak akses user yang login.

---

## 👥 Hak Akses

| Menu | Admin | TATIB | Walikelas |
|---|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ (kelas sendiri) |
| Catat / ubah / hapus pelanggaran | ✅ | ✅ | ❌ |
| Lihat daftar & rekap pelanggaran | ✅ | ✅ | ✅ (kelas sendiri) |
| Laporan PDF | ✅ | ✅ | ✅ (kelas sendiri) |
| Data master (siswa, kelas, jenis pelanggaran) | ✅ | ✅ | ❌ |
| User management | ✅ | ✅ (kecuali Admin) | ❌ |
| Naik kelas & perubahan kelas | ✅ | ❌ | ❌ |

Walikelas hanya bisa **melihat**; seluruh pencatatan dilakukan petugas TATIB atau Admin.

---

## 🛠️ Teknologi

| Komponen | Versi |
|---|---|
| PHP | 8.2+ |
| Laravel | 12 |
| Node.js | 20+ |
| Database | MySQL 8.0 |
| Frontend | Blade, Tailwind CSS 3, Alpine.js, Vite 7 |
| PDF | barryvdh/laravel-dompdf |
| Excel | OpenSpout |
| WhatsApp | Node.js + @whiskeysockets/baileys + Express |

Autentikasi TOTP diimplementasikan langsung di `app/Services/TotpService.php` (RFC 6238) — tanpa dependency Composer tambahan, dan kebenarannya diikat ke test vector resmi RFC.

---

## 🐳 Menjalankan dengan Docker (disarankan)

Cara ini yang dipakai di produksi. Panduan lengkap ada di **[DOCKER.md](DOCKER.md)**; ringkasnya:

```bash
cp .env.docker.example .env.docker      # sesuaikan DB_PASSWORD dan WHATSAPP_SHARED_SECRET
docker compose up -d --build
```

Container `app` akan otomatis membuat `.env`, meng-generate `APP_KEY`, menunggu database siap, dan **menjalankan migrasi** setiap kali start. Aplikasi tersedia di `http://localhost:8000`.

Isi data awal (sekali saja):

```bash
docker compose exec app php artisan db:seed
```

Sambungkan WhatsApp — scan QR yang muncul di log:

```bash
docker compose logs -f whatsapp
```

### Setelah menarik perubahan baru

```bash
git pull
docker compose exec app npm run build    # wajib kalau ada perubahan tampilan
docker compose restart app               # menjalankan migrasi + membangun ulang cache
```

> `public/build` adalah named volume yang hanya diisi saat pertama kali dibuat. `docker compose up --build` **tidak** memperbaruinya — CSS/JS baru hanya masuk lewat `npm run build` di dalam container.

---

## 💻 Menjalankan lokal (development)

```bash
git clone <repo-url> && cd nexus-21
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Sesuaikan `.env` — selain kredensial database, **tambahkan baris ini** karena nilai bawaannya menunjuk ke nama service Docker:

```env
WHATSAPP_HOST=http://localhost:3000
WHATSAPP_SHARED_SECRET=isi-dengan-string-acak-yang-sama-di-kedua-sisi
```

Lalu:

```bash
php artisan migrate --seed
php artisan storage:link
npm run dev          # menjalankan Vite + server Laravel + WhatsApp gateway sekaligus
```

Akun bawaan dari seeder: `admin` / `admin123`, `tatib` / `tatib123`, `wali_x2` / `password`. **Ganti seluruh password ini sebelum dipakai sungguhan.**

---

## 🔐 Autentikasi Dua Faktor (2FA)

### Mengaktifkan

**Profile → Autentikasi Dua Faktor → Aktifkan** → scan QR dengan Google Authenticator, Authy, atau Microsoft Authenticator → masukkan kode 6 digit.

Tidak bisa scan? Kunci penyiapan ditampilkan di sebelah QR untuk dimasukkan manual (pilih tipe **time-based**).

Setelah aktif, **8 recovery code** ditampilkan **satu kali**. Simpan di tempat aman — itu satu-satunya jalan masuk kalau HP hilang. Sisa kode terpantau di halaman Profile dan bisa dibuat ulang kapan saja.

### Kalau user kehilangan HP

1. **Masih punya recovery code** → login, pilih "Pakai recovery code", lalu daftarkan HP baru.
2. **Recovery code ikut hilang** → Admin mereset lewat **Users → Edit user → Reset autentikasi dua faktor**. User kembali memakai OTP WhatsApp.

### Kalau Admin sendiri yang terkunci

TATIB sengaja **tidak** diizinkan mereset 2FA milik Admin, supaya akun tertinggi tidak bisa dibajak. Jadi kalau Admin kehilangan HP *dan* recovery code-nya:

```bash
docker compose exec app php artisan tinker --execute="App\Models\User::where('username','admin')->first()->disableTwoFactor(); echo 'OK';"
```

> **Pencegahan:** buat **dua akun Admin** dan jangan aktifkan 2FA di keduanya pada hari yang sama. Dengan begitu skenario terkunci total tidak pernah terjadi.

### Catatan keamanan

Secret dan recovery code disimpan **terenkripsi** memakai `APP_KEY`. Kalau `APP_KEY` berubah, seluruh 2FA tidak bisa didekripsi lagi dan semua user harus direset. Jaga agar file `.env` produksi tidak terhapus atau tertimpa.

---

## 🧪 Testing

```bash
php artisan test
```

Test berjalan di SQLite in-memory dan tidak butuh database terpisah. Cakupan khusus 2FA ada di `tests/Unit/TotpServiceTest.php` (kesesuaian dengan RFC 6238) dan `tests/Feature/TwoFactorAuthenticationTest.php` (alur login, recovery code, hak akses reset).

> Sebagian test bawaan Laravel Breeze untuk fitur yang sudah dinonaktifkan di aplikasi ini (registrasi mandiri, reset password, verifikasi email) masih gagal. Itu kondisi lama dan tidak memengaruhi aplikasi.

---

## 📁 Struktur Penting

| Path | Isi |
|---|---|
| `app/Services/TotpService.php` | Implementasi TOTP (RFC 6238) untuk 2FA |
| `app/Helpers/SemesterHelper.php` | Penentuan tahun ajaran & semester aktif |
| `whatsapp-server.js` | WhatsApp gateway (Baileys + Express, port 3000) |
| `wa_auth/` | Sesi login WhatsApp — jangan dihapus, nanti harus scan QR lagi |
| `storage/app/public/bukti-pelanggaran/` | Foto bukti pelanggaran |
| `storage/app/public/ttd-pelanggaran/` | Tanda tangan siswa |
| `docker/` | Dockerfile pendukung, konfigurasi Nginx, entrypoint |
| `README_APLIKASI.md` | Panduan penggunaan untuk operator (non-teknis) |

---

## 👥 Kredit

Dikembangkan dengan ❤️ oleh **EXACONET**.

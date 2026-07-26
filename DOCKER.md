# 🐳 Menjalankan NEXUS-21 dengan Docker

Tidak perlu install PHP, Composer, atau Node.js di laptop.

---

## 🚀 Setup Awal (Sekali saja setelah clone)

### 1. Copy file environment

```bash
cp .env.docker.example .env.docker
```

> Edit `.env.docker` sesuai kebutuhan (database name, password, dsb.) sebelum menjalankan Docker.
>
> ⚠️ **Wajib diisi:** `WHATSAPP_SHARED_SECRET` — generate string acak yang panjang (mis. `openssl rand -hex 32`) dan isikan di `.env.docker`. Tanpa ini, login OTP lewat WhatsApp akan selalu gagal dan tidak ada yang bisa login.

### 2. Build & jalankan semua container

```bash
docker compose up -d --build
```

> Proses ini memakan waktu beberapa menit saat pertama kali karena mengunduh image dan menginstall dependencies.
> Entrypoint akan otomatis: generate `APP_KEY`, migrasi database, fix permissions.

### 3. Buat user Admin pertama

```bash
docker compose exec app php artisan tinker --execute="
\$password = \Illuminate\Support\Str::random(16);
\App\Models\User::create([
    'username'      => 'admin',
    'nama_lengkap'  => 'Administrator',
    'nomor_telepon' => '08123456789',
    'password'      => bcrypt(\$password),
    'role'          => 'Admin'
]);
echo 'Admin password: ' . \$password . PHP_EOL;
"
```

> ⚠️ **Catat password yang ditampilkan** — ini satu-satunya kesempatan untuk melihatnya. Segera login dan ganti password ini lewat halaman profil.

### 4. Buka di browser

```
http://localhost:8000
```

Login dengan **username** `admin` dan password yang dicatat di langkah 3.

---

## ▶️ Penggunaan Sehari-hari

```bash
# Nyalakan
docker compose up -d

# Matikan (data TETAP ada)
docker compose down

# Lihat log
docker compose logs -f app
```

---

## ⚠️ Peringatan

| Perintah                 | Efek                                                 |
| ------------------------ | ---------------------------------------------------- |
| `docker compose down`    | ✅ Data aman                                         |
| `docker compose down -v` | ❌ **Semua data terhapus** (database, vendor, build) |

---

## 🛠️ Perintah Artisan via Docker

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan cache:clear
```

## 📡 Services

| Service         | URL                       |
| --------------- | ------------------------- |
| Aplikasi        | http://localhost:8000     |

> **phpMyAdmin, MySQL, dan WhatsApp server tidak lagi dipublikasikan ke host** untuk keamanan — semuanya hanya bisa diakses lewat jaringan Docker internal (`nexus_network`). Untuk mengakses database secara manual saat development:
> - `docker compose exec db mysql -u root -p` (langsung dari terminal, tanpa expose port apapun)
> - Kalau benar-benar butuh phpMyAdmin sementara: `docker compose run --rm --publish 127.0.0.1:8080:80 phpmyadmin` (hanya bisa diakses dari mesin ini sendiri, lewat `http://localhost:8080`) — jangan publish ke `0.0.0.0` atau permanen ke internet.

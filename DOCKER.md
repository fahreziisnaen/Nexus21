# 🐳 Menjalankan NEXUS-21 dengan Docker

Tidak perlu install PHP, Composer, atau Node.js di laptop.

---

## 🚀 Setup Awal (Sekali saja setelah clone)

### 1. Copy file environment

```bash
cp .env.docker.example .env.docker
```

> Edit `.env.docker` sesuai kebutuhan (database name, password, dsb.) sebelum menjalankan Docker.

### 2. Build & jalankan semua container

```bash
docker compose up -d --build
```

> Proses ini memakan waktu beberapa menit saat pertama kali karena mengunduh image dan menginstall dependencies.
> Entrypoint akan otomatis: generate `APP_KEY`, migrasi database, fix permissions.

### 3. Buat user Admin pertama

```bash
docker compose exec app php artisan tinker --execute="
\App\Models\User::create([
    'username'      => 'admin',
    'nama_lengkap'  => 'Administrator',
    'nomor_telepon' => '08123456789',
    'password'      => bcrypt('password'),
    'role'          => 'Admin'
]);
"
```

### 4. Buka di browser

```
http://localhost:8000
```

Login: **username** `admin` / **password** `password`

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
| phpMyAdmin      | http://localhost:8080     |
| WhatsApp server | http://localhost:3000     |
| MySQL           | localhost:3306            |

> **phpMyAdmin login:** Username `root`, Password sesuai `DB_PASSWORD` di `.env.docker`

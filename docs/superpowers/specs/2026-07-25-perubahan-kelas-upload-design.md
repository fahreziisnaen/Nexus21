# Perubahan Kelas via Upload — Design

**Status:** Approved
**Date:** 2026-07-25

## Context

Aplikasi sudah punya fitur "Naik Kelas" (`NaikKelasController`, halaman `/naik-kelas`, Admin only) yang secara otomatis menaikkan SEMUA murid aktif di SEMUA kelas sekaligus dengan pola tetap: `X-N → XI-N`, `XI-N → XII-N`, `XII-N → Lulus`. Nomor section (N) selalu tetap sama — tidak ada mekanisme untuk memindahkan murid ke section paralel yang berbeda (mis. sebagian murid X-1 pindah ke XI-2, bukan XI-1).

Fitur ini menambahkan cara **manual** untuk mengoreksi/reshuffle kelas banyak murid sekaligus, dipakai sebagai **langkah lanjutan setelah** proses Naik Kelas otomatis dijalankan (bukan pengganti, bukan berdiri sendiri).

## Goal

Admin bisa:
1. Download template Excel kosong (kolom `NIS`, `Kelas Baru`).
2. Isi baris untuk murid yang kelasnya perlu diubah.
3. Upload file → sistem validasi seluruh baris.
4. Kalau ada error → tidak ada yang tersimpan, admin perbaiki & upload ulang.
5. Kalau semua valid → preview perubahan (NIS, Nama, Kelas Lama → Kelas Baru).
6. Konfirmasi → semua perubahan disimpan sekaligus.

## Non-Goals

- Tidak menggantikan proses Naik Kelas otomatis.
- Tidak menangani kelulusan (`status = Lulus`) — itu tetap domain fitur Naik Kelas.
- Tidak membuat kelas baru otomatis — `Kelas Baru` di file harus kelas yang sudah terdaftar.
- Tidak mengubah data selain field `kelas` pada tabel `murid`.

## Placement

Section baru **"Form Perubahan Kelas"** di halaman `/naik-kelas` (`resources/views/naik-kelas/index.blade.php`), ditempatkan di bawah form Naik Kelas otomatis yang sudah ada. Akses tetap dibatasi Admin via `checkAdminAccess()` yang sudah ada di `NaikKelasController`.

## Routes

Ditambahkan ke `routes/web.php`, di dalam group yang sama dengan route `naik-kelas` yang sudah ada, method baru di `NaikKelasController`:

| Method | Path | Controller method | Fungsi |
|---|---|---|---|
| GET | `/naik-kelas/perubahan-kelas/template` | `downloadTemplatePerubahanKelas` | Download template `.xlsx` kosong |
| POST | `/naik-kelas/perubahan-kelas/preview` | `previewPerubahanKelas` | Upload file, validasi semua baris, render preview/error |
| POST | `/naik-kelas/perubahan-kelas/proses` | `prosesPerubahanKelas` | Re-validasi singkat dari session, commit perubahan |
| POST | `/naik-kelas/perubahan-kelas/batal` | `batalPerubahanKelas` | Hapus data preview dari session (tombol Batal) |

## Template Format

File `.xlsx`, dibuat dengan OpenSpout (pola sama seperti `MuridController::downloadTemplate`):

| NIS | Kelas Baru |
|---|---|
| 1234567890 | XI-2 |
| 1234567891 | XI-3 |

Header wajib: `NIS`, `Kelas Baru`. Baris contoh disertakan sebagai panduan format, admin mengganti dengan NIS & kelas tujuan murid yang sebenarnya.

## Validation Rules (per baris)

Dievaluasi untuk SEMUA baris sebelum ada satu pun perubahan disimpan (all-or-nothing):

1. **NIS wajib ada** di tabel `murid` dengan `status = 'Aktif'`. Kalau tidak ditemukan / tidak aktif → error.
2. **NIS tidak boleh duplikat** dalam file yang sama → error (ambigu, baris mana yang berlaku).
3. **Kelas Baru wajib ada** di tabel `kelas` (tidak auto-create, tidak menerima `Lulus`) → error kalau tidak ditemukan.
4. **Kelas Baru = Kelas Lama** (kelas murid saat ini di DB) → dianggap valid, tapi ditandai *no-op*: tidak dihitung sebagai perubahan, tidak masuk hitungan kapasitas, tidak muncul di daftar "akan diubah" pada preview (boleh ditampilkan terpisah sebagai info "N baris tidak berubah").
5. **Kapasitas maksimal 40 murid aktif per kelas**, dihitung dari kondisi **akhir** setelah seluruh baris valid di file diterapkan:
   - Untuk tiap kelas tujuan yang terpengaruh, hitung: `jumlah_aktif_saat_ini_di_kelas_itu − jumlah_murid_di_file_yang_keluar_dari_kelas_itu + jumlah_murid_di_file_yang_masuk_ke_kelas_itu`.
   - Kalau hasilnya > 40 → seluruh baris yang menuju kelas itu ditandai error, dengan pesan yang menyebutkan kapasitas saat ini vs proyeksi.

Kalau ada **satu saja** baris error dari poin 1–3 dan 5 → tampilkan daftar error (maks 10 pertama + total sisanya, pola sama seperti `MuridController::import`), **tidak ada data yang disimpan ke session maupun DB**. Admin harus upload ulang file yang sudah diperbaiki.

Kalau semua baris valid (poin 4 dikecualikan dari commit tapi tidak menyebabkan error) → lanjut ke preview.

## Preview & Confirm Flow (Session-based)

**Kenapa session, bukan hidden form fields:** menghindari render ratusan `<input type="hidden">` untuk file besar, dan mencegah data kelas-tujuan dimanipulasi dari browser antara preview dan commit.

1. `previewPerubahanKelas`:
   - Parse file, jalankan semua validasi di atas.
   - Kalau ada error → render halaman `/naik-kelas` dengan section error terisi (tidak menyentuh session).
   - Kalau valid → simpan array perubahan `[['NIS' => ..., 'kelas_lama' => ..., 'kelas_baru' => ...], ...]` (hanya baris yang benar-benar berubah, no-op dikecualikan) ke `session('perubahan_kelas_preview')`, lalu render halaman `/naik-kelas` dengan tabel preview dari data tsb.
2. `prosesPerubahanKelas`:
   - Ambil data dari `session('perubahan_kelas_preview')`. Kalau kosong/tidak ada (mis. session expired atau langsung akses endpoint) → redirect balik dengan pesan error "Tidak ada data preview, silakan upload ulang."
   - **Re-validasi singkat**: pastikan tiap NIS masih aktif, kelas tujuan masih ada, dan kapasitas 40 masih terpenuhi (kondisi bisa berubah sejak preview dibuat). Kalau ada yang sudah tidak valid lagi → tolak seluruh proses, tampilkan error, hapus session.
   - Kalau semua masih valid → jalankan dalam `DB::transaction()`: update `kelas` tiap murid sesuai data session.
   - Hapus `session('perubahan_kelas_preview')`, redirect dengan pesan sukses berisi jumlah murid yang dipindahkan.
3. `batalPerubahanKelas`: hapus `session('perubahan_kelas_preview')`, redirect balik ke halaman.

## UI

Di `resources/views/naik-kelas/index.blade.php`, section baru di bawah card "Form Naik Kelas" yang sudah ada:

- Card "Form Perubahan Kelas" berisi:
  - Tombol "Download Template".
  - Input file (`.xlsx`) + tombol "Preview Perubahan" (POST ke `/naik-kelas/perubahan-kelas/preview`).
  - **State error**: kotak merah daftar error per baris (format sama seperti pesan error `MuridController::import`).
  - **State preview**: tabel (NIS, Nama, Kelas Lama → Kelas Baru), ringkasan jumlah murid akan dipindah + jumlah baris no-op (jika ada), tombol "Konfirmasi & Simpan Perubahan" (dengan 1 modal konfirmasi sederhana, pola `x-modal` yang sudah dipakai di halaman ini) dan tombol "Batal".

## Error Handling Summary

| Kasus | Perlakuan |
|---|---|
| File kosong / format salah / header tidak sesuai | Error, tidak lanjut ke parsing baris |
| NIS tidak ditemukan / tidak aktif | Baris error, blok seluruh batch |
| NIS duplikat dalam file | Baris error, blok seluruh batch |
| Kelas Baru tidak terdaftar | Baris error, blok seluruh batch |
| Kelas Baru sama dengan Kelas Lama | Valid, no-op, dikecualikan dari commit |
| Kelas tujuan melebihi 40 murid (proyeksi akhir) | Baris error, blok seluruh batch |
| Akses `/proses` tanpa data session valid | Redirect dengan pesan, tidak ada perubahan |
| Kondisi berubah antara preview dan konfirmasi (mis. kapasitas jadi penuh) | Re-validasi di `/proses` menolak, tidak ada perubahan, session dihapus |

## Testing Plan

- Feature test: upload template kosong (hanya header) → tidak ada perubahan, tidak error (0 baris valid).
- Feature test: file dengan 1 baris NIS tidak ditemukan → semua baris (termasuk yang valid) ditolak, tidak ada perubahan di DB.
- Feature test: file dengan NIS duplikat → ditolak.
- Feature test: file dengan Kelas Baru tidak terdaftar → ditolak.
- Feature test: file dengan Kelas Baru = Kelas Lama → dianggap no-op, tidak masuk perubahan tapi tidak menyebabkan error kalau baris lain valid.
- Feature test: proyeksi kapasitas 40 — kombinasi murid keluar+masuk kelas yang sama dalam 1 file tidak salah dihitung sebagai overflow.
- Feature test: alur penuh preview → proses → murid ter-update di DB, session terhapus setelah commit.
- Feature test: akses `/proses` langsung tanpa preview → redirect dengan error, tidak ada perubahan.
- Feature test: akses semua endpoint oleh role non-Admin → redirect ditolak (pola `checkAdminAccess` yang sudah ada).

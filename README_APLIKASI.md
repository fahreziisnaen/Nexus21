# Buku Panduan Lengkap Aplikasi Rekap Keterlambatan

Panduan ini berisi cara pengoperasian seluruh menu yang ada di aplikasi, mulai dari Dashboard hingga fitur Naik Kelas.

---

## 🏗️ 1. Dashboard (Halaman Utama)
Setelah login, Anda langsung diarahkan ke Dashboard. Halaman ini memberikan **Ringkasan Cepat** tentang kondisi sekolah hari ini.

**Fitur:**
- **Kartu Statistik**: Menampilkan jumlah total siswa tertambat hari ini dan bulan ini.
- **Grafik/Tabel Ringkas**: Menampilkan 5 siswa yang paling sering terlambat bulan ini (Top 5).
- **Aktivitas Terbaru**: Daftar 5 keterlambatan terakhir yang baru saja diinput oleh petugas.

---

## 🗃️ 2. Menu Data Master
Menu ini berisi data inti sekolah. Biasanya hanya diakses oleh **Admin**.

### A. User Management (Pengguna)
Tempat mengatur akun siapa saja yang bisa login.
- **Tambah User**: Klik "Tambah User Baru". Isi Username, Password, Nama Lengkap, dan Role.
- **Role Akun**:
  - `Admin`: Bisa akses semua menu.
  - `Walikelas`: Hanya melihat data kelasnya sendiri.
  - `TATIB`: Khusus untuk input keterlambatan (tidak bisa ubah data master).

### B. Data Kelas
Tempat mengatur daftar kelas dan siapa wali kelasnya.
- **Tambah Kelas**: Masukkan nama kelas (misal: `X-1`, `XII-IPA-2`).
- **Set Wali Kelas**: Pilih user (Role Walikelas) yang bertanggung jawab atas kelas tersebut.
- *Catatan*: Kelas tidak bisa dihapus jika masih ada murid aktif di dalamnya.

### C. Data Murid
Tempat mengatur daftar siswa aktif.
- **Tambah Manual**: Input satu per satu (NIS, Nama, Gender, Kelas).
- **Import Excel (Disarankan)**:
  1. Klik tombol **Import Data**.
  2. Download **Template**.
  3. Isi data siswa di Excel tersebut (NIS, Nama, Gender(L/P), Kode Kelas).
  4. Upload kembali file Excelnya -> Klik Import.
  5. Ribuan data siswa akan masuk sekaligus.

---

## 📝 3. Menu Data Keterlambatan
Menu utama untuk mencatat pelanggaran siswa. Diakses oleh **Admin / TATIB (Piket)**.

### A. Melihat Data (Index)
- Tabel berisi riwayat keterlambatan seluruh siswa.
- Gunakan kolom **Search** untuk mencari nama siswa tertentu.
- Gunakan tombol **Filter** untuk melihat data per tanggal/bulan tertentu.

### B. Input Keterlambatan (Tambah Baru)
Kunci operasional harian ada disini.
1. Klik **Tambah Baru**.
2. **Cari Nama**: Ketik nama siswa, sistem akan otomatis mencarinya.
   - *Peringatan*: Jika siswa sudah terlambat >3 kali, Popup Merah akan muncul.
3. **Data Waktu**: Tanggal & Jam otomatis terisi (bisa diubah jika input data kemarin).
4. **Foto Bukti**:
   - **Tombol Kamera**: Ambil foto langsung dari laptop/HP. (Otomatis ada Watermark Jam & Tanggal).
   - **Upload File**: Ambil dari galeri (Max 2MB).
5. Klik **Simpan**. Tunggu progress bar upload selesai (100%).

---

## 📊 4. Menu Report (Laporan)
Untuk mencetak atau mengunduh laporan rekapitulasi.

### A. Laporan Bulanan
Cocok untuk laporan bulanan ke Kepala Sekolah atau ditempel di mading.
- Pilih **Tahun Ajaran** dan **Bulan**.
- Pilih **Kelas** (Opsional, tinggalkan kosong untuk semua kelas).
- Klik **Tampilkan**.
- Klik **Print** untuk mencetak ke kertas/PDF.

### B. Laporan Semester
Cocok untuk rekap akhir semester (Evaluasi & Raport).
- Pilih **Semester** (Ganjil/Genap).
- Klik **Export Excel**.
- File Excel akan terdownload otomatis berisi rekap detil per hari untuk seluruh siswa.

---

## 🎓 5. Menu Naik Kelas
Fitur khusus akhir tahun ajaran (Admin Only).

**Fungsi**: Memindahkan siswa secara massal ke tingkat selanjutnya (misal: X -> XI).
1. Pilih **Kelas Asal** (misal: X-1).
2. Centang nama-nama siswa yang naik kelas (atau "Pilih Semua").
3. Pilih **Kelas Tujuan** (misal: XI-1) atau pilih status "Lulus" untuk kelas XII.
4. Klik **Proses**.

---

## ⚙️ 6. Troubleshooting
**(FAQ - Pertanyaan Umum)**

**Q: Saya salah input data keterlambatan, cara hapusnya?**
A: Buka menu Data Keterlambatan, cari datanya, klik tombol **Hapus** (Icon Sampah Merah). Hanya Admin yang bisa menghapus.

**Q: Foto tidak mau terupload?**
A: Pastikan ukuran foto di bawah 2MB. Jika dari kamera HP modern (yang >5MB), gunakan fitur kamera aplikasi saja agar otomatis dikompres.

**Q: Notifikasi WA tidak masuk?**
A: Lapor Admin. Kemungkinan server WA belum dinyalakan atau nomor HP Walikelas salah input.
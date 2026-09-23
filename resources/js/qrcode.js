// Entry Vite terpisah untuk halaman yang butuh render QR code (saat ini hanya
// penyiapan 2FA di halaman Profile).
//
// Sengaja tidak digabung ke app.js supaya library ini tidak ikut diunduh di
// setiap halaman, dan sengaja di-bundle lokal alih-alih diambil dari CDN: server
// sekolah bisa saja tidak punya akses internet keluar, dan QR penyiapan 2FA
// harus tetap tampil dalam kondisi itu.
//
// Diimpor dari 'qrcode/lib/browser' (bukan 'qrcode') karena entry utama paket
// ini adalah versi Node yang menarik modul fs.
import QRCode from 'qrcode/lib/browser';

window.QRCode = QRCode;

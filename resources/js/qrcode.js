// Render QR code untuk halaman yang membutuhkannya (saat ini hanya penyiapan 2FA
// di halaman Profile).
//
// Entry ini sengaja dipisah dari app.js supaya library QR tidak ikut diunduh di
// setiap halaman, dan sengaja di-bundle lokal alih-alih diambil dari CDN: server
// sekolah bisa saja tidak punya akses internet keluar, sementara QR penyiapan 2FA
// harus tetap tampil.
//
// Diimpor dari 'qrcode/lib/browser' (bukan 'qrcode') karena entry utama paket ini
// adalah versi Node yang menarik modul fs.
import QRCode from 'qrcode/lib/browser';

// Seluruh logika render tinggal di sini, bukan di <script> inline pada Blade.
// @vite() merender <script type="module">, yang selalu deferred — script inline
// pada halaman akan selalu jalan LEBIH DULU daripada modul ini, sehingga
// referensi ke QRCode dari sana dijamin undefined.
function renderQrCodes() {
    document.querySelectorAll('canvas[data-qr-uri]').forEach((canvas) => {
        const uri = canvas.dataset.qrUri;
        const fallbackId = canvas.dataset.qrFallback;
        const fallback = fallbackId ? document.getElementById(fallbackId) : null;

        const showFallback = () => {
            canvas.classList.add('hidden');
            if (fallback) {
                fallback.classList.remove('hidden');
            }
        };

        if (!uri) {
            showFallback();

            return;
        }

        const size = Number(canvas.dataset.qrSize) || 200;

        QRCode.toCanvas(canvas, uri, { width: size, margin: 1 }, (error) => {
            if (error) {
                showFallback();
            }
        });
    });
}

// Modul ini deferred, jadi DOM umumnya sudah siap saat baris ini dieksekusi.
// Pengecekan readyState tetap ada supaya aman kalau kelak dimuat dengan cara lain.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderQrCodes);
} else {
    renderQrCodes();
}

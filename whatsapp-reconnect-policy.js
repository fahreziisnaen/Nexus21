import { DisconnectReason } from '@whiskeysockets/baileys';

/**
 * Kebijakan reconnect untuk koneksi Baileys.
 *
 * Dipisah ke modul sendiri supaya logikanya murni (tanpa efek samping) dan bisa
 * diuji tanpa membuka koneksi WhatsApp sungguhan.
 *
 * Latar belakang: sebelumnya kode hanya menganggap 401 (loggedOut) sebagai
 * kondisi fatal, lalu mencoba ulang setiap 2 detik tanpa batas untuk status
 * lain. Untuk penolakan yang sifatnya permanen — mis. `<failure reason="405"
 * location="atn"/>` dari server WhatsApp — itu berarti ~30 percobaan
 * autentikasi per menit tanpa henti, yang justru memancing rate limit/blokir
 * dari WhatsApp dan memperpanjang masalahnya.
 */

/**
 * Status yang tidak mungkin pulih dengan mencoba ulang: sesi/perangkat sudah
 * tidak valid dan HARUS di-pairing ulang oleh manusia (scan QR baru).
 */
export const FATAL_REASONS = new Set([
    DisconnectReason.loggedOut,           // 401 — perangkat di-unlink dari HP
    DisconnectReason.forbidden,           // 403 — akun/perangkat diblokir
    DisconnectReason.multideviceMismatch, // 411 — wajib pairing ulang
    DisconnectReason.badSession,          // 500 — file sesi rusak
    DisconnectReason.connectionReplaced,  // 440 — sesi lain mengambil alih;
                                          //       reconnect malah saling rebut
]);

export const MAX_RECONNECT_ATTEMPTS = 10;

const BASE_DELAY_MS = 2000;
const MAX_DELAY_MS = 60000;

/**
 * Exponential backoff dengan batas atas: 2s, 4s, 8s, 16s, 32s, lalu 60s.
 * @param {number} attempt percobaan ke-berapa (mulai dari 1)
 */
export function backoffDelayMs(attempt) {
    return Math.min(BASE_DELAY_MS * 2 ** (attempt - 1), MAX_DELAY_MS);
}

/**
 * Tentukan tindakan setelah koneksi tertutup.
 *
 * @param {number|undefined} statusCode status dari Boom error Baileys
 * @param {number} attempt percobaan reconnect ke-berapa (mulai dari 1)
 * @returns {{action: 'retry', delayMs: number} | {action: 'stop', reason: 'fatal'|'exhausted'}}
 */
export function classifyDisconnect(statusCode, attempt) {
    if (FATAL_REASONS.has(statusCode)) {
        return { action: 'stop', reason: 'fatal' };
    }

    if (attempt > MAX_RECONNECT_ATTEMPTS) {
        return { action: 'stop', reason: 'exhausted' };
    }

    return { action: 'retry', delayMs: backoffDelayMs(attempt) };
}


import { makeWASocket, DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@whiskeysockets/baileys';
import express from 'express';
import bodyParser from 'body-parser';
import pino from 'pino';
import qrcode from 'qrcode-terminal';

const app = express();
const PORT = 3000;

app.use(bodyParser.json());

let sock; // Global socket variable used by the API

async function connectToWhatsApp() {
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`Using WhatsApp Web v${version.join('.')}, isLatest: ${isLatest}`);

    // Use multi-file auth state to save credentials locally
    const { state, saveCreds } = await useMultiFileAuthState('wa_auth');

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false, // Deprecated, we handle it manually below
        auth: state,
        browser: ['Windows', 'Chrome', '11.0.1'],
        connectTimeoutMs: 60000, // Give more time to connect
        defaultQueryTimeoutMs: 0, // Keep queries alive
        keepAliveIntervalMs: 10000, // Ping interval
    });

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('\nScan QR Code dibawah ini untuk login WhatsApp:\n');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            const error = lastDisconnect?.error;
            const statusCode = error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            // Handle specific error codes gracefully without scary logs
            if (statusCode === 515 || error?.message?.includes('Stream Errored')) {
                console.log('🔄 Stream need restart (Error 515). Reconnecting automatically...');
            } else {
                console.log('Connection closed due to ', error, ', reconnecting ', shouldReconnect);
            }

            // Nullify sock immediately to prevent using closed socket
            sock = undefined;
            if (shouldReconnect) {
                // Add small delay before reconnecting
                setTimeout(connectToWhatsApp, 2000); // Increased delay slightly
            }
        } else if (connection === 'open') {
            console.log('WhatsApp connection opened!');
        }
    });

    sock.ev.on('creds.update', saveCreds);
}

// Endpoint to send message is defined ONCE
app.post('/send-message', async (req, res) => {
    // Expected payload: { id: '628xxx@c.us', message: 'Hello World' }
    const { id, message } = req.body;

    if (!id || !message) {
        return res.status(400).json({ status: false, error: 'Missing id or message' });
    }

    try {
        // Wait for 5 seconds max if socket is not ready?
        // For now, let's just try to send. 
        // If sock is undefined or closed, it might throw.

        if (!sock) {
            return res.status(503).json({ status: false, error: 'WhatsApp service not ready' });
        }

        // Check for connection status (optional, but good)
        // Accessing internal state can be risky, try/catch is best.

        await sock.sendMessage(id, { text: message });
        console.log(`Message sent to ${id}`);
        res.json({ status: true, message: 'Message sent successfully' });
    } catch (error) {
        console.error('Failed to send message:', error);

        // Handle 428 specifically
        if (error?.output?.statusCode === 428) {
            return res.status(428).json({ status: false, error: 'WhatsApp connection not open. Please checking server logs.' });
        }

        res.status(500).json({ status: false, error: 'Failed to send message: ' + (error.message || error) });
    }
});

// Start WhatsApp Service
connectToWhatsApp().catch(err => console.log('Unexpected error:', err));

// Start Express Server
app.listen(PORT, () => {
    console.log(`WhatsApp Gateway running on port ${PORT}`);
});

/**
 * Recapture the NexaCRM product demo video from a running local app.
 *
 * Usage:
 *   DEMO_SEED_PASSWORD='...' node scripts/capture-nexacrm-demo-video.mjs
 *
 * Optional:
 *   CAPTURE_SUPERADMIN=1 SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD
 *   to include a Super Admin console segment (off by default to keep the demo short).
 *
 * Records the live UI via Chrome screencast (no desktop/browser chrome),
 * then holds each unique frame for its real on-screen duration.
 * Does not print or write passwords to disk.
 */
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import process from 'node:process';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const outFile = path.join(root, 'public/marketing/videos/nexacrm-product-demo.mp4');

const baseUrl = process.env.APP_URL?.replace(/\/$/, '') || 'http://127.0.0.1:8000';
const email = process.env.DEMO_ADMIN_EMAIL || 'admin@demo.nexacrm.test';
const password = process.env.DEMO_SEED_PASSWORD;
const superEmail = process.env.SUPERADMIN_EMAIL || 'owner@nexacrm.test';
const superPassword = process.env.SUPERADMIN_PASSWORD || '';
const includeSuperAdmin = process.env.CAPTURE_SUPERADMIN === '1' && Boolean(superPassword);

if (!password) {
    console.error('DEMO_SEED_PASSWORD is required.');
    process.exit(1);
}

const chrome = process.env.CHROME_PATH || '/usr/bin/google-chrome-stable';
const framesDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nexacrm-demo-frames-'));
const recorded = [];
let frameIndex = 0;
let writeQueue = Promise.resolve();

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function hideChromeNoise(page) {
    await page.addStyleTag({
        content: `
            #phpdebugbar, .phpdebugbar, .phpdebugbar-openhandler,
            .driver-overlay, .driver-popover, .driver-active-element {
                display: none !important;
            }
        `,
    }).catch(() => {});
    await page.evaluate(() => {
        document.querySelectorAll('.driver-popover, .driver-overlay, #phpdebugbar').forEach((el) => el.remove());
    });
}

async function gotoReady(page, route, dwell = 1800) {
    await page.goto(`${baseUrl}${route}`, { waitUntil: 'networkidle2', timeout: 30000 });
    await hideChromeNoise(page);
    await page.evaluate(async () => {
        if (document.fonts?.ready) {
            await document.fonts.ready;
        }
        window.scrollTo(0, 0);
    }).catch(() => {});
    await sleep(dwell);
}

async function logout(page) {
    await page.evaluate(async () => {
        const token = document.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            return;
        }
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': token,
            },
            body: `_token=${encodeURIComponent(token)}`,
        });
    }).catch(() => {});
}

async function login(page, user, pass) {
    await gotoReady(page, '/login', 900);
    await page.waitForSelector('input[name="email"]', { timeout: 15000 });
    await page.click('input[name="email"]', { clickCount: 3 });
    await page.type('input[name="email"]', user, { delay: 8 });
    await page.click('input[name="password"]', { clickCount: 3 });
    await page.type('input[name="password"]', pass, { delay: 8 });
    await sleep(250);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }),
        page.click('form button[type="submit"]'),
    ]);
    if (page.url().includes('/login')) {
        throw new Error('Login failed; still on /login');
    }
    await hideChromeNoise(page);
    await sleep(1200);
}

function runFfmpeg(args) {
    return new Promise((resolve, reject) => {
        const child = spawn('ffmpeg', args, { stdio: ['ignore', 'pipe', 'pipe'] });
        let stderr = '';
        child.stderr.on('data', (chunk) => {
            stderr += chunk.toString();
        });
        child.on('close', (code) => {
            if (code === 0) {
                resolve();
            } else {
                reject(new Error(`ffmpeg exited ${code}\n${stderr.slice(-2000)}`));
            }
        });
    });
}

const browser = await puppeteer.launch({
    executablePath: chrome,
    headless: 'new',
    defaultViewport: { width: 1920, height: 1080, deviceScaleFactor: 1 },
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--window-size=1920,1080', '--hide-scrollbars'],
});

const page = await browser.newPage();
await page.setViewport({ width: 1920, height: 1080, deviceScaleFactor: 1 });
const client = await page.createCDPSession();

client.on('Page.screencastFrame', (event) => {
    const dest = path.join(framesDir, `cap-${String(frameIndex).padStart(5, '0')}.jpg`);
    recorded.push({ dest, at: Date.now() });
    frameIndex += 1;
    writeQueue = writeQueue.then(() => fs.promises.writeFile(dest, Buffer.from(event.data, 'base64')));
    client.send('Page.screencastFrameAck', { sessionId: event.sessionId }).catch(() => {});
});

try {
    await gotoReady(page, '/', 400);
    await client.send('Page.startScreencast', {
        format: 'jpeg',
        quality: 84,
        everyNthFrame: 1,
        maxWidth: 1920,
        maxHeight: 1080,
    });

    await sleep(2400);
    await login(page, email, password);
    await gotoReady(page, '/dashboard', 3200);
    await page.evaluate(() => window.scrollTo(0, 240));
    await sleep(1200);
    await page.evaluate(() => window.scrollTo(0, 0));
    await sleep(800);
    await gotoReady(page, '/leads', 3400);
    await sleep(800);
    await gotoReady(page, '/customers', 3200);
    await gotoReady(page, '/tasks', 3200);
    await gotoReady(page, '/reports', 3400);
    await gotoReady(page, '/users', 3000);
    await gotoReady(page, '/roles', 3000);

    if (includeSuperAdmin) {
        await logout(page);
        await sleep(400);
        await login(page, superEmail, superPassword);
        await gotoReady(page, '/superadmin', 2800);
        await logout(page);
        await sleep(400);
        await login(page, email, password);
    }

    await gotoReady(page, '/dashboard', 2800);
    await client.send('Page.stopScreencast').catch(() => {});
    await writeQueue;
} finally {
    await browser.close();
}

if (recorded.length < 20) {
    throw new Error(`Too few screencast frames (${recorded.length}); aborting video encode.`);
}

const fps = 12;
const expandedDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nexacrm-demo-expanded-'));
const endedAt = Date.now();
let outIndex = 0;

for (let i = 0; i < recorded.length; i++) {
    const start = recorded[i].at;
    const end = recorded[i + 1]?.at ?? (endedAt + 1500);
    const seconds = Math.max(0.08, (end - start) / 1000);
    const copies = Math.min(48, Math.max(1, Math.round(seconds * fps)));
    for (let copy = 0; copy < copies; copy++) {
        fs.copyFileSync(
            recorded[i].dest,
            path.join(expandedDir, `frame-${String(outIndex).padStart(5, '0')}.jpg`),
        );
        outIndex += 1;
    }
}

fs.mkdirSync(path.dirname(outFile), { recursive: true });
const tmpOut = `${outFile}.tmp.mp4`;

await runFfmpeg([
    '-y',
    '-framerate', String(fps),
    '-i', path.join(expandedDir, 'frame-%05d.jpg'),
    '-c:v', 'libx264',
    '-pix_fmt', 'yuv420p',
    '-vf', 'scale=1920:1080',
    '-movflags', '+faststart',
    '-metadata', 'title=NexaCRM product demo',
    '-metadata', 'comment=NexaCRM — A Modern Multi-Tenant CRM for Growing Businesses',
    tmpOut,
]);

fs.renameSync(tmpOut, outFile);
fs.rmSync(framesDir, { recursive: true, force: true });
fs.rmSync(expandedDir, { recursive: true, force: true });
console.log('wrote', path.relative(root, outFile), `(${recorded.length} unique frames, ${outIndex} held frames)`);

/**
 * Recapture buyer-facing NexaCRM screenshots from a running local app.
 *
 * Usage:
 *   DEMO_SEED_PASSWORD='...' node scripts/capture-nexacrm-screenshots.mjs
 *
 * Requires: google-chrome, puppeteer-core, php artisan serve, DemoDataSeeder.
 * Does not print or write the password to disk.
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import process from 'node:process';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const outDir = path.join(root, 'public/marketing/screenshots');

const baseUrl = process.env.APP_URL?.replace(/\/$/, '') || 'http://127.0.0.1:8000';
const email = process.env.DEMO_ADMIN_EMAIL || 'admin@demo.nexacrm.test';
const password = process.env.DEMO_SEED_PASSWORD;

if (!password) {
    console.error('DEMO_SEED_PASSWORD is required.');
    process.exit(1);
}

const chrome =
    process.env.CHROME_PATH ||
    '/usr/bin/google-chrome-stable';

fs.mkdirSync(outDir, { recursive: true });

const tenantShots = [
    { file: 'nexacrm-dashboard.png', path: '/dashboard', wait: 1800 },
    { file: 'nexacrm-overview.png', path: '/dashboard', wait: 1800 },
    { file: 'nexacrm-leads.png', path: '/leads', wait: 1000 },
    { file: 'nexacrm-sales-pipeline.png', path: '/leads', wait: 1000 },
    { file: 'nexacrm-customers.png', path: '/customers', wait: 800 },
    { file: 'nexacrm-tasks.png', path: '/tasks', wait: 1000 },
    { file: 'nexacrm-reports.png', path: '/reports', wait: 2500 },
    {
        file: 'nexacrm-analytics.png',
        path: '/dashboard',
        wait: 2000,
        scroll: 500,
    },
    { file: 'nexacrm-activity-log.png', path: '/activity-logs', wait: 800, sanitizeIps: true },
    { file: 'nexacrm-roles-permissions.png', path: '/roles', wait: 800 },
    { file: 'nexacrm-user-management.png', path: '/users', wait: 800 },
];

const publicShots = [
    { file: 'nexacrm-pricing.png', path: '/pricing', wait: 1200 },
];

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
        const replay = document.querySelector('[data-tour="tour-replay"]');
        if (replay && typeof replay.blur === 'function') {
            replay.blur();
        }
    });
}

async function waitForFonts(page) {
    await page.evaluate(async () => {
        if (document.fonts?.ready) {
            await document.fonts.ready;
        }
    }).catch(() => {});
}

async function shot(page, file, extraWait = 0) {
    await hideChromeNoise(page);
    await waitForFonts(page);
    if (extraWait) {
        await new Promise((r) => setTimeout(r, extraWait));
    }
    const dest = path.join(outDir, file);
    await page.screenshot({ path: dest, type: 'png', fullPage: false });
    console.log('wrote', path.relative(root, dest));
}

function sanitizeDemoActivityIps() {
    execFileSync('php', [
        'artisan',
        'tinker',
        '--execute',
        'config(["tenancy.fail_closed_without_context" => false]); App\\Models\\ActivityLog::withoutGlobalScopes()->whereIn("ip_address", ["127.0.0.1", "::1"])->update(["ip_address" => "203.0.113.24"]);',
    ], { cwd: root });
}

async function applyFraming(page, item) {
    if (item.scrollSelector) {
        await page.evaluate((selector) => {
            const el = document.querySelector(selector);
            if (el) {
                el.scrollIntoView({ block: 'center', inline: 'nearest' });
            }
        }, item.scrollSelector);
        return;
    }
    if (item.scroll) {
        await page.evaluate((y) => window.scrollTo(0, y), item.scroll);
        return;
    }
    await page.evaluate(() => window.scrollTo(0, 0));
}

const browser = await puppeteer.launch({
    executablePath: chrome,
    headless: 'new',
    defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 2 },
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--window-size=1440,900', '--hide-scrollbars'],
});

const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });

try {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForSelector('input[name="email"]', { timeout: 15000 });
    await page.type('input[name="email"]', email, { delay: 10 });
    await page.type('input[name="password"]', password, { delay: 10 });
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }),
        page.click('form button[type="submit"]'),
    ]);

    const url = page.url();
    if (url.includes('/login')) {
        throw new Error('Login failed; still on /login');
    }

    for (const item of tenantShots) {
        if (item.sanitizeIps) {
            sanitizeDemoActivityIps();
        }
        await page.goto(`${baseUrl}${item.path}`, { waitUntil: 'networkidle2', timeout: 30000 });
        await page.waitForSelector('body', { timeout: 10000 });
        await applyFraming(page, item);
        await shot(page, item.file, item.wait);
    }

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

    const guest = await browser.newPage();
    await guest.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
    for (const item of publicShots) {
        await guest.goto(`${baseUrl}${item.path}`, { waitUntil: 'networkidle2', timeout: 30000 });
        await guest.evaluate(() => window.scrollTo(0, 0));
        await shot(guest, item.file, item.wait);
    }
    await guest.close();
} finally {
    await browser.close();
}

import { default as puppeteer } from 'puppeteer-core';
import fs from 'node:fs';
import { spawnSync } from 'node:child_process';

const BASE = 'http://127.0.0.1:8001';
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const SHOT = 'storage/app/sesi5';

fs.mkdirSync(SHOT, { recursive: true });

const summary = {};
const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu'],
    defaultViewport: { width: 1440, height: 950 },
});

const page = await browser.newPage();
const pageErrors = [];
page.on('pageerror', (e) => pageErrors.push(String(e)));
page.on('console', (m) => {
    if (m.type() === 'error' && !m.text().includes('favicon')) pageErrors.push(m.text());
});

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const goto = (path) => page.goto(BASE + path, { waitUntil: 'domcontentloaded', timeout: 60000 });
const shot = (name) => page.screenshot({ path: `${SHOT}/${name}.png` });
const waitFor = (fn, timeout = 25000) => page.waitForFunction(fn, { timeout });

// Seed a few fresh location logs into the 7h trail window (seeded data is older
// than 7 days relative to "today", so the trail would legitimately be empty).
function seedTrailLogs() {
    const php = `
$animals = App\\Models\\Animal::query()->whereNotNull('device_id')->orderBy('id')->limit(3)->get();
$base = now()->subMinutes(30);
$i = 0;
foreach ($animals as $a) {
    for ($k = 0; $k < 4; $k++) {
        $ts = $base->copy()->addMinutes($i * 3);
        App\\Models\\LocationLog::create([
            'device_id' => $a->device_id,
            'animal_id' => $a->id,
            'latitude' => -7.5 + ($k * 0.0015),
            'longitude' => 110.2 + ($k * 0.0015),
            'altitude' => 100,
            'speed_kmh' => 4.5,
            'heading' => 90,
            'accuracy_meters' => 8,
            'satellites' => 12,
            'hdop' => 0.8,
            'recorded_at' => $ts,
            'received_at' => now(),
            'is_valid' => true,
        ]);
        $i++;
    }
}
echo 'seeded=' . $i;
`;
    const r = spawnSync('php', ['artisan', 'tinker', '--execute', php], { encoding: 'utf8' });
    return r.status === 0 ? r.stdout.trim() : 'ERR:' + (r.stderr || '').trim().slice(0, 200);
}

// ---------- LOGIN ----------
await goto('/login');
await page.type('input[name="email"]', 'rudi@ternaktrack.test');
await page.type('input[name="password"]', 'password');
await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
    page.click('button[type="submit"]'),
]).catch(() => {});
await sleep(1200);
summary.loginUrl = page.url();
summary.loginOk = !page.url().includes('/login');

// ---------- FENCES LIST ----------
await goto('/fences');
await sleep(2000);
summary.fenceCards = await page.$$eval('.fence-mini-map', (els) => els.length);
summary.miniMapsInitialized = await page.$$eval('.fence-mini-map[data-inited="1"]', (els) => els.length);
summary.miniMapSvgPaths = await page.$$eval('.fence-mini-map svg path', (els) => els.length);
summary.miniMapHasPolygons = await page.$$eval('.fence-mini-map', (els) =>
    els.filter((el) => el.querySelectorAll('svg path').length > 0).length);
summary.firstFenceId = await page.evaluate(() => {
    const a = Array.from(document.querySelectorAll('a[href]'))
        .find((x) => /\/(fences)\/\d+$/.test(x.getAttribute('href') || ''));
    return a ? parseInt(a.getAttribute('href').match(/\/(\d+)$/)[1], 10) : null;
});
await shot('fences');

// ---------- FENCE CREATE ----------
await goto('/fences/create');
await sleep(1500);

// name via native setter + input/change events (real DOM path)
const nameRes = await page.evaluate(() => {
    const input = document.getElementById('fence-name');
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(input, 'Fence E2E ' + new Date().getTime().toString().slice(-6));
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    return input.value;
});
summary.nameSet = nameRes;
await sleep(1500);

// select a farm if available
const farmRes = await page.evaluate(() => {
    const sel = document.getElementById('fence-farm');
    if (!sel || sel.options.length < 2) return null;
    const setter = Object.getOwnPropertyDescriptor(window.HTMLSelectElement.prototype, 'value').set;
    setter.call(sel, sel.options[1].value);
    sel.dispatchEvent(new Event('change', { bubbles: true }));
    return sel.options[1].value;
});
summary.farmSelected = farmRes;
await sleep(800);

// select an animal if any
const animalPicked = await page.evaluate(() => {
    const boxes = Array.from(document.querySelectorAll('input[wire\\:model\\.live="selectedAnimals"]'));
    if (boxes.length === 0) return null;
    boxes[0].click();
    return true;
});
summary.animalSelected = animalPicked;
await sleep(800);

// ---- Draw polygon via in-page real MouseEvents ----
const drawPayload = await page.evaluate(async () => {
    const tt = window.ttFenceFormMap;
    if (!tt || !tt.map) return { ok: false, reason: 'no ttFenceFormMap' };
    const container = document.getElementById('fence-draw-map');
    const L = window.L;
    const sleepLocal = (ms) => new Promise((r) => setTimeout(r, ms));

    // start drawing via the real button click (DOM event path)
    const btn = document.getElementById('tb-draw');
    btn.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
    await sleepLocal(350);

    const c = tt.map.getCenter();
    const o = 0.0011;
    const ll = [
        [c.lat - o, c.lng - o],
        [c.lat - o, c.lng + o],
        [c.lat + o, c.lng + o],
        [c.lat + o, c.lng - o],
    ];

    const fireAt = (type, latlng, buttons) => {
        const pt = L.latLng(latlng[0], latlng[1]);
        const cp = tt.map.latLngToContainerPoint(pt);
        const rect = container.getBoundingClientRect();
        const x = Math.round(rect.left + cp.x);
        const y = Math.round(rect.top + cp.y);
        const target = document.elementFromPoint(x, y) || container;
        const base = {
            bubbles: true, cancelable: true, view: window,
            clientX: x, clientY: y, button: 0, buttons, detail: type === 'dblclick' ? 2 : 1,
        };
        // Leaflet 1.9 binds pointer events (pointerdown/pointermove/pointerup) in Chrome.
        const physMap = { mousemove: 'pointermove', mousedown: 'pointerdown', mouseup: 'pointerup' };
        const phys = physMap[type];
        if (phys) {
            target.dispatchEvent(new PointerEvent(phys, Object.assign({}, base, {
                pointerId: 11, pointerType: 'mouse', isPrimary: true,
                width: 1, height: 1, pressure: base.buttons ? 0.5 : 0,
            })));
        }
        target.dispatchEvent(new MouseEvent(type, base));
    };

    const screen = (latlng) => {
        const cp = tt.map.latLngToContainerPoint(L.latLng(latlng[0], latlng[1]));
        const rect = container.getBoundingClientRect();
        return { x: Math.round(rect.left + cp.x), y: Math.round(rect.top + cp.y) };
    };

    // hover first (positions the Leaflet mouse-marker)
    fireAt('mousemove', ll[0], 0);
    await sleepLocal(150);

    for (const p of ll) {
        fireAt('mousemove', p, 0);
        await sleepLocal(60);
        fireAt('mousedown', p, 1);
        await sleepLocal(100);
        fireAt('mouseup', p, 0);
        await sleepLocal(180);
    }

    // close the polygon: L.Draw.Polygon finishes on click of the FIRST vertex
    // (and dblclick on the last vertex). Try first-vertex click, then dblclick fallback.
    const closeTarget = (latlng, type, detail) => {
        const s = screen(latlng);
        const target = document.elementFromPoint(s.x, s.y) || container;
        target.dispatchEvent(new MouseEvent(type, {
            bubbles: true, cancelable: true, view: window,
            clientX: s.x, clientY: s.y, button: 0, buttons: 0, detail,
        }));
        return String(target.className || '').slice(0, 60);
    };
    fireAt('mousemove', ll[3], 0);
    await sleepLocal(60);
    let closed = null;
    closed = closeTarget(ll[0], 'click', 1);
    await sleepLocal(900);
    if (tt.points.length < 3) {
        closeTarget(ll[3], 'dblclick', 2);
        await sleepLocal(900);
    }

    return { ok: true, points: tt.points.length, closed };
});
summary.draw = drawPayload;

await sleep(2000);
summary.livePoints = (await page.$eval('#fence-live-points', (el) => el.textContent.trim())).replace(/\D/g, '');
summary.liveArea = await page.$eval('#fence-live-area', (el) => el.textContent.trim());
await shot('fence-form-drawn');

// ---------- SUBMIT ----------
await page.evaluate(() => {
    document.querySelector('form[wire\\:submit="save"] button[type="submit"]').click();
});
let landed = false;
try {
    await waitFor(() => !window.location.href.includes('/fences/create'));
    landed = true;
} catch (e) {
    await sleep(2000);
}
summary.landed = landed;
summary.afterSaveUrl = page.url();

if (!landed) {
    summary.createDiagnostic = await page.evaluate(() => {
        const errs = Array.from(document.querySelectorAll('.text-red-600')).map((el) => el.textContent.trim());
        return {
            redErrors: errs,
            livePoints: document.getElementById('fence-live-points')?.textContent.trim() ?? null,
            liveArea: document.getElementById('fence-live-area')?.textContent.trim() ?? null,
        };
    });
    await shot('fence-form-submit-fail');
}

// ---------- LIST AFTER CREATE ----------
await goto('/fences');
await sleep(2000);
summary.cardsAfterCreate = await page.$$eval('.fence-mini-map', (els) => els.length);
const newCard = await page.evaluate(() => {
    const maps = Array.from(document.querySelectorAll('.fence-mini-map')).filter((el) => {
        const p = el.parentElement && el.parentElement.parentElement;
        return p && p.textContent.includes('Fence E2E');
    });
    if (maps.length === 0) return null;
    const card = maps[0].parentElement.parentElement;
    const areaText = Array.from(card.querySelectorAll('div'))
        .find((d) => String(d.className || '').includes('tabular-nums'))
        ?.textContent.trim();
    return { area: areaText || '', inited: maps[0].dataset.inited || null };
});
summary.newFenceCard = newCard;
if (newCard) {
    summary.newFenceMiniMapPath = await page.evaluate(() => {
        const maps = Array.from(document.querySelectorAll('.fence-mini-map')).filter((el) => {
            const p = el.parentElement && el.parentElement.parentElement;
            return p && p.textContent.includes('Fence E2E');
        });
        return maps.length > 0 ? maps[0].querySelectorAll('svg path').length : 0;
    });
    summary.newFenceId = await page.evaluate(() => {
        const maps = Array.from(document.querySelectorAll('.fence-mini-map')).filter((el) => {
            const p = el.parentElement && el.parentElement.parentElement;
            return p && p.textContent.includes('Fence E2E');
        });
        if (maps.length === 0) return null;
        const a = maps[0].parentElement && maps[0].parentElement.closest('a[href]');
        const m = a ? (a.getAttribute('href') || '').match(/\/fences\/(\d+)/) : null;
        return m ? parseInt(m[1], 10) : null;
    });
}
await shot('fences-after');

// ---------- FENCE EDIT (preloaded polygon + vertex removal) ----------
if (summary.newFenceId) {
    const id = summary.newFenceId;
    await goto('/fences/' + id + '/edit');
    await sleep(2500);

    const editProbe = await page.evaluate(async () => {
        const tt = window.ttFenceFormMap;
        const out = {
            mapExists: !!tt && !!tt.map,
            preloaded: false, svgPaths: 0, livePoints: '', areaBefore: '',
            markersShown: 0, pointsAfter: 0, areaAfter: '', removedOne: false, areaChanged: false,
        };
        if (!tt || !tt.map) return out;
        out.preloaded = tt.points.length >= 3;
        out.svgPaths = document.querySelectorAll('#fence-draw-map svg path').length;
        out.livePoints = document.getElementById('fence-live-points')?.textContent.trim() || '';
        out.areaBefore = document.getElementById('fence-live-area')?.textContent.trim() || '';

        // enable vertex markers
        document.getElementById('tb-edit').dispatchEvent(
            new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
        await new Promise((r) => setTimeout(r, 400));
        const icons = document.querySelectorAll('#fence-draw-map .leaflet-marker-icon');
        out.markersShown = icons.length;
        // remove one vertex (click its icon) to change the polygon
        if (icons.length >= 2 && tt.points.length > 3) {
            icons[1].click();
            await new Promise((r) => setTimeout(r, 1800));
        }
        out.pointsAfter = tt.points.length;
        out.areaAfter = document.getElementById('fence-live-area')?.textContent.trim() || '';
        out.removedOne = out.pointsAfter === 3;
        out.areaChanged = out.areaBefore !== out.areaAfter;
        return out;
    });
    summary.edit = editProbe;
    await shot('fence-form-edit');

    summary.editNameKept = await page.evaluate((n) =>
        document.getElementById('fence-name')?.value === n, summary.nameSet);

    await page.evaluate(() => {
        document.querySelector('form[wire\\:submit="save"] button[type="submit"]').click();
    });
    let editLanded = false;
    try {
        await waitFor("!window.location.href.includes('/fences/" + id + "/edit')");
        editLanded = true;
    } catch (e) { await sleep(2000); }
    summary.editLanded = editLanded;
    summary.afterEditUrl = page.url();

    await goto('/fences');
    await sleep(2000);
    summary.editCardArea = await page.evaluate((name) => {
        const maps = Array.from(document.querySelectorAll('.fence-mini-map')).filter((el) => {
            const p = el.parentElement && el.parentElement.parentElement;
            return p && p.textContent.includes(name);
        });
        if (maps.length === 0) return null;
        const card = maps[0].parentElement.parentElement;
        const t = Array.from(card.querySelectorAll('div'))
            .find((d) => String(d.className || '').includes('tabular-nums'));
        return t ? t.textContent.trim() : null;
    }, summary.nameSet);
    summary.editCardMatches = !!summary.edit.areaAfter
        && summary.editCardArea === summary.edit.areaAfter;
}

// ---------- FENCE DELETE (via confirm modal) ----------
if (summary.newFenceId) {
    const id = summary.newFenceId;
    await goto('/fences/' + id);
    await sleep(2500);

    summary.deleteHandlersReady = await page.evaluate(() =>
        typeof window.ttFenceDetailDelete === 'function');

    await page.evaluate(() => {
        const btns = Array.from(document.querySelectorAll('button'))
            .filter((b) => b.textContent.trim() === 'Hapus');
        if (btns.length > 0) btns[0].click();
    });
    await sleep(500);
    summary.deleteModalOpen = await page.evaluate(() =>
        !!(window.Alpine && window.Alpine.store('confirm').open));
    summary.deleteModalTitle = await page.evaluate(() =>
        window.Alpine ? window.Alpine.store('confirm').title : null);

    summary.deleteConfirmed = false;
    if (summary.deleteModalOpen) {
        summary.deleteConfirmed = await page.evaluate(() => {
            const btns = Array.from(document.querySelectorAll('[x-data="Alpine.store(\'confirm\')"] button'));
            const btn = btns[btns.length - 1];
            if (!btn) return false;
            btn.click();
            return true;
        });
        try {
            await waitFor("!window.location.href.includes('/fences/" + id + "')", 15000);
        } catch (e) {}
        await sleep(2500);
    }
    summary.deleteLanded = !page.url().includes('/fences/' + id);
    summary.afterDeleteUrl = page.url();
    await shot('fences-after-delete');

    await goto('/fences');
    await sleep(2000);
    summary.fencesAfterDelete = await page.$$eval('.fence-mini-map', (els) => els.length);
    summary.fenceGoneAfterDelete = await page.evaluate((name) => {
        return Array.from(document.querySelectorAll('.fence-mini-map')).filter((el) => {
            const p = el.parentElement && el.parentElement.parentElement;
            return p && p.textContent.includes(name);
        }).length === 0;
    }, summary.nameSet);
}

// ---------- LIVE MAP ----------
summary.seedTrailLogs = seedTrailLogs();
await goto('/map');
await sleep(3500);
summary.mapPins = await page.$$eval('.tt-animal-pin', (els) => els.length);
summary.mapFencePolygons = await page.evaluate(() =>
    document.querySelectorAll('#live-map svg path').length);
summary.mapCheckboxes = await page.$$eval('[id^="fence-cb-"]', (els) => els.length);
summary.pinStatuses = await page.evaluate(() => {
    const r = {};
    document.querySelectorAll('.tt-animal-pin').forEach((p) => {
        const s = p.dataset.status || 'none';
        r[s] = (r[s] || 0) + 1;
    });
    return r;
});
await page.evaluate(() => {
    window.ttLiveMap.setStatusFilter('outside');
    window.__pinsFiltered = document.querySelectorAll('.tt-animal-pin').length;
    window.ttLiveMap.setStatusFilter('');
});
summary.pinsVisibleAfterFilterOutside = await page.evaluate(() => window.__pinsFiltered);
summary.statusFilterWorks = typeof summary.pinsVisibleAfterFilterOutside === 'number'
    && summary.pinsVisibleAfterFilterOutside <= summary.mapPins;
await shot('live-map');

// ---------- TRAIL ----------
// widen the window first (default 1h often has no logs in seeded data)
await page.evaluate(() => {
    const el = document.getElementById('trail-hours');
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(el, '7');
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
});
await sleep(1500);
const trailAnimal = await page.evaluate(() => {
    let payload = null;
    try { payload = JSON.parse(document.getElementById('live-map-data').textContent); } catch (e) { return null; }
    const found = (payload.animals || []).find((a) => a.lat !== null && a.lng !== null);
    return found ? String(found.id) : null;
});
if (trailAnimal) {
    const before = await page.$$eval('.leaflet-interactive', (els) => els.length);
    await page.select('#trail-animal', trailAnimal);
    try {
        await waitFor(`document.querySelectorAll('.leaflet-interactive').length > ${before}`, 15000);
    } catch (e) {}
    await sleep(1500);
    summary.trailLinesBefore = before;
    summary.trailLinesAfter = await page.$$eval('.leaflet-interactive', (els) => els.length);
    summary.trailLen = await page.evaluate(() => {
        try {
            const p = JSON.parse(document.getElementById('live-map-data').textContent);
            return Array.isArray(p.trail) ? p.trail.length : 0;
        } catch (e) { return 0; }
    });
    summary.trailDrawn = summary.trailLinesAfter > before && summary.trailLen > 1;
    await shot('live-map-trail');
} else {
    summary.trailDrawn = false;
    summary.trailLen = 0;
}

// ---------- FENCE DETAIL ----------
if (summary.firstFenceId) {
    await goto('/fences/' + summary.firstFenceId);
    await sleep(2500);
    summary.detailPins = await page.$$eval('.tt-animal-pin', (els) => els.length);
    summary.detailMapOk = await page.evaluate(() => !!window.ttFenceDetailMap
        && document.querySelector('#fence-detail-map.leaflet-container') !== null);
    summary.detailMapSvgPaths = await page.evaluate(() =>
        document.querySelectorAll('#fence-detail-map svg path').length);
    summary.detailEventsShown = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('h2')).some((h) => h.textContent.includes('Riwayat Event'));
    });
    await shot('fence-detail');
}

// detail of a fence that has animals assigned (pins proof)
if (summary.firstFenceId !== 1) {
    await goto('/fences/1');
    await sleep(2500);
    summary.detailOkPinsPage = await page.$$eval('#fence-detail-map .tt-animal-pin', (els) => els.length);
    summary.detailOkMapOnFence1 = await page.evaluate(() =>
        !!window.ttFenceDetailMap
        && document.querySelector('#fence-detail-map.leaflet-container') !== null
        && document.querySelectorAll('#fence-detail-map svg path').length > 0);
    await shot('fence-detail-pins');
} else {
    summary.detailOkPinsPage = summary.detailPins;
}

// ---------- REPORT ----------
summary.pageErrors = pageErrors.slice(0, 15);

fs.writeFileSync(`${SHOT}/summary.json`, JSON.stringify(summary, null, 2));
await browser.close();
console.log('E2E SUMMARY');
console.log(JSON.stringify(summary, null, 2));
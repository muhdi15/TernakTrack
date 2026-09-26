# TernakTrack

Sistem pemantauan ternak berbasis GPS & Virtual Fence. Backend Laravel 12
(PHP 8.3) + Livewire 3, menerima data lokasi dari perangkat GPS tracker
(ESP32) melalui REST API, lalu mengambil keputusan geofence secara real-time.

## Arsitektur Pengiriman Data (SESI 3)

```
┌──────────┐   POST /api/v1/locations       ┌──────────────────────────────┐
│  ESP32   │ ──────────────────────────────► │         API v1 (Laravel)      │
│ (tracker)│   Bearer <api_token>            │                              │
└──────────┘                                 │  1. AuthenticateDevice        │
     ▲                                      │     (cek devices.api_token)  │
     │  201 { geofence_events, ... }         │  2. Validasi: lat, lng,      │
     │                                      │     accuracy < 100 m,        │
┌──────────┐                                 │     speed < 100 km/h         │
│ ADS-B/   │   GET /devices/me/fences        │  3. Simpan location_logs     │
│ config   │   GET /devices/me/config        │     + update last_seen_at    │
│          │   GET /devices/me/commands      │  4. GeofenceService          │
│          │   POST .../command/ack          │     (Ray Casting + Haversine)│
└──────────┘                                 │  5. Simpan geofence_events   │
                                             │  6. Dispatch SendGeofenceAlert│
                                             │     / ProcessLocation        │
                                             └──────────────────────────────┘
```

- Semua endpoint `/api/v1/*` dilindungi `auth.device` (Bearer token) dan rate
  layout sesuai jalur: `locations` 60/menit, `heartbeat` 12/menit,
  endpoint lain 30/menit (kunci per device).
- Job antrian (`jobs` table) menangani pembuatan Alert dan analisis lanjutan
  secara async; jalankan worker dengan `php artisan queue:work`.

## Inti Logika Geofence

- `app/Services/GeofenceService.php` — **lokasi algoritma Ray Casting**
  (`isPointInPolygon`), jarak minimum ke polygon (`distanceToPolygon`, Haversine),
  luas hektar (`calculateAreaHectares`, Shoelace), dan evaluasi posisi hewan
  terhadap semua fence (`checkAnimalPosition`).
  - Poligon dengan < 3 titik → `false` (tidak berisi apa-apa).
  - Titik tepat di tepi polygon → dianggap **di dalam** (behavior terdokumentasi).
  - Anti-spam: bandingkan dengan event terakhir per `(animal, fence)`;
    status tidak berubah → tidak membuat event baru.
- `app/Services/PolygonValidator.php` — validasi geometri fence:
  minimal 3 titik, tidak menyilang (self-intersection), tidak duplikat/terlalu
  dekat (< 5 m), memakai rumus orientasi
  `(q.lng-p.lng)*(r.lat-p.lat) - (q.lat-p.lat)*(r.lng-p.lng)`.

## Kontrak API v1

| Method | Endpoint                          | Keterangan                                       |
| ------ | --------------------------------- | ------------------------------------------------ |
| GET    | `/api`                            | Health check publik                              |
| POST   | `/api/v1/locations`               | Laporan titik GPS (lihat contoh di bawah)        |
| GET    | `/api/v1/devices/me/fences`       | Fence yang di-assign ke hewan pemilik device     |
| GET    | `/api/v1/devices/me/config`       | Interval laporan & ambang baterai                |
| POST   | `/api/v1/devices/me/heartbeat`    | Status perangkat (baterai, firmware)             |
| GET    | `/api/v1/devices/me/commands`     | Command pending untuk device                     |
| POST   | `/api/v1/devices/me/commands/{id}/ack` | Konfirmasi command dieksekusi               |

### Contoh curl (token dari seeder: device `GPS-01 Sapi Titan`)

```bash
TOKEN="tt_test_api_token_gps01_titan_0000000001"
BASE="http://127.0.0.1:8000"

# Laporan lokasi
curl -X POST "$BASE/api/v1/locations" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"latitude":-6.2580,"longitude":106.8350,
       "recorded_at":"2026-09-16T07:00:00Z",
       "accuracy_meters":4.5,"speed_kmh":3.1,
       "battery_level":72}' --fail
```

Respon sukses:

```json
{
  "status": "success",
  "message": "Lokasi diterima.",
  "data": {
    "log_id": 101,
    "geofence_events": [],
    "next_interval_seconds": 60
  }
}
```

Jika hewan keluar dari fence inclusion (atau masuk fence exclusion), array
`geofence_events` berisi event baru beserta `is_alert`, dan backend membuat
record `geofence_events` + `alerts`.

## Setup

```bash
cp .env.example .env          # atur kredensial database (MySQL, DB=database queue)
composer install
php artisan key:generate
php artisan migrate --seed    # akun demo: rudi@ternaktrack.test / password
npm install && npm run build
php artisan serve
```

Seed akun demo (sesi 1-2) ada di `DatabaseSeeder`:
`admin@ternaktrack.test` (admin) dan `rudi@ternaktrack.test` (peternak),
password keduanya `password`.

## Testing

```bash
php artisan test              # Unit (geofence/validator) + Feature (API v1)
```

Suite memakai sqlite `:memory:` (phpunit.xml). Contoh cakupan:
- `GeofenceServiceTest`: titik dalam/luar/tepi, jarak, luas, anti-spam,
  inclusion vs exclusion.
- `PolygonValidatorTest`: minimal titik, bowtie menyilang, duplikat.
- `DeviceApiTest`: auth Bearer, validasi lat/lng/accuracy/speed, event exit +
  alert, fences/config/heartbeat/commands/ack, rate limit 60/menit.
- `DeviceManageTest` & `AnimalManageTest`: CRUD Livewire, scoping pemilik vs
  admin, kebijakan akses (403 non-pemilik), upload foto + resize 800px,
  sinkron pagar virtual, perintah perangkat, rekam kesehatan.
- `MovementStatsServiceTest` / `ImageServiceTest`: statistik harian jarak &
  kecepatan; resize GD (rasio terjaga, PNG transparan, file invalid ditolak).

## Modul Web (Livewire) — SESI 4

CRUD perangkat (GPS tracker) & hewan ternak dengan chart & peta interaktif.

### Rute

| Rute | Nama | Komponen |
| --- | --- | --- |
| `GET /devices` | `devices.index` | `DeviceList` |
| `GET /devices/create` | `devices.create` | `DeviceForm` |
| `GET /devices/{device}` | `devices.show` | `DeviceDetail` |
| `GET /devices/{device}/edit` | `devices.edit` | `DeviceForm` |
| `GET /animals` | `animals.index` | `AnimalList` |
| `GET /animals/create` | `animals.create` | `AnimalForm` |
| `GET /animals/{animal}` | `animals.show` | `AnimalDetail` |
| `GET /animals/{animal}/edit` | `animals.edit` | `AnimalForm` |

### Fitur

- **DeviceList**: pencarian + filter status, paginasi 15/halaman,
  `wire:poll.30s`, indikator ONLINE (lapor < 5 menit) + badge status warna,
  hapus dengan konfirmasi modal.
- **DeviceForm**: validasi real-time (`wire:model.live`), kode unik, template
  `TT-XXXXXXXX` otomatis; setelah create → modal menampilkan `api_token`
  **sekali** dengan tombol salin.
- **DeviceDetail**: grafik baterai 7 hari (Chart.js, mengambil nilai terakhir
  per hari dari tabel `device_battery_logs`), peta polyline rute 24 jam
  (Leaflet), kirim perintah (reboot / set_interval / locate_now), regenerasi
  token dengan konfirmasi ketik **REGENERATE**.
- **AnimalList**: filter spesies & kesehatan, thumbnail foto, badge kesehatan.
- **AnimalForm**: info lengkap hewan, dropdown perangkat yang belum terpasang,
  multi-select pagar virtual (`animal_fence.assigned_at`), upload foto
  JPG/PNG max 2 MB → resize otomatis 800px (GD, rasio terjaga).
- **AnimalDetail**: tab Info / Pergerakan / Zona & Pagar / Rekam Kesehatan;
  chart jarak & kecepatan harian (7 hari, `MovementStatsService`), peta
  pergerakan 24 jam berwarna urutan waktu, riwayat kejadian zone, tombol
  "Lacak Sekarang" → perintah `locate_now`, CRUD rekam kesehatan via modal
  (`HealthRecordForm`).
- **Kebijakan akses**: `DevicePolicy`, `AnimalPolicy`, `HealthRecordPolicy` —
  pemilik hanya dapat lihat/edit/hapus data sendiri; admin melewati semua.

### Perbaikan pasca-verifikasi browser (E2E)

- **Alpine dibuat satu instance**: `resources/js/app.js` tidak lagi
  `import Alpine from 'alpinejs'` (Livewire 3 sudah membundel Alpine di
  `window.Alpine`). Masalah lama — dua instance Alpine memutus `wire:submit`,
  `wire:model.live`, dan modal konfirmasi (contoh: logout berhenti bekerja,
  hewan tidak tersimpan). Store & helper kini didaftarkan lewat event
  `alpine:init` pada instance milik Livewire.
- **Escape string di atribut Blade**: ekspresi `x-on:click` pada tombol hapus
  memakai `\"` di dalam atribut yang dibatasi tanda kutip ganda → browser
  memotong ekspresi ("Invalid or unexpected token"). Diganti atribut ber-tanda
  kutip tunggal / tanpa escape.
- **Enum `health_records.type` diseragamkan** (untuk DB MySQL dev + migrasi):
  menambah `penimbangan` agar cocok dengan opsi di `HealthRecordForm` dan
  `HealthRecordFactory`.

### Verifikasi (tanpa browser)

- `php artisan test` → **115 test, 115 pass** (Unit geofence/validator/services
  + Feature API & manajemen perangkat/hewan).
- `php artisan route:list --path=devices` / `--path=animals` menampilkan
  8 rute Livewire.
- Pemeriksaan HTTP live (login `rudi@ternaktrack.test` / `password`):
  kedelapan halaman mengembalikan `200` dan merender konten masing-masing
  (judul, `battery-chart`, `route-map`, `movement-chart`, `movement-map`,
  `health-record-form`, tab "Rekam Kesehatan", script `/livewire/livewire.js`).
- Verifikasi E2E browser (Chrome headless): tambah hewan → redirect ke
  `/animals` + baris tersimpan di DB; submit form logout → konfirmasi modal →
  logout sukses; tanpa error JS/catatan "multiple instances of Alpine".
- Bundle aset: `npm run build` menghasilkan `app-*.js` (±356 kB, berisi
  Chart.js + Leaflet, tanpa Alpine ganda) dan CSS Leaflet.

## SESI 5: Virtual Fence & Live Map

Modul manajemen pagar virtual (geofence) dan peta pemantauan hewan real-time
berbasis Leaflet + Livewire 3.

### Fitur

- **FenceList**: kartu per pagar dengan peta mini Leaflet (poligon ditambal
  penuh, `SVG path` di-render langsung di kartu), area hektar, perangkat di-assign.
- **FenceForm**: gambar poligon di peta interaktif memakai `leaflet-draw`
  (klik titik, selesaikan dengan klik di titik pertama), validasi
  `PolygonValidator` (min. 3 titik, tidak menyilang, tidak duplikat, luas
  minimal 0,1 ha), info status langkah (legs) + luas area "live" saat menggambar,
  simpan via `wire:submit=save` → redirect ke `/fences`.
- **FenceDetail**: peta dengan poligon + marker perangkat ternak, riwayat
  kejadian geofence, tombol hapus dengan konfirmasi modal.
- **LiveMap**: peta semua fence + marker perangkat (status warna: dalam jalan /
  dalam pagar / di luar pagar), filter status (`[wire:model.live]`), toggle
  tampil/sembunyi per pagar, dan **trail pergerakan** per perangkat (interval
  jam terpilih, polyline berurutan waktu).

### Perbaikan pasca-verifikasi browser (E2E)

- **`@json()` di dalam atribut HTML double-quote** memotong atribut (hasilnya
  berisi tanda kutip ganda) → `data-points`, `data-center`, `data-polygon`,
  `data-animals`, `data-coords` di-blade diubah ke atribut single-quote dengan
  `JSON_HEX_APOS` agar aman.
- **`Livewire.first()` menarget komponen pertama** (badge notifikasi sidebar),
  bukan komponen form → `sync()` / hapus fence memakai
  `Livewire.find(rootEl.getAttribute('wire:id')).set(...)`.
- **`Livewire.find(id)` belum tersedia di `DOMContentLoaded`** (komponen belum
  ter-hydrate) → `ttFenceDetailDelete` menyimpan referensi saat klik, bukan saat
  inisialisasi, sehingga tombol hapus fence berfungsi tanpa `TypeError`.
- **Leaflet z-index bleeding**: pane Leaflet bawaan ber-`z-index: 400` dan naik
  ke stacking context halaman sehingga peta "menembus" backdrop modal hapus.
  Container Leaflet kini `isolation: isolate` (pane terkunci di dalamnya),
  wrapper peta live-map ikut di-isolasi, dan modal konfirmasi dinaikkan ke
  `z-index: 1100` (di atas ambang Leaflet 1000).
- **Escape selector Blade**: `closest('[wire\\:id]')` di blade dirender menjadi
  `'[wire\:id]'` yang string JS-nya menjadi `[wire:id]` (escape `\:` tidak valid)
  → ditulis `'[wire\\\\:id]'` (4 backslash di file).

### Verifikasi (tanpa browser)

- `php artisan test` → **134 test, 134 pass** (Unit geofence/validator/services
  + Feature API & manajemen perangkat/hewan/fence/live map/dashboard).
- Verifikasi E2E browser (Chrome headless, `node e2e-sesi5.mjs`):
  - Gambar poligon 4 titik → `livePoints=4`, luas live `5,8639 ha` → simpan →
    redirect `/fences` → kartu baru menampilkan area yang sama + peta mini
    ter-initialize.
  - Peta mini semua kartu merender poligon (`miniMapHasPolygons=9/9`).
  - Edit fence: polygon dimuat ulang (4 titik, luas `5,8639 ha`), 1 titik
    dihapus lewat mode "Edit Titik" → `3` titik, luas `2,9320 ha`, disimpan dan
    area kartu sinkron (`editCardMatches=true`).
  - Hapus fence via modal konfirmasi ("Hapus Fence?") → redirect `/fences`,
    kartu dan data fence hilang (`fenceGoneAfterDelete=true`); saat modal
    terbuka, `elementFromPoint` di tengah peta mini mengirim ke backdrop
    (`bg-slate-900/60`), bukan pane Leaflet — bukti backdrop menutupi peta.
  - Live map: 3 perangkat ber-lokasi muncul, filter status bekerja, trail
    pergerakan digambar (polyline bertambah; skrip menabur LocationLog segar ke
    jendela 7 jam karena data seed lebih tua dari 7 hari), tanpa error JS
    (`pageErrors=[]`).
  - Detail pagar: poligon + marker + riwayat kejadian tampil.
import './bootstrap';

import Chart from 'chart.js/auto';

window.Chart = Chart;

// Catatan penting:
// Leaflet TIDAK lagi di-bundle oleh Vite. Sesi 5 memuat Leaflet 1.9.4 +
// Leaflet.Draw 1.0.4 via CDN di `layouts/app.blade.php` sehingga tersedia
// sebagai global `window.L` di SEMUA halaman (termasuk peta SESI 4:
// route-map device & movement-map animal). Ini menghindari dua instance
// Leaflet di halaman yang sama (bundle vs CDN).
//
// Alpine juga TIDAK diimpor dari npm. Livewire 3 sudah membundel Alpine dan
// mengeksposnya di `window.Alpine` saat `@livewireScripts` dimuat. Mengimpor
// Alpine sendiri menyebabkan dua instance Alpine berjalan (warn "multiple
// instances") yang membuat `wire:submit`/`x-data`/modal konfirmasi rusak.
// Semua store & helper didaftarkan lewat event `alpine:init`, yang di-fire
// oleh instance Alpine milik Livewire saat inisialisasi.

/**
 * Helper untuk chart & peta yang dipakai komponen Livewire (Sesi 4 & 5).
 */
window.TT = {
    /**
     * Line chart baterai (7 hari).
     */
    batteryChart(canvas, labels, values) {
        if (!canvas) {
            return null;
        }
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Baterai (%)',
                    data: values,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.12)',
                    tension: 0.35,
                    fill: true,
                }],
            },
            options: this.baseChartOptions(),
        });
    },

    /**
     * Bar chart jarak (km) per hari + garis kecepatan rata-rata (km/jam).
     */
    movementChart(canvas, labels, distanceKm, avgSpeedKmh) {
        if (!canvas) {
            return null;
        }
        return new Chart(canvas, {
            data: {
                labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Jarak (km)',
                        data: distanceKm,
                        backgroundColor: 'rgba(22, 163, 74, 0.7)',
                        borderRadius: 6,
                    },
                    {
                        type: 'line',
                        label: 'Kecepatan rata-rata (km/jam)',
                        data: avgSpeedKmh,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        tension: 0.35,
                        yAxisID: 'y2',
                    },
                ],
            },
            options: {
                ...this.baseChartOptions(),
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: this.gridColor() } },
                    y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
                },
            },
        });
    },

    /**
     * Peta polyline riwayat lokasi (device detail).
     */
    routeMap(divId, points) {
        return this.commonMap(divId, points);
    },

    /**
     * Peta polyline berwarna sesuai urutan waktu (animal detail).
     */
    movementMap(divId, points) {
        return this.commonMap(divId, points);
    },

    commonMap(divId, points) {
        const L = window.L;
        const container = document.getElementById(divId);
        if (!container || !L || points.length < 3) {
            return null;
        }

        const map = L.map(divId).setView([points[0].lat, points[0].lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const colorStep = points.length > 1 ? 360 / (points.length - 1) : 0;
        const latlngs = points.map((p) => [p.lat, p.lng]);

        // Polyline utuh sebagai dasar.
        L.polyline(latlngs, { color: '#16a34a', weight: 3, opacity: 0.5 }).addTo(map);

        // Segmen per-titik dengan warna bergeser (waktu → warna).
        for (let i = 0; i < points.length - 1; i++) {
            const hue = Math.round(i * colorStep);
            L.polyline([latlngs[i], latlngs[i + 1]], {
                color: `hsl(${hue}, 80%, 45%)`,
                weight: 4,
                opacity: 0.9,
            }).addTo(map);
        }

        L.marker(latlngs[0]).addTo(map).bindPopup('Mulai');
        L.marker(latlngs[latlngs.length - 1]).addTo(map).bindPopup('Terakhir');

        map.fitBounds(latlngs, { padding: [40, 40] });
        return map;
    },

    /**
     * Buat layer polygon (fence) dari array titik {lat,lng} atau [lat,lng].
     * Mengembalikan null bila titik < 3.
     */
    fenceLayer(points, color = '#22c55e') {
        const L = window.L;
        if (!L || !Array.isArray(points) || points.length < 3) {
            return null;
        }
        const latlngs = points.map((p) => (
            Array.isArray(p) ? [p[0], p[1]] : [p.lat, p.lng]
        ));
        return L.polygon(latlngs, {
            color,
            weight: 2,
            fillColor: color,
            fillOpacity: 0.18,
        });
    },

    /**
     * Icon marker hewan berstatus (Sesi 5): hijau di dalam fence, merah di
     * luar, abu-abu bila tidak ter-assign ke fence manapun, status "--" tanpa
     * lokasi.
     */
    animalStatusIcon(status) {
        const L = window.L;
        const dot = document.createElement('div');
        dot.className = 'tt-animal-pin';
        dot.dataset.status = status;
        return L.divIcon({
            className: 'tt-animal-icon',
            html: dot.outerHTML,
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -12],
        });
    },

    /**
     * Inisialisasi mini-map preview polygon untuk semua elemen
     * `.fence-mini-map[data-coords]` di halaman (FenceList). Aman dipanggil
     * berulang (elemen yang sudah di-init dilewati).
     */
    initMiniFenceMaps() {
        const L = window.L;
        if (!L) {
            return;
        }
        document.querySelectorAll('.fence-mini-map:not([data-inited])').forEach((container) => {
            let points = [];
            try {
                points = JSON.parse(container.dataset.coords || '[]');
            } catch (e) {
                points = [];
            }
            const color = container.dataset.color || '#22c55e';
            if (!Array.isArray(points) || points.length < 3) {
                container.innerHTML = '<p class="text-xs text-slate-400 p-3">Belum ada polygon.</p>';
                container.dataset.inited = '1';
                return;
            }
            const latlngs = points.map((p) => [p.lat, p.lng]);
            const map = L.map(container, {
                zoomControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                tap: false,
                attributionControl: false,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { crossOrigin: true }).addTo(map);
            L.polygon(latlngs, { color, weight: 2, fillColor: color, fillOpacity: 0.25 }).addTo(map);
            map.fitBounds(latlngs, { padding: [6, 6] });
            container.dataset.inited = '1';
        });
    },

    /**
     * Format luas dalam hektar untuk tampilan.
     */
    fmtAreaHectares(ha, decimals = 2) {
        if (ha === null || ha === undefined || ha <= 0) {
            return '—';
        }
        const value = Number(ha);
        return `${new Intl.NumberFormat('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value)} ha`;
    },

    baseChartOptions() {
        const dark = document.documentElement.classList.contains('dark');
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: dark ? '#cbd5e1' : '#374151' } },
            },
            scales: {
                x: { ticks: { color: dark ? '#94a3b8' : '#64748b' }, grid: { color: this.gridColor() } },
                y: { beginAtZero: true, max: 100, ticks: { color: dark ? '#94a3b8' : '#64748b' }, grid: { color: this.gridColor() } },
            },
        };
    },

    gridColor() {
        return document.documentElement.classList.contains('dark') ? 'rgba(148, 163, 184, 0.15)' : 'rgba(100, 116, 139, 0.12)';
    },

    darkText() {
        return (typeof document === 'undefined') ? false : document.documentElement.classList.contains('dark');
    },
};

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // ---- Theme (dark mode) ----
    Alpine.store('theme', {
        dark: false,

        init() {
            const saved = localStorage.getItem('tt_theme');
            if (saved === 'dark') {
                this.dark = true;
            } else if (saved === 'light') {
                this.dark = false;
            } else {
                this.dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            this.apply();
        },

        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('tt_theme', this.dark ? 'dark' : 'light');
            this.apply();
        },

        apply() {
            document.documentElement.classList.toggle('dark', this.dark);
        },
    });

    // ---- Toast notifications ----
    Alpine.store('toast', {
        items: [],

        show(title, message = '', type = 'success', timeout = 4000) {
            const id = Date.now() + Math.random();
            const icons = {
                success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            };
            this.items.push({ id, title, message, type, icons });
            setTimeout(() => {
                this.items = this.items.filter((i) => i.id !== id);
            }, timeout);
        },

        success(title, message = '') {
            this.show(title, message, 'success');
        },

        error(title, message = '') {
            this.show(title, message, 'error', 6000);
        },

        warning(title, message = '') {
            this.show(title, message, 'warning');
        },

        info(title, message = '') {
            this.show(title, message, 'info');
        },
    });

    // ---- Confirmation modal ----
    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        confirmLabel: 'Ya, lanjutkan',
        danger: true,
        onConfirm: null,

        ask(title, message, onConfirm, options = {}) {
            this.title = title;
            this.message = message;
            this.onConfirm = onConfirm || null;
            this.confirmLabel = options.confirmLabel || 'Ya, lanjutkan';
            this.danger = options.danger ?? true;
            this.open = true;
        },

        cancel() {
            this.open = false;
            this.onConfirm = null;
        },

        confirm() {
            const cb = this.onConfirm;
            this.open = false;
            this.onConfirm = null;
            if (typeof cb === 'function') {
                cb();
            }
        },
    });

    window.Alpine.confirmModal = (title, message, onConfirm, options = {}) => {
        window.Alpine.store('confirm').ask(title, message, onConfirm, options);
    };

    // ---- Loading bar ----
    Alpine.store('loading', {
        visible: false,
        progress: 0,
        timer: null,

        start() {
            this.visible = true;
            this.progress = 10;
            this.timer = setInterval(() => {
                this.progress = Math.min(this.progress + Math.random() * 20, 90);
            }, 200);
        },

        finish() {
            clearInterval(this.timer);
            this.progress = 100;
            setTimeout(() => {
                this.visible = false;
                this.progress = 0;
            }, 300);
        },
    });
});

// Watch full-page navigations
window.addEventListener('beforeunload', () => window.Alpine?.store('loading')?.start());

window.addEventListener('load', () => {
    const loading = window.Alpine?.store('loading');
    if (loading?.visible) {
        loading.finish();
    }
});

// Expose toast helpers for Livewire dispatch events
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ succeed }) => {
        succeed(() => {
            if (window.Alpine?.store('loading')?.visible) {
                window.Alpine.store('loading').finish();
            }
        });
    });
});
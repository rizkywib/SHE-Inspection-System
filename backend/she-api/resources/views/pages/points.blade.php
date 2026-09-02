@extends('layouts.app')

@section('title', 'Points')
@section('nav-points', 'active')

@section('content')
<style>
    #pointMap {
        height: 360px;
        min-height: 360px;
        background: #f8fafc;
        position: relative;
        overflow: hidden;
    }
    .leaflet-container { border-radius: 0.75rem; }
    #pointMap.offline-coordinate-map {
        background-color: #e5eef8;
        background-image:
            linear-gradient(rgba(15, 23, 42, 0.12) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15, 23, 42, 0.12) 1px, transparent 1px),
            radial-gradient(circle at center, rgba(37, 99, 235, 0.12), transparent 38%);
        background-size: 48px 48px, 48px 48px, 100% 100%;
    }
    .offline-map-pin {
        width: 22px;
        height: 22px;
        border-radius: 9999px 9999px 9999px 0;
        background: #dc2626;
        border: 3px solid #ffffff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.28);
        cursor: grab;
        position: absolute;
        transform: translate(-50%, -100%) rotate(-45deg);
        z-index: 2;
    }
    .offline-map-pin:active { cursor: grabbing; }
    .offline-map-pin::after {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        background: #ffffff;
        position: absolute;
        left: 5px;
        top: 5px;
    }
    .offline-current-position {
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background: #2563eb;
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 8px rgba(37, 99, 235, 0.18);
        position: absolute;
        transform: translate(-50%, -50%);
        z-index: 1;
    }
    .map-locate-button {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        color: #1f2937;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        padding: 9px 11px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.16);
    }
    .map-locate-button:hover { background: #f8fafc; }
    .map-locate-button:disabled {
        cursor: wait;
        opacity: 0.7;
    }
    .offline-map-locate-button {
        position: absolute;
        right: 12px;
        top: 12px;
        z-index: 3;
    }
    .qr-print-logo {
        width: 48px;
        height: 64px;
        object-fit: contain;
        display: block;
        margin: 0 auto 12px;
    }
    @media print {
        body * { visibility: hidden; }
        #qrPrintArea, #qrPrintArea * { visibility: visible; }
        #qrPrintArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 24px;
            text-align: center;
        }
        .no-print { display: none !important; }
    }
</style>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Points</h1>
        <p class="text-gray-600 mt-1">Manage inspection points</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Point
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Point</h2>
        <form id="pointForm" class="space-y-6">
            <input type="hidden" id="point_id">

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-5 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Informasi Point</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-1 gap-4">
                        <div class="md:col-span-2 xl:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input id="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Keterangan</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan 1</label>
                            <textarea id="keterangan1" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan 2</label>
                            <textarea id="keterangan2" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-7 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Lokasi Map</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" onclick="useCurrentPosition()" id="currentPositionButton" data-current-position-button class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                <i class="fas fa-crosshairs mr-1"></i>Posisi Saat Ini
                            </button>
                            <button type="button" onclick="clearMapTileCache()" class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                                <i class="fas fa-database mr-1"></i>Clear Cache
                            </button>
                        </div>
                    </div>

                    <div id="pointMap" class="w-full border border-gray-200 shadow-sm rounded-xl flex items-center justify-center text-sm text-gray-500">
                        <span id="mapStatus">Map akan dimuat saat form dibuka.</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input id="latitude" type="number" step="any" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input id="longitude" type="number" step="any" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Show</label>
            <select id="pageSize" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-sm text-gray-500">entries</span>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Search:</label>
            <input id="searchFilter" type="text" placeholder="Search points..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="pointTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginationControls" class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div id="paginationInfo" class="text-sm text-gray-600"></div>
            <div class="flex items-center gap-2">
                <button id="prevPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <span id="pageNumbers" class="text-sm text-gray-700"></span>
                <button id="nextPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                <button id="lastPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Last</button>
            </div>
        </div>
    </div>

    <div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 no-print">
                <h3 class="text-lg font-semibold text-gray-900">QR Code Point</h3>
                <button type="button" onclick="closeQrModal()" class="text-gray-400 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="qrPrintArea" class="p-6 text-center">
                <img id="qrPrintLogo" src="/images/ecogreen-logo-print.png" alt="Ecogreen Oleochemicals" class="qr-print-logo">
                <h4 id="qrPointName" class="text-xl font-bold text-gray-900 mb-4"></h4>
                <img id="qrImage" alt="Point QR Code" class="mx-auto w-56 h-56 border border-gray-200 rounded-lg p-2 bg-white">
                <p id="qrCodeText" class="mt-4 text-sm font-mono text-gray-700 break-all whitespace-pre-line"></p>
                <p id="qrGeneratedAt" class="mt-1 text-xs text-gray-500"></p>
            </div>
            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 no-print">
                <button type="button" onclick="closeQrModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                    Close
                </button>
                <button type="button" onclick="printQr()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let pointMap;
let pointMarker;
let currentPositionMarker;
let leafletLoadPromise;
let tileDbPromise;
let offlineMapActive = false;
let offlinePin;
let offlineCurrentMarker;
let offlineMapCenter = { lat: -6.200000, lng: 106.816666 };
const offlineMapSpan = { lat: 0.08, lng: 0.08 };
let points = [];
let currentPage = 1;
let pageSize = 25;
let searchFilter = '';
const defaultPoint = [-6.200000, 106.816666];

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
        .replace(/'/g, '&#039;');
}

function hasCoordinates(loc) {
    return loc.lat !== null && loc.lat !== undefined && loc.lat !== '' &&
        loc.lng !== null && loc.lng !== undefined && loc.lng !== '';
}

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString('id-ID');
}

function qrImageUrl(point) {
    return `https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=12&data=${encodeURIComponent(point.qr_code)}`;
}

function viewQr(id) {
    const point = points.find(item => item.id === id);
    if (!point || !point.qr_code) {
        alert('QR Code belum tersedia. Simpan point terlebih dahulu.');
        return;
    }

    document.getElementById('qrPointName').textContent = point.name_point || '-';
    document.getElementById('qrImage').src = qrImageUrl(point);
    document.getElementById('qrCodeText').textContent = point.qr_code;
    document.getElementById('qrGeneratedAt').textContent = `Generated: ${formatDateTime(point.qr_generated_at)}`;
    document.getElementById('qrModal').classList.remove('hidden');
}

function closeQrModal() {
    document.getElementById('qrModal').classList.add('hidden');
}

function printQr() {
    window.print();
}

function statusBadge(status) {
    const value = Number(status) === 1 ? 'active' : 'inactive';
    const classes = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800'
    };
    return `<span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${classes[value] || classes.active}">${escapeHtml(value)}</span>`;
}

function setMapStatus(message, isError = false) {
    const status = document.getElementById('mapStatus');
    if (!status) return;

    status.textContent = message;
    status.classList.toggle('text-red-600', isError);
    status.classList.toggle('text-gray-500', !isError);
}

function hideMapStatus() {
    const status = document.getElementById('mapStatus');
    if (status) status.classList.add('hidden');
}

function showMapStatus() {
    const status = document.getElementById('mapStatus');
    if (status) status.classList.remove('hidden');
}

function loadLeaflet() {
    if (window.L) return Promise.resolve();
    if (leafletLoadPromise) return leafletLoadPromise;
    if (navigator.onLine === false) return Promise.reject(new Error('Offline'));

    leafletLoadPromise = new Promise((resolve, reject) => {
        if (!document.getElementById('leafletCss')) {
            const cssSources = [
                'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
            ];
            const link = document.createElement('link');
            link.id = 'leafletCss';
            link.rel = 'stylesheet';
            link.href = cssSources[0];
            link.onerror = () => {
                if (link.href !== cssSources[1]) link.href = cssSources[1];
            };
            document.head.appendChild(link);
        }

        const sources = [
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
        ];

        function trySource(index) {
            if (index >= sources.length) {
                reject(new Error('Leaflet failed to load'));
                return;
            }

            const script = document.createElement('script');
            script.src = sources[index];
            script.async = true;
            const timeout = setTimeout(() => {
                script.onload = null;
                script.onerror = null;
                script.remove();
                trySource(index + 1);
            }, 6000);

            script.onload = () => {
                clearTimeout(timeout);
                if (window.L) {
                    resolve();
                    return;
                }
                trySource(index + 1);
            };
            script.onerror = () => {
                clearTimeout(timeout);
                script.remove();
                trySource(index + 1);
            };
            document.body.appendChild(script);
        }

        trySource(0);
    });

    return leafletLoadPromise;
}

function openTileDb() {
    if (!('indexedDB' in window)) return Promise.reject(new Error('IndexedDB unavailable'));
    if (tileDbPromise) return tileDbPromise;

    tileDbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open('she-point-map-tiles', 1);
        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('tiles')) db.createObjectStore('tiles');
        };
        request.onsuccess = event => resolve(event.target.result);
        request.onerror = () => reject(request.error);
    });

    return tileDbPromise;
}

async function getCachedTile(key) {
    const db = await openTileDb();
    return new Promise((resolve, reject) => {
        const request = db.transaction('tiles', 'readonly').objectStore('tiles').get(key);
        request.onsuccess = () => resolve(request.result || null);
        request.onerror = () => reject(request.error);
    });
}

async function cacheTile(key, blob) {
    if (!blob || !blob.size) return;

    const db = await openTileDb();
    await new Promise((resolve, reject) => {
        const request = db.transaction('tiles', 'readwrite').objectStore('tiles').put(blob, key);
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

function createCachedTileLayer(url, options, cachePrefix) {
    const CachedTileLayer = L.TileLayer.extend({
        createTile(coords, done) {
            const tile = document.createElement('img');
            tile.alt = '';
            tile.setAttribute('role', 'presentation');
            tile.crossOrigin = 'anonymous';

            const tileUrl = this.getTileUrl(coords);
            const key = `${cachePrefix}:${coords.z}:${coords.x}:${coords.y}`;
            let doneCalled = false;

            tile.onload = () => {
                if (doneCalled) return;
                doneCalled = true;
                done(null, tile);
            };
            tile.onerror = () => {
                if (doneCalled) return;
                doneCalled = true;
                done(new Error('Tile load error'), tile);
            };

            loadCachedTile(tile, key, tileUrl);
            return tile;
        }
    });

    return new CachedTileLayer(url, options);
}

async function loadCachedTile(tile, key, tileUrl) {
    let cached = null;

    try {
        cached = await getCachedTile(key);
        if (cached && navigator.onLine === false) {
            tile.src = URL.createObjectURL(cached);
            return;
        }
    } catch (error) {
        cached = null;
    }

    try {
        const response = await fetch(tileUrl, { mode: 'cors' });
        if (!response.ok) throw new Error('Tile fetch failed');

        const blob = await response.blob();
        cacheTile(key, blob).catch(() => {});
        tile.src = URL.createObjectURL(blob);
        return;
    } catch (error) {
        if (cached) {
            tile.src = URL.createObjectURL(cached);
            return;
        }

        tile.src = tileUrl;
    }
}

function clearMapTileCache() {
    if (!('indexedDB' in window)) {
        alert('Cache map tidak tersedia di browser ini.');
        return;
    }

    tileDbPromise = null;
    const request = indexedDB.deleteDatabase('she-point-map-tiles');
    request.onsuccess = () => alert('Cache map berhasil dibersihkan.');
    request.onerror = () => alert('Cache map gagal dibersihkan.');
}

function initPointMap() {
    if (pointMap || typeof L === 'undefined') return;

    offlineMapActive = false;
    const mapElement = document.getElementById('pointMap');
    mapElement.classList.remove('offline-coordinate-map', 'flex', 'items-center', 'justify-center', 'text-sm', 'text-gray-500');
    mapElement.innerHTML = '';

    const streetLayer = createCachedTileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }, 'street');
    const satelliteLayer = createCachedTileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Tiles &copy; Esri'
    }, 'satellite');
    const satelliteLabelLayer = createCachedTileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Labels &copy; Esri'
    }, 'satellite-labels');
    const satelliteRoadLayer = createCachedTileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Road labels &copy; Esri'
    }, 'satellite-roads');
    const satelliteHybridLayer = L.layerGroup([
        satelliteLayer,
        satelliteRoadLayer,
        satelliteLabelLayer
    ]);

    pointMap = L.map('pointMap', {
        center: defaultPoint,
        zoom: 13,
        layers: [streetLayer]
    });

    L.control.layers({
        'Street': streetLayer,
        'Satellite + Label': satelliteHybridLayer
    }).addTo(pointMap);
    addLeafletLocateControl();

    pointMarker = L.marker(defaultPoint, { draggable: true }).addTo(pointMap);
    pointMarker.on('dragend', () => updateCoordinates(pointMarker.getLatLng(), false));
    pointMap.on('click', event => updateCoordinates(event.latlng, true));
    hideMapStatus();
}

function addLeafletLocateControl() {
    const LocateControl = L.Control.extend({
        options: { position: 'topright' },
        onAdd() {
            const container = L.DomUtil.create('div', 'leaflet-bar');
            const button = L.DomUtil.create('button', 'map-locate-button', container);
            button.type = 'button';
            button.title = 'Gunakan posisi saat ini';
            button.setAttribute('data-current-position-button', '');
            button.innerHTML = '<i class="fas fa-crosshairs mr-1"></i>Posisi Saat Ini';

            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.on(button, 'click', event => {
                L.DomEvent.preventDefault(event);
                useCurrentPosition();
            });

            return container;
        }
    });

    pointMap.addControl(new LocateControl());
}

function initOfflineMap() {
    if (offlineMapActive) return;

    offlineMapActive = true;
    const mapElement = document.getElementById('pointMap');
    mapElement.classList.add('offline-coordinate-map');
    mapElement.classList.remove('flex', 'items-center', 'justify-center', 'text-gray-500');
    mapElement.innerHTML = `
        <span id="mapStatus" class="absolute left-3 top-3 bg-white bg-opacity-95 text-xs text-gray-600 px-3 py-2 rounded-lg shadow">Offline map</span>
        <button type="button" onclick="useCurrentPosition()" data-current-position-button class="map-locate-button offline-map-locate-button">
            <i class="fas fa-crosshairs mr-1"></i>Posisi Saat Ini
        </button>
    `;

    offlinePin = document.createElement('div');
    offlinePin.className = 'offline-map-pin';
    mapElement.appendChild(offlinePin);

    offlineCurrentMarker = document.createElement('div');
    offlineCurrentMarker.className = 'offline-current-position hidden';
    mapElement.appendChild(offlineCurrentMarker);

    mapElement.addEventListener('click', event => {
        if (event.target.closest('button')) return;
        if (event.target === offlinePin) return;
        const latlng = offlinePointToLatLng(event.offsetX, event.offsetY);
        updateCoordinates(latlng, true);
    });

    let dragging = false;
    offlinePin.addEventListener('pointerdown', event => {
        dragging = true;
        offlinePin.setPointerCapture(event.pointerId);
        event.preventDefault();
    });
    offlinePin.addEventListener('pointermove', event => {
        if (!dragging) return;
        const rect = mapElement.getBoundingClientRect();
        const latlng = offlinePointToLatLng(event.clientX - rect.left, event.clientY - rect.top);
        updateCoordinates(latlng, true);
    });
    offlinePin.addEventListener('pointerup', () => { dragging = false; });
    offlinePin.addEventListener('pointercancel', () => { dragging = false; });

    moveMarkerFromInputs(true);
}

async function refreshMap() {
    setMapStatus('Memuat map...');

    try {
        await loadLeaflet();
        initPointMap();
        if (!pointMap) return;

        setTimeout(() => {
            pointMap.invalidateSize();
            moveMarkerFromInputs(true);
        }, 150);
    } catch (error) {
        initOfflineMap();
        setMapStatus('Offline map aktif. Satellite/Street akan memakai cache jika Leaflet pernah dimuat online.', false);
    }
}

function updateCoordinates(latlng, moveMarker = true) {
    document.getElementById('latitude').value = Number(latlng.lat).toFixed(8);
    document.getElementById('longitude').value = Number(latlng.lng).toFixed(8);
    if (moveMarker && pointMarker) {
        pointMarker.setLatLng(latlng);
        pointMap.panTo(latlng);
    }
    if (moveMarker && offlineMapActive) {
        moveOfflinePin(latlng);
    }
}

function moveMarkerFromInputs(zoomToPoint = true) {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    const latlng = Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : defaultPoint;

    if (pointMap && pointMarker) {
        pointMarker.setLatLng(latlng);
        if (zoomToPoint) pointMap.setView(latlng, Number.isFinite(lat) && Number.isFinite(lng) ? 16 : 13);
    }

    if (offlineMapActive) {
        const offlineLatLng = { lat: latlng[0], lng: latlng[1] };
        if (zoomToPoint) centerOfflineMap(offlineLatLng);
        moveOfflinePin(offlineLatLng);
    }
}

function centerOfflineMap(latlng) {
    offlineMapCenter = { lat: Number(latlng.lat), lng: Number(latlng.lng) };
}

function offlineLatLngToPoint(latlng) {
    const mapElement = document.getElementById('pointMap');
    const width = mapElement.clientWidth || 1;
    const height = mapElement.clientHeight || 1;
    const x = ((Number(latlng.lng) - offlineMapCenter.lng) / offlineMapSpan.lng + 0.5) * width;
    const y = (0.5 - (Number(latlng.lat) - offlineMapCenter.lat) / offlineMapSpan.lat) * height;

    return {
        x: Math.max(0, Math.min(width, x)),
        y: Math.max(0, Math.min(height, y))
    };
}

function offlinePointToLatLng(x, y) {
    const mapElement = document.getElementById('pointMap');
    const width = mapElement.clientWidth || 1;
    const height = mapElement.clientHeight || 1;

    return {
        lat: offlineMapCenter.lat + (0.5 - (y / height)) * offlineMapSpan.lat,
        lng: offlineMapCenter.lng + ((x / width) - 0.5) * offlineMapSpan.lng
    };
}

function moveOfflinePin(latlng) {
    if (!offlinePin) return;

    const point = offlineLatLngToPoint(latlng);
    offlinePin.style.left = `${point.x}px`;
    offlinePin.style.top = `${point.y}px`;
}

function moveOfflineCurrentMarker(latlng) {
    if (!offlineCurrentMarker) return;

    const point = offlineLatLngToPoint(latlng);
    offlineCurrentMarker.classList.remove('hidden');
    offlineCurrentMarker.style.left = `${point.x}px`;
    offlineCurrentMarker.style.top = `${point.y}px`;
}

function setCurrentPositionButtonsLoading(isLoading) {
    document.querySelectorAll('[data-current-position-button]').forEach(button => {
        button.disabled = isLoading;
        button.classList.toggle('opacity-70', isLoading);
        button.classList.toggle('cursor-wait', isLoading);
    });
}

function useCurrentPosition() {
    if (!navigator.geolocation) {
        alert('Current position tidak didukung di browser ini.');
        return;
    }

    setCurrentPositionButtonsLoading(true);

    navigator.geolocation.getCurrentPosition(position => {
        const latlng = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
        };

        updateCoordinates(latlng, true);

        if (pointMap) {
            pointMap.setView(latlng, 17);
            if (!currentPositionMarker) {
                currentPositionMarker = L.circleMarker(latlng, {
                    radius: 8,
                    color: '#ffffff',
                    weight: 3,
                    fillColor: '#2563eb',
                    fillOpacity: 1
                }).addTo(pointMap);
            } else {
                currentPositionMarker.setLatLng(latlng);
            }
        }

        if (offlineMapActive) {
            centerOfflineMap(latlng);
            moveOfflinePin(latlng);
            moveOfflineCurrentMarker(latlng);
        }

        setCurrentPositionButtonsLoading(false);
    }, error => {
        alert(error.message || 'Current position gagal didapatkan.');
        setCurrentPositionButtonsLoading(false);
    }, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 60000
    });
}

async function loadPoints() {
    const res = await fetch(`${API_URL}/points`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    points = Array.isArray(json.data) ? json.data : [];
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('pointTable');
    const search = searchFilter.toLowerCase().trim();

    let filtered = points;
    if (search) {
        filtered = points.filter(loc => {
            return String(loc.id).includes(search) ||
                (loc.name_point || '').toLowerCase().includes(search) ||
                (loc.lat || '').toLowerCase().includes(search) ||
                (loc.lng || '').toLowerCase().includes(search) ||
                (loc.ket1 || '').toLowerCase().includes(search) ||
                (loc.ket2 || '').toLowerCase().includes(search) ||
                (loc.qr_code || '').toLowerCase().includes(search) ||
                (Number(loc.status) === 1 ? 'active' : 'inactive').includes(search);
        });
    }

    const totalItems = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * pageSize;
    const end = Math.min(start + pageSize, totalItems);
    const pageItems = filtered.slice(start, end);

    if (totalItems === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No points found</td></tr>';
    } else {
        tbody.innerHTML = pageItems.map((loc, index) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-900">${start + index + 1}</td>
                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(loc.name_point)}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${hasCoordinates(loc) ? `${escapeHtml(loc.lat)}, ${escapeHtml(loc.lng)}` : '-'}</td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div>${escapeHtml(loc.ket1 || '-')}</div>
                    <div class="text-xs text-gray-400 mt-1">${escapeHtml(loc.ket2 || '-')}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    ${loc.qr_code ? `
                        <button onclick="viewQr(${loc.id})" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            <i class="fas fa-qrcode mr-1"></i>View
                        </button>
                        <div class="text-xs font-mono text-gray-400 mt-1 whitespace-pre-line">${escapeHtml(loc.qr_code)}</div>
                    ` : '<span class="text-gray-400">Belum ada</span>'}
                </td>
                <td class="px-6 py-4 text-sm">${statusBadge(loc.status)}</td>
                <td class="px-6 py-4 text-sm">
                    <button onclick="editItem(${loc.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button onclick="deleteItem(${loc.id})" class="text-red-600 hover:text-red-800 font-medium">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </td>
            </tr>
        `).join('');
    }

    const info = document.getElementById('paginationInfo');
    if (totalItems === 0) {
        info.textContent = 'Showing 0 to 0 of 0 entries';
    } else {
        info.textContent = `Showing ${start + 1} to ${end} of ${totalItems} entries`;
    }

    const pageNumbers = document.getElementById('pageNumbers');
    pageNumbers.textContent = `Page ${currentPage} of ${totalPages}`;

    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
    document.getElementById('lastPage').disabled = currentPage >= totalPages;
}

function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Point';
    document.getElementById('pointForm').reset();
    document.getElementById('point_id').value = '';
    document.getElementById('status').value = 'active';
    refreshMap();
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }

function editItem(id) {
    const loc = points.find(item => item.id === id);
    if (!loc) return;

    openForm();
    document.getElementById('formTitle').textContent = 'Edit Point';
    document.getElementById('point_id').value = loc.id;
    document.getElementById('name').value = loc.name_point || '';
    document.getElementById('latitude').value = loc.lat || '';
    document.getElementById('longitude').value = loc.lng || '';
    document.getElementById('keterangan1').value = loc.ket1 || '';
    document.getElementById('keterangan2').value = loc.ket2 || '';
    document.getElementById('status').value = Number(loc.status) === 1 ? 'active' : 'inactive';
    refreshMap();
}

document.getElementById('latitude').addEventListener('input', () => moveMarkerFromInputs(false));
document.getElementById('longitude').addEventListener('input', () => moveMarkerFromInputs(false));

document.getElementById('pointForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('point_id').value;
    const payload = {
        name_point: document.getElementById('name').value,
        lat: document.getElementById('latitude').value || null,
        lng: document.getElementById('longitude').value || null,
        ket1: document.getElementById('keterangan1').value || null,
        ket2: document.getElementById('keterangan2').value || null,
        status: document.getElementById('status').value === 'active' ? 1 : 0,
    };
    const url = id ? `${API_URL}/points/${id}` : `${API_URL}/points`;
    const method = id ? 'PUT' : 'POST';
    const res = await fetch(url, {
        method,
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    if (!res.ok) { alert('Save failed'); return; }
    closeForm();
    loadPoints();
});

async function deleteItem(id) {
    if (!confirm('Delete this point?')) return;
    const res = await fetch(`${API_URL}/points/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (res.ok) loadPoints();
}

function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
        .finally(() => { localStorage.clear(); window.location.href='/'; });
}

document.getElementById('pageSize').addEventListener('change', function() {
    pageSize = parseInt(this.value);
    currentPage = 1;
    renderTable();
});

document.getElementById('searchFilter').addEventListener('input', function() {
    searchFilter = this.value;
    currentPage = 1;
    renderTable();
});

document.getElementById('prevPage').addEventListener('click', function() {
    if (currentPage > 1) {
        currentPage--;
        renderTable();
    }
});

function getFilteredCount() {
    const search = searchFilter.toLowerCase().trim();
    if (!search) return points.length;
    return points.filter(loc => {
        return String(loc.id).includes(search) ||
            (loc.name_point || '').toLowerCase().includes(search) ||
            (loc.lat || '').toLowerCase().includes(search) ||
            (loc.lng || '').toLowerCase().includes(search) ||
            (loc.ket1 || '').toLowerCase().includes(search) ||
            (loc.ket2 || '').toLowerCase().includes(search) ||
            (loc.qr_code || '').toLowerCase().includes(search) ||
            (Number(loc.status) === 1 ? 'active' : 'inactive').includes(search);
    }).length;
}

document.getElementById('nextPage').addEventListener('click', function() {
    const totalPages = Math.max(1, Math.ceil(getFilteredCount() / pageSize));
    if (currentPage < totalPages) {
        currentPage++;
        renderTable();
    }
});

document.getElementById('lastPage').addEventListener('click', function() {
    const totalPages = Math.max(1, Math.ceil(getFilteredCount() / pageSize));
    if (currentPage < totalPages) {
        currentPage = totalPages;
        renderTable();
    }
});

loadPoints();
</script>
@endsection

@extends('layouts.app')

@section('title', $inspectionId ? 'Edit Fire Extinguisher Inspection' : 'New Fire Extinguisher Inspection')
@section('nav-fire-extinguishers', 'active')

@section('content')
<div class="p-8">
    <div class="mb-6 flex items-center gap-4">
        <a href="/dashboard/fire-extinguishers#inspection-list" class="text-gray-500 hover:text-gray-800">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 id="pageTitle" class="text-3xl font-bold text-gray-900">{{ $inspectionId ? 'Edit Inspection' : 'New Inspection' }}</h1>
            <p id="pageSubtitle" class="text-gray-600 mt-1">Fire Extinguisher Inspection</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <form id="extinguisherForm" class="space-y-6">
            <input type="hidden" id="reference_no">

            <section>
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide border-b border-gray-200 pb-3 mb-4">Inspection Data</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label>
                        <input id="inspection_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi APAR</label>
                        <select id="location_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">- Select Location -</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inspector</label>
                        <select id="inspector_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">- Current User -</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-200 pt-6">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Item APAR</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">List APAR</label>
                        <select id="point_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">- Select APAR -</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe (Ket 1)</label>
                        <input id="item_type" type="text" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detail Lokasi (Ket 2)</label>
                        <input id="item_location_detail" type="text" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-200 pt-6">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Kondisi APAR</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach ([
                        ['name' => 'item_pressure_condition', 'label' => 'Pressure Condition'],
                        ['name' => 'item_seal_condition', 'label' => 'Seal Condition'],
                        ['name' => 'item_nozzle_condition', 'label' => 'Nozzle Condition'],
                    ] as $condition)
                    <div class="border border-gray-200 rounded-lg px-3 py-3">
                        <span class="block text-sm font-medium text-gray-700 mb-2">{{ $condition['label'] }}</span>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-1 text-sm"><input name="{{ $condition['name'] }}" type="radio" value="1"> YES</label>
                            <label class="flex items-center gap-1 text-sm"><input name="{{ $condition['name'] }}" type="radio" value="0" checked> NO</label>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sebelum</label>
                        <input id="item_photo_before" type="file" accept="image/*,.heic,.heif" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP, HEIC. Maksimal 10 MB.</p>
                        <div id="current_photo_before" class="mt-2 text-xs text-gray-400"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sesudah</label>
                        <input id="item_photo_after" type="file" accept="image/*,.heic,.heif" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP, HEIC. Maksimal 10 MB.</p>
                        <div id="current_photo_after" class="mt-2 text-xs text-gray-400"></div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remark</label>
                        <textarea id="item_remark" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-3 pt-2">
                <button id="saveButton" type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 disabled:opacity-60">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
                <a href="/dashboard/fire-extinguishers#inspection-list" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const API_URL = '/api';
    const inspectionId = @json($inspectionId);
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const pageParams = new URLSearchParams(window.location.search);
    const inheritedLocationId = pageParams.get('location_id');
    const inheritedInspectorId = pageParams.get('inspector_id');
    const inheritedInspectionDate = pageParams.get('inspection_date');
    const isItemCreate = !inspectionId && Boolean(inheritedLocationId);
    let points = [];

    if (!token) {
        window.location.href = '/';
        return;
    }
    document.getElementById('userName').textContent = user.name || 'User';
    if (isItemCreate) {
        document.getElementById('pageTitle').textContent = 'Create New Fire Extinguisher Item';
        document.getElementById('pageSubtitle').textContent = 'Lokasi APAR dan Inspector mengikuti inspection yang dipilih.';
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function formatDate(value) { return value ? String(value).slice(0, 10) : ''; }
    function photoUrl(value) { return value ? `/${String(value).replace(/^\/+/, '')}` : ''; }
    function getRadioValue(name) { return document.querySelector(`input[name="${name}"]:checked`)?.value ?? null; }
    function validationMessage(json, fallback) {
        const errors = json?.errors ? Object.values(json.errors).flat() : [];
        return errors.length ? errors.join('\n') : (json?.message || fallback);
    }

    async function apiFetch(path, options = {}) {
        const response = await fetch(`${API_URL}${path}`, {
            cache: 'no-store',
            ...options,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                ...(options.headers || {})
            }
        });
        if (response.status === 401) {
            localStorage.clear();
            window.location.href = '/';
            throw new Error('Session expired');
        }
        return response;
    }

    async function fetchRows(path) {
        const response = await apiFetch(path);
        const json = await response.json();
        if (!response.ok) throw new Error(json.message || 'Gagal memuat master data');
        return Array.isArray(json.data) ? json.data : [];
    }

    function fillSelect(id, rows, emptyLabel, labelResolver) {
        document.getElementById(id).innerHTML = `<option value="">${emptyLabel}</option>` + rows.map(row =>
            `<option value="${row.id_location || row.id}">${escapeHtml(labelResolver(row))}</option>`
        ).join('');
    }

    function onPointChange() {
        const pointId = Number(document.getElementById('point_id').value);
        const point = points.find(row => Number(row.id) === pointId);
        document.getElementById('item_type').value = point?.ket1 || '';
        document.getElementById('item_location_detail').value = point?.ket2 || '';
    }

    function setRadio(name, value) {
        const radio = document.querySelector(`input[name="${name}"][value="${value ? 1 : 0}"]`);
        if (radio) radio.checked = true;
    }

    function setCurrentPhoto(elementId, path) {
        document.getElementById(elementId).innerHTML = path
            ? `<a href="${escapeHtml(photoUrl(path))}" target="_blank" class="text-blue-600 hover:text-blue-800"><i class="fas fa-image mr-1"></i>Lihat foto saat ini</a>`
            : 'Belum ada foto';
    }

    function lockSelect(selectId, value) {
        const select = document.getElementById(selectId);
        select.value = value || '';
        select.disabled = true;
        select.classList.add('bg-gray-100', 'text-gray-700', 'cursor-not-allowed');
        select.setAttribute('aria-readonly', 'true');
    }

    function lockInput(inputId, value) {
        const input = document.getElementById(inputId);
        input.value = value || '';
        input.readOnly = true;
        input.classList.add('bg-gray-100', 'text-gray-700', 'cursor-not-allowed');
        input.setAttribute('aria-readonly', 'true');
    }

    async function loadPage() {
        try {
            const [locations, users, pointRows] = await Promise.all([
                fetchRows('/fire-extinguisher-locations'),
                fetchRows('/users'),
                fetchRows('/points')
            ]);
            points = pointRows;
            fillSelect('location_id', locations, '- Select Location -', row => `${row.id_location} - ${row.name}`);
            fillSelect('inspector_id', users, '- Current User -', row => `${row.id} - ${row.name || row.email}`);
            fillSelect('point_id', points, '- Select APAR -', row => `${row.id} - ${row.name_point}`);

            if (!inspectionId) {
                document.getElementById('inspection_date').value = inheritedInspectionDate || new Date().toISOString().slice(0, 10);
                document.getElementById('location_id').value = inheritedLocationId || '';
                document.getElementById('inspector_id').value = inheritedInspectorId || user.id || '';
                if (isItemCreate) {
                    lockInput('inspection_date', inheritedInspectionDate || new Date().toISOString().slice(0, 10));
                    lockSelect('location_id', inheritedLocationId);
                    lockSelect('inspector_id', inheritedInspectorId || user.id);
                }
                const response = await apiFetch('/fire-extinguishers/next-reference');
                const json = await response.json();
                document.getElementById('reference_no').value = json.data?.reference_no || '';
                return;
            }

            const response = await apiFetch(`/fire-extinguishers/${inspectionId}`);
            const json = await response.json();
            if (!response.ok) throw new Error(json.message || 'Data inspection tidak ditemukan');
            const inspection = json.data;
            const item = inspection.items?.[0] || {};
            const matchingPoint = points.find(point =>
                point.name_point === item.name &&
                (point.ket1 || '') === (item.type || '') &&
                (point.ket2 || '') === (item.location_detail || '')
            );

            document.getElementById('reference_no').value = inspection.reference_no || '';
            document.getElementById('inspection_date').value = formatDate(inspection.inspection_date);
            document.getElementById('location_id').value = inspection.location_id || '';
            document.getElementById('inspector_id').value = inspection.inspector_id || '';
            document.getElementById('point_id').value = matchingPoint?.id || '';
            document.getElementById('item_type').value = item.type || '';
            document.getElementById('item_location_detail').value = item.location_detail || '';
            document.getElementById('item_remark').value = item.remark || '';
            setRadio('item_pressure_condition', item.pressure_condition);
            setRadio('item_seal_condition', item.seal_condition);
            setRadio('item_nozzle_condition', item.nozzle_condition);
            setCurrentPhoto('current_photo_before', item.photo_before);
            setCurrentPhoto('current_photo_after', item.photo_after);
        } catch (error) {
            alert(error.message || 'Gagal memuat form');
        }
    }

    function currentCoordinates() {
        if (!navigator.geolocation) return Promise.resolve(null);
        return new Promise(resolve => navigator.geolocation.getCurrentPosition(
            position => resolve({ lat: position.coords.latitude, lng: position.coords.longitude }),
            () => resolve(null),
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 60000 }
        ));
    }

    document.getElementById('point_id').addEventListener('change', onPointChange);
    document.getElementById('extinguisherForm').addEventListener('submit', async event => {
        event.preventDefault();
        const saveButton = document.getElementById('saveButton');
        const originalHtml = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

        try {
            const coordinates = await currentCoordinates();
            const formData = new FormData();
            const values = {
                reference_no: document.getElementById('reference_no').value,
                inspection_date: document.getElementById('inspection_date').value,
                location_id: document.getElementById('location_id').value,
                inspector_id: document.getElementById('inspector_id').value,
                point_id: document.getElementById('point_id').value,
                checkin_lat: coordinates?.lat,
                checkin_lng: coordinates?.lng,
                'item[pressure_condition]': getRadioValue('item_pressure_condition'),
                'item[seal_condition]': getRadioValue('item_seal_condition'),
                'item[nozzle_condition]': getRadioValue('item_nozzle_condition'),
                'item[remark]': document.getElementById('item_remark').value
            };
            Object.entries(values).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') formData.append(key, value);
            });

            const beforePhoto = document.getElementById('item_photo_before').files[0];
            const afterPhoto = document.getElementById('item_photo_after').files[0];
            if (beforePhoto) formData.append('item[photo_before]', beforePhoto);
            if (afterPhoto) formData.append('item[photo_after]', afterPhoto);
            if (inspectionId) formData.append('_method', 'PUT');

            const response = await apiFetch(
                inspectionId ? `/fire-extinguishers/${inspectionId}` : '/fire-extinguishers',
                { method: 'POST', body: formData }
            );
            const json = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(validationMessage(json, `Save failed (${response.status})`));
            window.location.href = '/dashboard/fire-extinguishers#inspection-list';
        } catch (error) {
            alert(error.message || 'Save failed');
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = originalHtml;
        }
    });

    window.logout = function () {
        fetch(`${API_URL}/auth/logout`, {
            method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        }).finally(() => { localStorage.clear(); window.location.href = '/'; });
    };

    loadPage();
})();
</script>
@endsection

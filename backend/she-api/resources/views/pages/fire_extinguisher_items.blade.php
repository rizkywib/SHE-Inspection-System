@extends('layouts.app')

@section('title', 'Fire Extinguisher Items')
@section('nav-fire-extinguishers', 'active')

@section('content')
<div class="p-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="/dashboard/fire-extinguishers#inspection-list" class="text-gray-500 hover:text-gray-800">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Fire Extinguisher Items</h1>
                <p id="locationName" class="text-gray-600 mt-1">Memuat lokasi...</p>
            </div>
        </div>
        <a id="createItemButton" href="/dashboard/fire-extinguishers/create?location_id={{ $locationId }}" class="btn-primary inline-flex items-center justify-center text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>Create New Fire Extinguisher Item
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">List Data Fire Extinguisher Item</h2>
                <p class="text-sm text-gray-500 mt-1">Seluruh item inspection pada lokasi yang dipilih.</p>
            </div>
            <span id="itemCount" class="text-sm font-semibold text-gray-600">Memuat data...</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">APAR</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detail Location</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pressure</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Seal</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nozzle</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inspector</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Photos</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="itemTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="11" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const API_URL = '/api';
    const locationId = @json($locationId);
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (!token) {
        window.location.href = '/';
        return;
    }
    document.getElementById('userName').textContent = user.name || 'User';

    function canModify(inspection) {
        if (user.role === 'admin' || user.role === 'super_admin') return true;
        return String(inspection.inspector_id) === String(user.id);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function formatDate(value) { return value ? String(value).slice(0, 10) : '-'; }
    function photoUrl(value) { return value ? `/${String(value).replace(/^\/+/, '')}` : ''; }
    function conditionChecklist(value, label) {
        return value
            ? `<span class="text-green-600 font-bold" title="${label} OK">✓</span>`
            : `<span class="text-red-500 font-bold" title="${label} Not OK">✗</span>`;
    }
    function photoLink(path, label) {
        return path
            ? `<a href="${escapeHtml(photoUrl(path))}" target="_blank" class="text-blue-600 hover:text-blue-800" title="${label}"><i class="fas fa-image"></i></a>`
            : '<span class="text-gray-300">-</span>';
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
        }
        return response;
    }

    async function loadItems() {
        const table = document.getElementById('itemTable');
        try {
            const [locationResponse, inspectionsResponse] = await Promise.all([
                apiFetch(`/fire-extinguisher-locations/${locationId}`),
                apiFetch(`/fire-extinguishers?location_id=${locationId}&_=${Date.now()}`)
            ]);
            const locationJson = await locationResponse.json();
            const inspectionsJson = await inspectionsResponse.json();
            if (!locationResponse.ok) throw new Error(locationJson.message || 'Lokasi tidak ditemukan');
            if (!inspectionsResponse.ok) throw new Error(inspectionsJson.message || 'Gagal memuat item');

            document.getElementById('locationName').textContent = locationJson.data?.name || `Location #${locationId}`;
            const inspections = Array.isArray(inspectionsJson.data) ? inspectionsJson.data : [];
            const pageParams = new URLSearchParams(window.location.search);
            const requestedInspectorId = pageParams.get('inspector_id');
            const requestedInspectionDate = pageParams.get('inspection_date');
            const inheritedInspectorId = requestedInspectorId || inspections[0]?.inspector_id || '';
            const inheritedInspectionDate = requestedInspectionDate || (inspections[0]?.inspection_date ? String(inspections[0].inspection_date).slice(0, 10) : '');
            const createUrl = new URL('/dashboard/fire-extinguishers/create', window.location.origin);
            createUrl.searchParams.set('location_id', locationId);
            if (inheritedInspectorId) createUrl.searchParams.set('inspector_id', inheritedInspectorId);
            if (inheritedInspectionDate) createUrl.searchParams.set('inspection_date', inheritedInspectionDate);
            document.getElementById('createItemButton').href = createUrl.pathname + createUrl.search;
            const rows = inspections.flatMap(inspection =>
                (inspection.items || []).map(item => ({ inspection, item }))
            );
            document.getElementById('itemCount').textContent = `${rows.length} item`;

            if (rows.length === 0) {
                table.innerHTML = '<tr><td colspan="11" class="px-6 py-8 text-center text-gray-500">Belum ada item pada lokasi ini</td></tr>';
                return;
            }

            table.innerHTML = rows.map(({ inspection, item }, index) => `<tr class="hover:bg-gray-50 align-top">
                <td class="px-4 py-4 text-sm text-center">${index + 1}</td>
                <td class="px-4 py-4 text-sm text-gray-600">${escapeHtml(formatDate(inspection.inspection_date))}</td>
                <td class="px-4 py-4 text-sm font-medium text-gray-900">${escapeHtml(item.name || '-')}</td>
                <td class="px-4 py-4 text-sm text-gray-600">${escapeHtml(item.type || '-')}</td>
                <td class="px-4 py-4 text-sm text-gray-600">${escapeHtml(item.location_detail || '-')}</td>
                <td class="px-4 py-4 text-center">${conditionChecklist(item.pressure_condition, 'Pressure')}</td>
                <td class="px-4 py-4 text-center">${conditionChecklist(item.seal_condition, 'Seal')}</td>
                <td class="px-4 py-4 text-center">${conditionChecklist(item.nozzle_condition, 'Nozzle')}</td>
                <td class="px-4 py-4 text-sm text-gray-900">${escapeHtml(inspection.inspector?.name || '-')}</td>
                <td class="px-4 py-4 text-center"><div class="flex justify-center gap-3">
                    ${photoLink(item.photo_before, 'Foto Sebelum')}
                    ${photoLink(item.photo_after, 'Foto Sesudah')}
                </div></td>
                <td class="px-4 py-4 text-sm whitespace-nowrap">
                    ${canModify(inspection) ? `<a href="/dashboard/fire-extinguishers/${inspection.id}/edit" class="text-blue-600 hover:text-blue-800 mr-3 font-medium"><i class="fas fa-edit mr-1"></i>Edit</a>
                    <button type="button" onclick="deleteInspection(${inspection.id})" class="text-red-600 hover:text-red-800 font-medium"><i class="fas fa-trash mr-1"></i>Delete</button>` : '-'}
                </td>
            </tr>`).join('');
        } catch (error) {
            document.getElementById('itemCount').textContent = 'Gagal memuat data';
            table.innerHTML = `<tr><td colspan="11" class="px-6 py-8 text-center text-red-600">${escapeHtml(error.message)}</td></tr>`;
        }
    }

    async function deleteInspection(id) {
        if (!confirm('Delete this inspection?')) return;
        const response = await apiFetch(`/fire-extinguishers/${id}`, { method: 'DELETE' });
        if (!response.ok) {
            const json = await response.json().catch(() => ({}));
            alert(json.message || 'Delete failed');
            return;
        }
        loadItems();
    }

    window.deleteInspection = deleteInspection;
    window.logout = function () {
        fetch(`${API_URL}/auth/logout`, {
            method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        }).finally(() => { localStorage.clear(); window.location.href = '/'; });
    };

    loadItems();
})();
</script>
@endsection

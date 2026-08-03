@extends('layouts.app')

@section('title', 'ES&EW List Item Detail')

@section('nav-es-ew-inspections', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="mb-6">
        <a href="/dashboard/es-ew-inspections" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Back to Inspections
        </a>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">ES&amp;EW List Item Detail</h1>
                <p id="pageSubtitle" class="text-sm text-blue-600 font-medium mt-0.5"></p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="/dashboard/es-ew-inspections/{{ $inspectionId }}/items/create" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
                    <i class="fas fa-plus mr-2"></i>New ES&amp;EW Item Detail
                </a>
            </div>
        </div>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800"></div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Water Flow ES</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Water Flow EW</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Water Condition</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Valve ES</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Valve EW</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Physical Condition ES</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Physical Condition EW</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sign Board</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Housekeeping</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Road Access</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sewer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remark</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Eye Wash</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Emergency Shower</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Inspection</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspected By</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="itemTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="21" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const token = localStorage.getItem('token');
const inspectionId = @json($inspectionId);
const headers = {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
};
const conditions = [
    'water_flow_es',
    'water_flow_ew',
    'water_condition',
    'actual_valve_es',
    'actual_valve_ew',
    'physical_condition_es',
    'physical_condition_ew',
    'sign_board_condition',
    'housekeeping_condition',
    'road_access_condition',
    'sewer_condition'
];

if (!token) window.location.href = '/';

function escapeHtml(value) {
    return String(value ?? '-').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

function formatDateOnly(value) {
    return value ? String(value).slice(0, 10) : '-';
}

function photoUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (/^(https?:|data:|blob:)/i.test(value)) return value;
    return `/${value.replace(/^\/+/, '').replace(/^public\//, '')}`;
}

function canPreviewImage(path) {
    return !/\.(heic|heif)$/i.test(String(path || '').split('?')[0]);
}

function checklistResult(value) {
    const isChecked = booleanValue(value);
    const iconClass = isChecked
        ? 'fa-check text-green-600'
        : 'fa-times text-red-500';
    const label = isChecked ? 'Checked' : 'Not checked';

    return `<i class="fas ${iconClass} text-lg font-bold" aria-label="${label}" title="${label}"></i>`;
}

function booleanValue(value) {
    return value === true || value === 1 || value === '1';
}

function photoCell(path, label) {
    if (!path) return '-';
    const url = photoUrl(path);
    const preview = canPreviewImage(url)
        ? `<img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-10 w-14 object-cover rounded border border-gray-200 mx-auto hover:opacity-80 transition">`
        : '<div class="h-10 w-14 flex items-center justify-center text-[10px] text-gray-400 bg-gray-100 rounded border border-gray-200 mx-auto">HEIC</div>';

    return `<a href="${escapeHtml(url)}" target="_blank" class="block">${preview}</a>`;
}

function showError(message) {
    const box = document.getElementById('messageBox');
    box.textContent = message;
    box.className = 'mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800';
    box.classList.remove('hidden');
}

async function fetchInspectionDetail() {
    const response = await fetch(`/api/es-ew/${inspectionId}`, { headers });

    if (response.status === 401) {
        localStorage.clear();
        window.location.href = '/';
        return null;
    }

    if (!response.ok) {
        throw new Error('Inspection tidak ditemukan.');
    }

    const data = (await response.json()).data;

    return data;
}

async function loadItemDetail() {
    let data;
    try {
        data = await fetchInspectionDetail();
    } catch (error) {
        showError(error.message || 'Inspection tidak ditemukan.');
        document.getElementById('itemTable').innerHTML =
            '<tr><td colspan="21" class="px-6 py-8 text-center text-red-500">Inspection not found</td></tr>';
        return;
    }

    if (!data) return;

    const items = Array.isArray(data.items) ? data.items : [];
    const areaName = data.area?.name || '-';
    const inspectionDate = formatDateOnly(data.inspection_date);
    const inspectorName = data.inspector?.name || '-';

    document.getElementById('pageSubtitle').textContent = `${areaName} • ${inspectionDate}`;

    if (items.length === 0) {
        document.getElementById('itemTable').innerHTML =
            '<tr><td colspan="21" class="px-6 py-8 text-center text-gray-500">No items found</td></tr>';
        return;
    }

    document.getElementById('itemTable').innerHTML = items.map((item, index) => {
        const conditionCells = conditions.map(field =>
            `<td class="px-4 py-3 text-sm text-center">${checklistResult(item[field])}</td>`
        ).join('');

        return `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 text-sm text-gray-900 text-center">${index + 1}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">${escapeHtml(item.name)}</td>
            <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(item.type)}</td>
            <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(item.location_detail)}</td>
            ${conditionCells}
            <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(item.remark)}</td>
            <td class="px-4 py-3 text-sm text-center">${photoCell(item.photo_before, 'Eye Wash')}</td>
            <td class="px-4 py-3 text-sm text-center">${photoCell(item.photo_after, 'Emergency Shower')}</td>
            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">${escapeHtml(inspectionDate)}</td>
            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">${escapeHtml(inspectorName)}</td>
            <td class="px-4 py-3 text-sm text-center whitespace-nowrap">
                <a href="/dashboard/es-ew-inspections/${inspectionId}/items/${item.id}/edit" class="text-blue-600 hover:text-blue-800 font-medium mr-2">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                <button onclick="deleteItem(${item.id})" class="text-red-600 hover:text-red-800 font-medium">
                    <i class="fas fa-trash-alt mr-1"></i>Delete
                </button>
            </td>
        </tr>
        `;
    }).join('');
}

async function deleteItem(itemId) {
    if (!confirm('Delete this ES&EW item?')) return;

    const response = await fetch(`/api/es-ew/${inspectionId}/items/${itemId}`, {
        method: 'DELETE',
        headers
    });
    const data = await response.json();

    if (!response.ok) {
        showError(data.message || 'Delete failed.');
        return;
    }

    const box = document.getElementById('messageBox');
    box.textContent = data.message;
    box.className = 'mb-5 rounded-lg border px-4 py-3 bg-green-50 border-green-300 text-green-800';
    await loadItemDetail();
}

const detailMessage = sessionStorage.getItem('esEwDetailMessage');
if (detailMessage) {
    sessionStorage.removeItem('esEwDetailMessage');
    const box = document.getElementById('messageBox');
    box.textContent = detailMessage;
    box.className = 'mb-5 rounded-lg border px-4 py-3 bg-green-50 border-green-300 text-green-800';
}

loadItemDetail().catch(() => {
    showError('Terjadi kesalahan saat memuat detail.');
    document.getElementById('itemTable').innerHTML =
        '<tr><td colspan="21" class="px-6 py-8 text-center text-red-500">Failed to load item detail</td></tr>';
});
</script>
@endsection

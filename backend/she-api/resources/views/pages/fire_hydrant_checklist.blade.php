@extends('layouts.app')

@section('title', 'Fire Hydrant Items Checklist')

@section('nav-fire-hydrants', 'active')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <a href="/dashboard/fire-hydrants" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Back to Inspections
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Fire Hydrant Items Checklist</h1>
                <p id="pageSubtitle" class="text-sm text-blue-600 font-medium mt-0.5"></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hydrant Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location Detail</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hose</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nozzle</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Coupling</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Wrench</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Valve</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Extra Coupling</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remark</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Photo Before</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Photo After</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Inspection</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="checklistTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="15" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let locations = [];
let pointHydrants = [];

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

function formatDateOnly(value) {
    if (!value) return '';
    return String(value).slice(0, 10);
}

async function fetchList(path) {
    try {
        const res = await fetch(`${API_URL}${path}`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (!res.ok) return [];
        const json = await res.json();
        return Array.isArray(json.data) ? json.data : [];
    } catch (error) {
        return [];
    }
}

function photoUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('data:') || value.startsWith('blob:')) {
        return value;
    }
    return `/${value.replace(/^\/+/, '').replace(/^public\//, '')}`;
}

function canPreviewImage(path) {
    return !/\.(heic|heif)$/i.test(String(path || '').split('?')[0]);
}

async function loadLocations() {
    locations = await fetchList('/fire-hydrant-locations');
}

function getLocationLabel(locationId) {
    const loc = locations.find(l => String(l.id_location) === String(locationId));
    return loc ? loc.name : ('Location #' + locationId);
}

async function loadChecklist() {
    const urlParams = new URLSearchParams(window.location.search);
    const locationId = urlParams.get('location_id');
    const requestedInspectionId = urlParams.get('inspection_id');

    if (!locationId) {
        document.getElementById('checklistTable').innerHTML = '<tr><td colspan="15" class="px-6 py-8 text-center text-gray-500">No location specified</td></tr>';
        return;
    }

    const locLabel = getLocationLabel(locationId);
    document.getElementById('pageSubtitle').textContent = locLabel;

    const res = await fetch(`${API_URL}/fire-hydrants?location_id=${locationId}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const inspections = Array.isArray(json.data) ? json.data : [];
    const targetInspection = requestedInspectionId
        ? inspections.find(inspection => String(inspection.id) === String(requestedInspectionId))
        : inspections[0];

    const tbody = document.getElementById('checklistTable');

    if (inspections.length === 0) {
        tbody.innerHTML = '<tr><td colspan="15" class="px-6 py-8 text-center text-gray-500">No hydrant inspections found for this location</td></tr>';
        return;
    }

    if (!targetInspection) {
        tbody.innerHTML = '<tr><td colspan="15" class="px-6 py-8 text-center text-red-500">Inspection not found for this location</td></tr>';
        return;
    }

    const allItems = [];
    if (targetInspection && Array.isArray(targetInspection.items)) {
        targetInspection.items.forEach(item => {
            allItems.push({
                ...item,
                inspection_id: targetInspection.id,
                inspection_date: targetInspection.inspection_date
            });
        });
    }

    if (allItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="15" class="px-6 py-8 text-center text-gray-500">No items found</td></tr>';
        return;
    }

    tbody.innerHTML = allItems.map((item, index) => {
        const lastInspection = item.inspection_date ? formatDateOnly(item.inspection_date) : '-';
        const photoBefore = item.photo_before ? 
            `<a href="${escapeHtml(photoUrl(item.photo_before))}" target="_blank" class="block">
                ${canPreviewImage(photoUrl(item.photo_before))
                    ? `<img src="${escapeHtml(photoUrl(item.photo_before))}" alt="Photo Before" class="h-10 w-14 object-cover rounded border border-gray-200 mx-auto hover:opacity-80 transition">`
                    : `<div class="h-10 w-14 flex items-center justify-center text-[10px] text-gray-400 bg-gray-100 rounded border border-gray-200 mx-auto">HEIC</div>`
                }
            </a>` : '-';
        const photoAfter = item.photo_after ? 
            `<a href="${escapeHtml(photoUrl(item.photo_after))}" target="_blank" class="block">
                ${canPreviewImage(photoUrl(item.photo_after))
                    ? `<img src="${escapeHtml(photoUrl(item.photo_after))}" alt="Photo After" class="h-10 w-14 object-cover rounded border border-gray-200 mx-auto hover:opacity-80 transition">`
                    : `<div class="h-10 w-14 flex items-center justify-center text-[10px] text-gray-400 bg-gray-100 rounded border border-gray-200 mx-auto">HEIC</div>`
                }
            </a>` : '-';
        
        return `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-sm text-gray-900 text-center">${index + 1}</td>
                <td class="px-4 py-3 text-sm text-gray-900 font-medium">${escapeHtml(item.hydrant_number || '-')}</td>
                <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(item.name || '-')}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(item.location_detail || '-')}</td>
                <td class="px-4 py-3 text-sm text-center">${item.hose_condition ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.nozzle_condition ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.coupling_condition ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.wrench_condition ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.valve_condition ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.coupling_extra_condition ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(item.remark || '-')}</td>
                <td class="px-4 py-3 text-sm text-center">${photoBefore}</td>
                <td class="px-4 py-3 text-sm text-center">${photoAfter}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${lastInspection}</td>
                <td class="px-4 py-3 text-sm text-center">
                    <a href="/dashboard/fire-hydrants/edit?id=${item.inspection_id}&item=${index}&location_id=${encodeURIComponent(locationId)}&inspection_id=${item.inspection_id}" class="text-blue-600 hover:text-blue-800 font-medium mr-2">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <button onclick="confirmDeleteItem(${item.inspection_id}, ${index})" class="text-red-600 hover:text-red-800 font-medium">
                        <i class="fas fa-trash-alt mr-1"></i>Delete
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

async function confirmDeleteItem(inspectionId, itemIndex) {
    if (!confirm('Are you sure you want to delete this item?')) return;

    try {
        const res = await fetch(`${API_URL}/fire-hydrants/${inspectionId}/items/${itemIndex}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });

        if (!res.ok) {
            const error = await res.json().catch(() => null);
            alert(error?.message || 'Failed to delete item');
            return;
        }

        alert('Item deleted successfully');
        await loadChecklist();
    } catch (error) {
        alert('Delete failed: ' + error.message);
    }
}

(async function init() {
    await loadLocations();
    await loadChecklist();
})();
</script>
@endsection

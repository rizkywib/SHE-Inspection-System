@extends('layouts.app')

@section('title', ($mode === 'create' ? 'New' : 'Edit') . ' ES&EW Item Detail')

@section('nav-es-ew-inspections', 'active')

@section('content')
@php
    $conditions = [
        ['field' => 'water_flow_es', 'label' => 'Water Flow ES'],
        ['field' => 'water_flow_ew', 'label' => 'Water Flow EW'],
        ['field' => 'water_condition', 'label' => 'Water Condition'],
        ['field' => 'actual_valve_es', 'label' => 'Actual Valve ES'],
        ['field' => 'actual_valve_ew', 'label' => 'Actual Valve EW'],
        ['field' => 'physical_condition_es', 'label' => 'Physical Condition ES'],
        ['field' => 'physical_condition_ew', 'label' => 'Physical Condition EW'],
        ['field' => 'sign_board_condition', 'label' => 'Sign Board'],
        ['field' => 'housekeeping_condition', 'label' => 'Housekeeping'],
        ['field' => 'road_access_condition', 'label' => 'Road Access'],
        ['field' => 'sewer_condition', 'label' => 'Sewer Condition'],
    ];
@endphp
<div class="p-4 md:p-8">
    <div class="mb-6">
        <a href="/dashboard/es-ew-inspections/{{ $inspectionId }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Back to Item Detail
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $mode === 'create' ? 'New' : 'Edit' }} ES&amp;EW Item Detail</h1>
        <p class="text-gray-600 mt-1">Item disimpan pada inspection header yang sama.</p>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3"></div>

    <form id="itemForm" class="space-y-6" enctype="multipart/form-data">
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide border-b pb-3 mb-4">Inspection Header</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                    <input id="header_area" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label>
                    <input id="inspection_date" type="date" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inspected By</label>
                    <input id="header_inspector" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed">
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide border-b pb-3 mb-4">ES&amp;EW Item Detail</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-600">*</span></label>
                    <select id="point_id" required class="w-full border rounded-lg px-3 py-2">
                        <option value="">Select Name</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input id="item_location" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                    <input id="item_section" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                </div>
            </div>

            <div id="conditionsContainer" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mt-4"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Eye Wash</label>
                    <input id="photo_before" type="file" accept=".jpg,.jpeg,.png,.webp" class="w-full border rounded-lg px-3 py-2 bg-white">
                    <div id="preview_before" class="mt-2 text-xs text-gray-500">No photo</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Shower</label>
                    <input id="photo_after" type="file" accept=".jpg,.jpeg,.png,.webp" class="w-full border rounded-lg px-3 py-2 bg-white">
                    <div id="preview_after" class="mt-2 text-xs text-gray-500">No photo</div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remark</label>
                    <textarea id="remark" rows="2" maxlength="5000" class="w-full border rounded-lg px-3 py-2"></textarea>
                </div>
            </div>
        </section>

        <div class="flex gap-3">
            <button id="saveButton" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>{{ $mode === 'create' ? 'Add Item' : 'Save Changes' }}
            </button>
            <a href="/dashboard/es-ew-inspections/{{ $inspectionId }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
const token = localStorage.getItem('token');
const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
const inspectionId = @json($inspectionId);
const itemId = @json($itemId);
const mode = @json($mode);
const conditionFields = @json($conditions);
const headers = {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
};
let points = [];

if (!token) window.location.href = '/';

const canModifyHeader = record => currentUser.role === 'admin' || currentUser.role === 'super_admin' || String(record.inspector_id) === String(currentUser.id);

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

function photoUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (/^(https?:|data:|blob:)/i.test(value)) return value;
    return `/${value.replace(/^\/+/, '').replace(/^public\//, '')}`;
}

function showMessage(text, type = 'error') {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = `mb-5 rounded-lg border px-4 py-3 ${type === 'success'
        ? 'bg-green-50 border-green-300 text-green-800'
        : 'bg-red-50 border-red-300 text-red-800'}`;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderConditions(item = {}) {
    document.getElementById('conditionsContainer').innerHTML = conditionFields.map(condition => {
        const value = item[condition.field];
        const checked = value === undefined || value === null || value === true || value === 1 || value === '1';

        return `<div class="border rounded-lg p-3">
            <span class="block text-sm font-medium text-gray-700 mb-2">${escapeHtml(condition.label)}</span>
            <div class="flex gap-5">
                <label class="text-sm flex gap-2 items-center">
                    <input required type="radio" name="${condition.field}" data-condition="${condition.field}" value="1" ${checked ? 'checked' : ''}> YES
                </label>
                <label class="text-sm flex gap-2 items-center">
                    <input required type="radio" name="${condition.field}" data-condition="${condition.field}" value="0" ${checked ? '' : 'checked'}> NO
                </label>
            </div>
        </div>`;
    }).join('');
}

function updatePointDetails() {
    const point = points.find(row => String(row.id) === document.getElementById('point_id').value);
    document.getElementById('item_location').value = point?.ket1 || '';
    document.getElementById('item_section').value = point?.ket2 || '';
}

function setPhoto(field, path) {
    const preview = document.getElementById(`preview_${field.replace('photo_', '')}`);
    preview.innerHTML = path
        ? `<a href="${escapeHtml(photoUrl(path))}" target="_blank" class="text-blue-600 hover:text-blue-800">View current photo</a>`
        : 'No photo';
}

function bindPhotoPreview(field) {
    document.getElementById(field).addEventListener('change', event => {
        const file = event.target.files[0];
        if (!file) return;
        document.getElementById(`preview_${field.replace('photo_', '')}`).innerHTML =
            `<img src="${URL.createObjectURL(file)}" class="h-24 rounded border object-cover" alt="Preview">`;
    });
}

async function init() {
    const [masterResponse, inspectionResponse] = await Promise.all([
        fetch('/api/es-ew/master-data', { headers }),
        fetch(`/api/es-ew/${inspectionId}`, { headers })
    ]);

    if (masterResponse.status === 401 || inspectionResponse.status === 401) {
        localStorage.clear();
        window.location.href = '/';
        return;
    }

    if (!masterResponse.ok) {
        showMessage('Master data gagal dimuat.');
        return;
    }

    if (!inspectionResponse.ok) {
        showMessage('Inspection header tidak ditemukan.');
        return;
    }

    const master = await masterResponse.json();
    const inspection = (await inspectionResponse.json()).data;
    points = Array.isArray(master.points) ? master.points : [];

    if (!canModifyHeader(inspection)) {
        showMessage('Anda tidak memiliki izin untuk mengubah data ini.');
        document.getElementById('itemForm').classList.add('hidden');
        return;
    }

    document.getElementById('point_id').insertAdjacentHTML('beforeend', points.map(point =>
        `<option value="${point.id}">${escapeHtml(point.name_point)}</option>`
    ).join(''));
    document.getElementById('header_area').value = inspection.area?.name || '-';
    document.getElementById('inspection_date').value = String(inspection.inspection_date || '').slice(0, 10);
    document.getElementById('header_inspector').value = inspection.inspector?.name || '-';

    let item = {};
    if (mode === 'edit') {
        item = (inspection.items || []).find(row => String(row.id) === String(itemId));
        if (!item) {
            showMessage('Item ES&EW tidak ditemukan.');
            document.getElementById('saveButton').disabled = true;
            return;
        }

        const selectedPoint = points.find(point =>
            point.name_point === item.name
            && String(point.ket1 ?? '') === String(item.type ?? '')
            && String(point.ket2 ?? '') === String(item.location_detail ?? '')
        ) || points.find(point => point.name_point === item.name);

        document.getElementById('point_id').value = selectedPoint?.id || '';
        document.getElementById('remark').value = item.remark || '';
        setPhoto('photo_before', item.photo_before);
        setPhoto('photo_after', item.photo_after);
    }

    updatePointDetails();
    renderConditions(item);
}

document.getElementById('point_id').addEventListener('change', updatePointDetails);
bindPhotoPreview('photo_before');
bindPhotoPreview('photo_after');

document.getElementById('itemForm').addEventListener('submit', async event => {
    event.preventDefault();
    const button = document.getElementById('saveButton');
    button.disabled = true;
    button.classList.add('opacity-60');

    const form = new FormData();
    form.append('point_id', document.getElementById('point_id').value);
    form.append('remark', document.getElementById('remark').value);
    conditionFields.forEach(condition => {
        form.append(condition.field, document.querySelector(`[data-condition="${condition.field}"]:checked`).value);
    });
    ['photo_before', 'photo_after'].forEach(field => {
        const file = document.getElementById(field).files[0];
        if (file) form.append(field, file);
    });

    if (mode === 'edit') form.append('_method', 'PUT');
    const endpoint = mode === 'create'
        ? `/api/es-ew/${inspectionId}/items`
        : `/api/es-ew/${inspectionId}/items/${itemId}`;
    const response = await fetch(endpoint, {
        method: 'POST',
        headers,
        body: form
    });
    const data = await response.json();

    button.disabled = false;
    button.classList.remove('opacity-60');

    if (!response.ok) {
        const errors = Object.values(data.errors || {}).flat();
        showMessage(errors[0] || data.message || 'Save failed.');
        return;
    }

    sessionStorage.setItem('esEwDetailMessage', data.message);
    window.location.href = `/dashboard/es-ew-inspections/${inspectionId}`;
});

init().catch(() => showMessage('Terjadi kesalahan saat memuat form item.'));
</script>
@endsection

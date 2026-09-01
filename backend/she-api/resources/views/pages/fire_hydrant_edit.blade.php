@extends('layouts.app')

@section('title', 'Edit Fire Hydrant Item')

@section('nav-fire-hydrants', 'active')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <a id="backLink" href="/dashboard/fire-hydrants" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Inspections
                </a>
                <h1 id="pageTitle" class="text-3xl font-bold text-gray-900">Edit Fire Hydrant Item</h1>
                <p id="pageReference" class="text-sm text-blue-600 font-medium mt-0.5"></p>
            </div>
        </div>
    </div>

    <div id="loadingState" class="bg-white rounded-xl shadow-lg p-12 text-center">
        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
        <p class="text-gray-500">Loading item data...</p>
    </div>

    <div id="errorState" class="hidden bg-white rounded-xl shadow-lg p-12 text-center">
        <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
        <p id="errorMessage" class="text-gray-700 mb-4"></p>
        <button onclick="location.reload()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-refresh mr-2"></i>Retry
        </button>
    </div>

    <div id="editForm" class="hidden">
        <input type="hidden" id="hid_id">
        <input type="hidden" id="itemIndex">

        <!-- Inspection Header Info -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500 font-medium">Inspection Date</span>
                    <span id="infoDate" class="text-gray-900 font-semibold"></span>
                </div>
                <div>
                    <span class="block text-gray-500 font-medium">Location</span>
                    <span id="infoLocation" class="text-gray-900 font-semibold"></span>
                </div>
                <div>
                    <span class="block text-gray-500 font-medium">Inspector</span>
                    <span id="infoInspector" class="text-gray-900 font-semibold"></span>
                </div>
                <div>
                    <span class="block text-gray-500 font-medium">Status</span>
                    <span id="infoStatus" class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"></span>
                </div>
            </div>
        </div>

        <form id="itemForm" class="space-y-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 id="itemFormTitle" class="text-xl font-bold text-gray-900">Item Details</h2>
                        <p id="itemLabel" class="text-sm text-blue-600 font-medium mt-0.5"></p>
                    </div>
                </div>

                <div id="itemEditor" class="space-y-4">
                    <!-- Dynamically populated -->
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition font-medium">
                    <i class="fas fa-save mr-2"></i><span id="saveButtonLabel">Save Changes</span>
                </button>
                <a id="cancelLink" href="/dashboard/fire-hydrants" class="bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transition font-medium">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let locations = [];
let users = [];
let pointHydrants = [];
let inspectionData = null;
let itemIndex = -1;

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

const urlParams = new URLSearchParams(window.location.search);
const inspectionId = urlParams.get('id');
const itemParam = urlParams.get('item');
const returnLocationId = urlParams.get('location_id');
const returnInspectionId = urlParams.get('inspection_id') || inspectionId;

if (returnLocationId) {
    const checklistUrl = `/dashboard/fire-hydrant-checklist?location_id=${encodeURIComponent(returnLocationId)}&inspection_id=${encodeURIComponent(returnInspectionId)}`;
    document.getElementById('backLink').href = checklistUrl;
    document.getElementById('backLink').innerHTML = '<i class="fas fa-arrow-left mr-1"></i>Back to Checklist';
    document.getElementById('cancelLink').href = checklistUrl;
}

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

function boolValue(value) {
    return value === true || value === 1 || value === '1';
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

async function loadReferenceData() {
    const [locationData, userData, pointData] = await Promise.all([
        fetchList('/fire-hydrant-locations'),
        fetchList('/users'),
        fetchList('/points')
    ]);
    locations = locationData;
    users = userData;
    pointHydrants = pointData;
}

function hydrantPointOptions(selectedValue = '') {
    const selected = String(selectedValue ?? '');
    const hasSelected = pointHydrants.some(point => String(point.name_point ?? point.id) === selected);
    const fallbackOption = selected && !hasSelected
        ? `<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`
        : '';
    return `<option value="">- Select Hydrant Number -</option>${fallbackOption}` + pointHydrants.map(point => {
        const value = String(point.name_point ?? point.id);
        const selectedAttr = value === selected ? 'selected' : '';
        const label = `${point.id} - ${point.name_point || 'Point'}`;
        return `<option value="${escapeHtml(value)}" data-name="${escapeHtml(point.ket1 || '')}" data-location="${escapeHtml(point.ket2 || '')}" ${selectedAttr}>${escapeHtml(label)}</option>`;
    }).join('');
}

function applyHydrantPoint(select) {
    const container = select.closest('#itemEditor');
    if (!container) return;
    const option = select.options[select.selectedIndex];
    const nameInput = container.querySelector('[data-field="name"]');
    const locationInput = container.querySelector('[data-field="location_detail"]');
    if (option?.dataset.name) nameInput.value = option.dataset.name;
    if (option?.dataset.location) locationInput.value = option.dataset.location;
}

function conditionSelect(field, label, value) {
    const val = boolValue(value);
    return `
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <span class="font-medium text-gray-600 min-w-[100px]">${label}</span>
            <label class="inline-flex items-center cursor-pointer">
                <input data-field="${field}" type="radio" name="${field}" value="1" ${val ? 'checked' : ''} class="text-green-600 focus:ring-green-500">
                <span class="ml-1.5 text-green-700">Yes</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input data-field="${field}" type="radio" name="${field}" value="0" ${!val ? 'checked' : ''} class="text-red-600 focus:ring-red-500">
                <span class="ml-1.5 text-red-700">No</span>
            </label>
        </div>
    `;
}

function buildItemEditor(item) {
    return `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hydrant Number</label>
                <div class="relative" data-hydrant-combobox>
                    <button type="button" data-hydrant-toggle class="w-full text-left border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center justify-between">
                        <span data-hydrant-label class="text-gray-400 truncate">- Select Hydrant Number -</span>
                        <i class="fas fa-angle-down text-gray-400 ml-2"></i>
                    </button>
                    <div data-hydrant-panel class="absolute z-20 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden">
                        <div class="p-2 border-b border-gray-200 bg-white">
                            <input type="text" data-hydrant-search placeholder="Search hydrant number..." class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <ul data-hydrant-list class="max-h-56 overflow-y-auto"></ul>
                    </div>
                </div>
                <select data-field="hydrant_number" required tabindex="-1" aria-hidden="true" class="sr-only">
                    ${hydrantPointOptions(item.hydrant_number || '')}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input data-field="name" required value="${escapeHtml(item.name || '')}" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location Detail</label>
                <input data-field="location_detail" value="${escapeHtml(item.location_detail || '')}" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    ${conditionSelect('hose_condition', 'Hose', item.hose_condition)}
                    ${conditionSelect('nozzle_condition', 'Nozzle', item.nozzle_condition)}
                    ${conditionSelect('coupling_condition', 'Coupling', item.coupling_condition)}
                    ${conditionSelect('wrench_condition', 'Wrench', item.wrench_condition)}
                    ${conditionSelect('valve_condition', 'Valve', item.valve_condition)}
                    ${conditionSelect('coupling_extra_condition', 'Extra Coupling', item.coupling_extra_condition)}
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo Before</label>
                <input data-field="photo_before" type="file" accept="image/*,.heic,.heif" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                ${item.photo_before ? `<div class="mt-2"><a href="${escapeHtml(photoUrl(item.photo_before))}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-image mr-1"></i>View current photo</a></div>` : '<div class="mt-2 text-xs text-gray-400">No current photo</div>'}
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo After</label>
                <input data-field="photo_after" type="file" accept="image/*,.heic,.heif" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                ${item.photo_after ? `<div class="mt-2"><a href="${escapeHtml(photoUrl(item.photo_after))}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-image mr-1"></i>View current photo</a></div>` : '<div class="mt-2 text-xs text-gray-400">No current photo</div>'}
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Remark</label>
                <textarea data-field="remark" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">${escapeHtml(item.remark || '')}</textarea>
            </div>
        </div>
    `;
}

function getItemFromForm() {
    const container = document.getElementById('itemEditor');
    return {
        hydrant_number: container.querySelector('[data-field="hydrant_number"]').value,
        name: container.querySelector('[data-field="name"]').value,
        location_detail: container.querySelector('[data-field="location_detail"]').value || null,
        hose_condition: container.querySelector('[data-field="hose_condition"]:checked')?.value === '1',
        nozzle_condition: container.querySelector('[data-field="nozzle_condition"]:checked')?.value === '1',
        coupling_condition: container.querySelector('[data-field="coupling_condition"]:checked')?.value === '1',
        wrench_condition: container.querySelector('[data-field="wrench_condition"]:checked')?.value === '1',
        valve_condition: container.querySelector('[data-field="valve_condition"]:checked')?.value === '1',
        coupling_extra_condition: container.querySelector('[data-field="coupling_extra_condition"]:checked')?.value === '1',
        remark: container.querySelector('[data-field="remark"]').value || null,
        photo_before: container.querySelector('[data-field="photo_before"]').files[0] || null,
        photo_after: container.querySelector('[data-field="photo_after"]').files[0] || null,
    };
}

function hydrantComboboxRef(container) {
    return {
        select: container.querySelector('[data-field="hydrant_number"]'),
        label: container.querySelector('[data-hydrant-label]'),
        panel: container.querySelector('[data-hydrant-panel]'),
        search: container.querySelector('[data-hydrant-search]'),
        list: container.querySelector('[data-hydrant-list]'),
    };
}

function hydrantComboboxSync(container) {
    const { select, label } = hydrantComboboxRef(container);
    const option = select.options[select.selectedIndex];
    if (option && option.value) {
        label.textContent = option.textContent || option.value;
        label.classList.remove('text-gray-400');
    } else {
        label.textContent = '- Select Hydrant Number -';
        label.classList.add('text-gray-400');
    }
}

function hydrantComboboxClose(container) {
    hydrantComboboxRef(container).panel.classList.add('hidden');
}

function hydrantComboboxRender(container) {
    const { select, panel, search, list } = hydrantComboboxRef(container);
    const query = search.value.toLowerCase().trim();
    const matches = pointHydrants.filter(point => {
        if (!query) return true;
        return [point.id, point.name_point, point.ket1, point.ket2]
            .filter(value => value != null)
            .join(' ')
            .toLowerCase()
            .includes(query);
    });

    if (matches.length === 0) {
        list.innerHTML = '<li class="px-3 py-4 text-sm text-gray-400 italic">No hydrant found</li>';
    } else {
        list.innerHTML = matches.map(point => {
            const value = String(point.name_point ?? point.id);
            const isSelected = Array.from(select.options)
                .some(option => option.value === value && option.selected);
            return `
                <li data-value="${escapeHtml(value)}" class="px-3 py-2 cursor-pointer text-sm hover:bg-blue-50 ${isSelected ? 'bg-blue-50' : ''}">
                    <div class="font-medium text-gray-900">${escapeHtml(`${point.id} - ${point.name_point || 'Point'}`)}</div>
                    ${point.ket2 ? `<div class="text-xs text-gray-500 truncate">${escapeHtml(point.ket2)}</div>` : ''}
                </li>
            `;
        }).join('');
    }

    list.querySelectorAll('li[data-value]').forEach(item => {
        item.addEventListener('click', () => {
            const option = Array.from(select.options)
                .find(option => option.value === item.dataset.value);
            if (!option) return;
            select.value = option.value;
            applyHydrantPoint(select);
            hydrantComboboxSync(container);
            hydrantComboboxClose(container);
        });
    });

    panel.classList.remove('hidden');
}

function hydrantComboboxInit(container) {
    const { panel, search } = hydrantComboboxRef(container);

    container.querySelector('[data-hydrant-toggle]').addEventListener('click', () => {
        if (panel.classList.contains('hidden')) {
            search.value = '';
            hydrantComboboxRender(container);
            setTimeout(() => search.focus(), 0);
        } else {
            hydrantComboboxClose(container);
        }
    });

    search.addEventListener('input', () => hydrantComboboxRender(container));
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault();
        if (event.key === 'Escape') hydrantComboboxClose(container);
    });
    search.addEventListener('click', (event) => event.stopPropagation());

    hydrantComboboxSync(container);
}

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-hydrant-panel]:not(.hidden)').forEach(panel => {
        const combobox = panel.closest('[data-hydrant-combobox]');
        if (combobox && !combobox.contains(event.target)) panel.classList.add('hidden');
    });
});

async function loadInspection() {
    if (!inspectionId || itemParam === null) {
        document.getElementById('errorMessage').textContent = 'Invalid URL parameters.';
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        return;
    }

    itemIndex = parseInt(itemParam, 10);

    try {
        await loadReferenceData();

        const res = await fetch(`${API_URL}/fire-hydrants/${inspectionId}`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Failed to load inspection');

        const json = await res.json();
        inspectionData = json.data;

        const items = Array.isArray(inspectionData.items) ? inspectionData.items : [];
        if (itemIndex < 0 || itemIndex >= items.length) {
            throw new Error(`Item index ${itemIndex} is out of range (0-${items.length - 1})`);
        }

        const item = items[itemIndex];

        // Set hidden fields
        document.getElementById('hid_id').value = inspectionData.id;
        document.getElementById('itemIndex').value = itemIndex;

        // Set reference display
        document.getElementById('pageTitle').textContent = 'Edit Fire Hydrant Item';
        document.getElementById('itemFormTitle').textContent = 'Item Details';
        document.getElementById('saveButtonLabel').textContent = 'Save Changes';
        document.getElementById('pageReference').textContent = `${inspectionData.reference_no || 'No Reference'} - Item ${itemIndex + 1}`;
        document.getElementById('itemLabel').textContent = `Editing item ${itemIndex + 1} of ${items.length}`;

        // Set inspection info
        const locName = inspectionData.location 
            ? (inspectionData.location.id_location + ' - ' + inspectionData.location.name) 
            : (inspectionData.location_id || '-');
        const inspName = inspectionData.inspector 
            ? inspectionData.inspector.name 
            : (inspectionData.inspector_id || '-');

        document.getElementById('infoDate').textContent = formatDateOnly(inspectionData.inspection_date) || '-';
        document.getElementById('infoLocation').textContent = locName;
        document.getElementById('infoInspector').textContent = inspName;

        const statusSpan = document.getElementById('infoStatus');
        statusSpan.textContent = inspectionData.status || '-';
        statusSpan.className = 'px-3 py-1 inline-flex text-xs font-semibold rounded-full ' +
            (inspectionData.status === 'completed' ? 'bg-green-100 text-green-800' :
             inspectionData.status === 'signed' ? 'bg-blue-100 text-blue-800' :
             'bg-yellow-100 text-yellow-800');

        // Build the single item editor
        document.getElementById('itemEditor').innerHTML = buildItemEditor(item);
        hydrantComboboxInit(document.getElementById('itemEditor'));

        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('editForm').classList.remove('hidden');
    } catch (error) {
        document.getElementById('errorMessage').textContent = error.message || 'Failed to load item data.';
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
    }
}

document.getElementById('itemForm').addEventListener('submit', async e => {
    e.preventDefault();

    const id = document.getElementById('hid_id').value;
    const idx = parseInt(document.getElementById('itemIndex').value, 10);
    const editedItem = getItemFromForm();
    const originalItems = Array.isArray(inspectionData.items) ? inspectionData.items : [];

    // Reconstruct full items array: keep all items, replace the edited one
    const allItems = originalItems.map((originalItem, i) => {
        if (i !== idx) {
            // Keep other item data; existing photo paths are restored by the backend.
            return {
                hydrant_number: originalItem.hydrant_number || '',
                name: originalItem.name || '',
                location_detail: originalItem.location_detail || null,
                hose_condition: boolValue(originalItem.hose_condition),
                nozzle_condition: boolValue(originalItem.nozzle_condition),
                coupling_condition: boolValue(originalItem.coupling_condition),
                wrench_condition: boolValue(originalItem.wrench_condition),
                valve_condition: boolValue(originalItem.valve_condition),
                coupling_extra_condition: boolValue(originalItem.coupling_extra_condition),
                remark: originalItem.remark || null,
            };
        }
        // Send edited item; photos will be handled via FormData file uploads
        return {
            hydrant_number: editedItem.hydrant_number,
            name: editedItem.name,
            location_detail: editedItem.location_detail,
            hose_condition: editedItem.hose_condition,
            nozzle_condition: editedItem.nozzle_condition,
            coupling_condition: editedItem.coupling_condition,
            wrench_condition: editedItem.wrench_condition,
            valve_condition: editedItem.valve_condition,
            coupling_extra_condition: editedItem.coupling_extra_condition,
            remark: editedItem.remark,
            // For the edited item, only include photo file if new one uploaded
            // Existing photos will be handled by carryExistingItemPhotos in backend
        };
    });

    // But when uploading, put the file reference for the edited item index
    const formData = new FormData();
    formData.append('inspection_date', inspectionData.inspection_date);
    formData.append('location_id', inspectionData.location_id || '');
    formData.append('inspector_id', inspectionData.inspector_id || '');
    formData.append('notes', inspectionData.notes || '');
    formData.append('status', inspectionData.status || 'new');

    allItems.forEach((item, index) => {
        Object.entries(item).forEach(([key, value]) => {
            if (value === null || value === undefined) return;
            if (value instanceof File) {
                formData.append(`items[${index}][${key}]`, value);
                return;
            }
            formData.append(`items[${index}][${key}]`, typeof value === 'boolean' ? (value ? '1' : '0') : value);
        });
        // For the edited item, add the actual file if selected
        if (index === idx) {
            const photoBeforeFile = document.querySelector('[data-field="photo_before"]').files[0];
            const photoAfterFile = document.querySelector('[data-field="photo_after"]').files[0];
            if (photoBeforeFile) formData.append(`items[${index}][photo_before]`, photoBeforeFile);
            if (photoAfterFile) formData.append(`items[${index}][photo_after]`, photoAfterFile);
        }
    });

    formData.append('_method', 'PUT');

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    try {
        const res = await fetch(`${API_URL}/fire-hydrants/${id}`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
            body: formData
        });

        if (!res.ok) {
            const error = await res.json().catch(() => null);
            const message = error?.message || Object.values(error?.errors || {}).flat()[0] || 'Save failed';
            alert(message);
            return;
        }

        alert('Item updated successfully!');
        window.location.href = returnLocationId
            ? `/dashboard/fire-hydrant-checklist?location_id=${encodeURIComponent(returnLocationId)}&inspection_id=${encodeURIComponent(returnInspectionId)}`
            : '/dashboard/fire-hydrants';
    } catch (error) {
        alert('Save failed: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

loadInspection();
</script>
@endsection

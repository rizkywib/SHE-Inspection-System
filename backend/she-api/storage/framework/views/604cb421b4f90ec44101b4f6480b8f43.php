<?php $__env->startSection('title', 'Fire Hydrant Inspections'); ?>

<?php $__env->startSection('content'); ?>
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Fire Hydrant Inspections</h1>
        <p class="text-gray-600 mt-1">Manage and track fire hydrant inspections</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Inspection
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Inspection</h2>
        <form id="hydrantForm" class="space-y-6">
            <input type="hidden" id="hid_id">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Inspection Data</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference No</label>
                            <input id="reference_no" readonly placeholder="Auto generated" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label>
                            <input id="inspection_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location ID</label>
                            <select id="location_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">- Select Location -</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="draft">Draft</option>
                                <option value="completed">Completed</option>
                                <option value="signed">Signed</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Assignment</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inspector ID</label>
                            <select id="inspector_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">- Current User -</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Notes</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea id="notes" rows="11" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Fire Hydrant Items</h3>
                        <p class="text-sm text-gray-500 mt-1">Isi detail item hydrant dan kondisi perlengkapannya.</p>
                    </div>
                    <button type="button" onclick="addHydrantItem()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Item
                    </button>
                </div>
                <div id="hydrantItems" class="space-y-4"></div>
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
                <button type="button" onclick="closeForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="hydrantTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto" onclick="if(event.target===this)closeView()">
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform transition-all" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-8 py-5 border-b border-gray-200">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Inspection Detail</h2>
                    <p id="viewReference" class="text-sm text-blue-600 font-medium mt-0.5"></p>
                </div>
                <button onclick="closeView()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="px-8 py-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="block text-gray-500 font-medium">Inspection Date</span>
                        <span id="viewDate" class="text-gray-900 font-semibold"></span>
                    </div>
                    <div>
                        <span class="block text-gray-500 font-medium">Location</span>
                        <span id="viewLocation" class="text-gray-900 font-semibold"></span>
                    </div>
                    <div>
                        <span class="block text-gray-500 font-medium">Status</span>
                        <span id="viewStatus" class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"></span>
                    </div>
                    <div>
                        <span class="block text-gray-500 font-medium">Inspector</span>
                        <span id="viewInspector" class="text-gray-900 font-semibold"></span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="block text-gray-500 font-medium">Notes</span>
                        <span id="viewNotes" class="text-gray-900"></span>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-5">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Fire Hydrant Items</h3>
                    <div id="viewItems" class="space-y-3"></div>
                </div>
            </div>
            <div class="px-8 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeView()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let locations = [];
let users = [];
let hydrants = [];
let pointHydrants = [];
let referenceDataPromise;

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

function optionLabel(item, fallbackPrefix) {
    const id = item.id ?? item.id_location;
    return `${id} - ${item.name || item.name_point || item.username || item.asset_name || fallbackPrefix}`;
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

function fillSelect(id, data, emptyLabel, fallbackPrefix, valueKey = 'id') {
    const select = document.getElementById(id);
    select.innerHTML = `<option value="">${emptyLabel}</option>` + data.map(item =>
        `<option value="${item[valueKey]}">${escapeHtml(optionLabel(item, fallbackPrefix))}</option>`
    ).join('');
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
    fillSelect('location_id', locations, '- Select Location -', 'Location', 'id_location');
    fillSelect('inspector_id', users, '- Current User -', 'User');
}

async function ensureReferenceData() {
    if (!referenceDataPromise) {
        referenceDataPromise = loadReferenceData();
    }

    await referenceDataPromise;
}

async function fillNextReferenceNo() {
    const input = document.getElementById('reference_no');
    input.value = 'Generating...';

    try {
        const res = await fetch(`${API_URL}/fire-hydrants/next-reference`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        const json = await res.json();
        input.value = json.data?.reference_no || 'Auto generated';
    } catch (error) {
        input.value = 'Auto generated';
    }
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

function applyHydrantPoint(select, overwrite = true) {
    const card = select.closest('.hydrant-item');
    const option = select.options[select.selectedIndex];
    const nameInput = card.querySelector('[data-field="name"]');
    const locationInput = card.querySelector('[data-field="location_detail"]');
    const nameValue = option?.dataset.name || '';
    const locationValue = option?.dataset.location || '';

    if (overwrite || !nameInput.value) {
        nameInput.value = nameValue;
    }

    if (overwrite || !locationInput.value) {
        locationInput.value = locationValue;
    }
}

function boolValue(value) {
    return value === true || value === 1 || value === '1';
}

function conditionRadio(field, label, item, index) {
    const val = boolValue(item[field]);
    const yesChecked = val ? 'checked' : '';
    const noChecked = !val ? 'checked' : '';
    return `
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700">
            <span class="font-medium text-gray-600 min-w-[100px]">${label}</span>
            <div class="flex items-center gap-4">
                <label class="inline-flex items-center cursor-pointer">
                    <input data-field="${field}" type="radio" name="${field}_${index}" value="1" ${yesChecked} class="text-blue-600 focus:ring-blue-500">
                    <span class="ml-1.5">Yes</span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input data-field="${field}" type="radio" name="${field}_${index}" value="0" ${noChecked} class="text-blue-600 focus:ring-blue-500">
                    <span class="ml-1.5">No</span>
                </label>
            </div>
        </div>
    `;
}

function addHydrantItem(item = {}) {
    const container = document.getElementById('hydrantItems');
    const index = container.children.length + 1;
    const card = document.createElement('div');
    card.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 hydrant-item';
    card.innerHTML = `
        <div class="flex items-center justify-between gap-3 mb-4">
            <h4 class="font-semibold text-gray-900">Item Hydrant <span class="item-number">${index}</span></h4>
            <button type="button" onclick="removeHydrantItem(this)" class="text-red-600 hover:text-red-800 text-sm font-medium">
                <i class="fas fa-trash mr-1"></i>Remove
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hydrant Number</label>
                <select data-field="hydrant_number" required onchange="applyHydrantPoint(this)" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    ${hydrantPointOptions(item.hydrant_number || '')}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input data-field="name" required readonly value="${escapeHtml(item.name || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location Detail</label>
                <input data-field="location_detail" readonly value="${escapeHtml(item.location_detail || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    ${conditionRadio('hose_condition', 'Hose', item, index)}
                    ${conditionRadio('nozzle_condition', 'Nozzle', item, index)}
                    ${conditionRadio('coupling_condition', 'Coupling', item, index)}
                    ${conditionRadio('wrench_condition', 'Wrench', item, index)}
                    ${conditionRadio('valve_condition', 'Valve', item, index)}
                    ${conditionRadio('coupling_extra_condition', 'Extra Coupling', item, index)}
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo Before</label>
                <input data-field="photo_before" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo After</label>
                <input data-field="photo_after" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Remark</label>
                <textarea data-field="remark" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">${escapeHtml(item.remark || '')}</textarea>
            </div>
        </div>
    `;
    container.appendChild(card);
    applyHydrantPoint(card.querySelector('[data-field="hydrant_number"]'), false);
    renumberHydrantItems();
}

function removeHydrantItem(button) {
    button.closest('.hydrant-item').remove();
    renumberHydrantItems();
}

function renumberHydrantItems() {
    document.querySelectorAll('#hydrantItems .hydrant-item').forEach((item, index) => {
        item.querySelector('.item-number').textContent = index + 1;
    });
}

function collectHydrantItems() {
    return Array.from(document.querySelectorAll('#hydrantItems .hydrant-item')).map(card => {
        const item = {
            hydrant_number: card.querySelector('[data-field="hydrant_number"]').value,
            name: card.querySelector('[data-field="name"]').value,
            location_detail: card.querySelector('[data-field="location_detail"]').value || null,
            hose_condition: card.querySelector('[data-field="hose_condition"]:checked')?.value === '1',
            nozzle_condition: card.querySelector('[data-field="nozzle_condition"]:checked')?.value === '1',
            coupling_condition: card.querySelector('[data-field="coupling_condition"]:checked')?.value === '1',
            wrench_condition: card.querySelector('[data-field="wrench_condition"]:checked')?.value === '1',
            valve_condition: card.querySelector('[data-field="valve_condition"]:checked')?.value === '1',
            coupling_extra_condition: card.querySelector('[data-field="coupling_extra_condition"]:checked')?.value === '1',
            remark: card.querySelector('[data-field="remark"]').value || null,
            photo_before: card.querySelector('[data-field="photo_before"]').files[0] || null,
            photo_after: card.querySelector('[data-field="photo_after"]').files[0] || null,
        };
        const hasText = [
            item.hydrant_number,
            item.name,
            item.location_detail,
            item.remark,
        ].some(Boolean);
        const hasCondition = [
            item.hose_condition,
            item.nozzle_condition,
            item.coupling_condition,
            item.wrench_condition,
            item.valve_condition,
            item.coupling_extra_condition,
        ].some(Boolean);

        return hasText || hasCondition ? item : null;
    }).filter(Boolean);
}

function resetHydrantItems(items = []) {
    document.getElementById('hydrantItems').innerHTML = '';
    if (items.length === 0) {
        addHydrantItem();
        return;
    }
    items.forEach(item => addHydrantItem(item));
}

function getLocationLabel(locationId) {
    const loc = locations.find(l => String(l.id_location) === String(locationId));
    return loc ? `${loc.id_location} - ${loc.name}` : (locationId || '-');
}

function getInspectorLabel(inspectorId) {
    const u = users.find(u => String(u.id) === String(inspectorId));
    return u ? u.username || u.name || inspectorId : (inspectorId || '-');
}

async function loadHydrants() {
    const res = await fetch(`${API_URL}/fire-hydrants`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const tbody = document.getElementById('hydrantTable');
    hydrants = Array.isArray(json.data) ? json.data : [];
    if (hydrants.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No inspections found</td></tr>';
        return;
    }
    tbody.innerHTML = hydrants.map(h => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(h.id)}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(h.reference_no)}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(h.location ? (h.location.id_location + ' - ' + h.location.name) : (h.location_id || '-'))}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(h.inspector ? h.inspector.name : (h.inspector_id || '-'))}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(formatDateOnly(h.inspection_date))}</td>
            <td class="px-6 py-4 text-sm">
                <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${h.status==='completed'?'bg-green-100 text-green-800':h.status==='signed'?'bg-blue-100 text-blue-800':'bg-yellow-100 text-yellow-800'}">${escapeHtml(h.status)}</span>
            </td>
            <td class="px-6 py-4 text-sm">
                <button onclick="viewItem(${h.id})" class="text-green-600 hover:text-green-800 mr-3 font-medium">
                    <i class="fas fa-eye mr-1"></i>View
                </button>
                <button onclick="editItem(${h.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteItem(${h.id})" class="text-red-600 hover:text-red-800 font-medium">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </td>
        </tr>
    `).join('');
}

async function openForm(options = {}) {
    const { generateReference = true } = options;
    await ensureReferenceData();
    closeView();
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Inspection';
    document.getElementById('hydrantForm').reset();
    document.getElementById('hid_id').value = '';
    document.getElementById('status').value = 'draft';
    document.getElementById('inspector_id').value = user.id || '';
    if (generateReference) {
        fillNextReferenceNo();
    }
    resetHydrantItems();
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }

async function editItem(id) {
    const h = hydrants.find(item => item.id === id);
    if (!h) return;

    const res = await fetch(`${API_URL}/fire-hydrants/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const detailJson = await res.json();
    const detail = detailJson.data || h;

    await openForm({ generateReference: false });
    document.getElementById('formTitle').textContent = 'Edit Inspection';
    document.getElementById('hid_id').value = detail.id;
    document.getElementById('reference_no').value = detail.reference_no || '';
    document.getElementById('inspection_date').value = formatDateOnly(detail.inspection_date);
    document.getElementById('location_id').value = detail.location_id || '';
    document.getElementById('inspector_id').value = detail.inspector_id || '';
    document.getElementById('notes').value = detail.notes || '';
    document.getElementById('status').value = detail.status || 'draft';
    resetHydrantItems(Array.isArray(detail.items) ? detail.items : []);
}

function closeView() {
    document.getElementById('viewModal').classList.add('hidden');
}

async function viewItem(id) {
    const res = await fetch(`${API_URL}/fire-hydrants/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const detail = json.data;

    document.getElementById('viewReference').textContent = detail.reference_no || '-';

    document.getElementById('viewDate').textContent = formatDateOnly(detail.inspection_date) || '-';

    const locName = detail.location ? (detail.location.id_location + ' - ' + detail.location.name) : (detail.location_id || '-');
    document.getElementById('viewLocation').textContent = locName;

    const statusSpan = document.getElementById('viewStatus');
    statusSpan.textContent = detail.status || '-';
    statusSpan.className = 'px-3 py-1 inline-flex text-xs font-semibold rounded-full ' +
        (detail.status === 'completed' ? 'bg-green-100 text-green-800' :
         detail.status === 'signed' ? 'bg-blue-100 text-blue-800' :
         'bg-yellow-100 text-yellow-800');

    const inspName = detail.inspector ? detail.inspector.name : (detail.inspector_id || '-');
    document.getElementById('viewInspector').textContent = inspName;

    document.getElementById('viewNotes').textContent = detail.notes || '-';

    const itemsContainer = document.getElementById('viewItems');
    const items = Array.isArray(detail.items) ? detail.items : [];
    if (items.length === 0) {
        itemsContainer.innerHTML = '<p class="text-sm text-gray-400 italic">No items</p>';
    } else {
        itemsContainer.innerHTML = items.map((item, i) => `
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Item Hydrant ${i + 1}</h4>
                    <span class="text-sm text-gray-500">${escapeHtml(item.hydrant_number || '')}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                    <div>
                        <span class="block text-gray-500">Name</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.name || '-')}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Location Detail</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.location_detail || '-')}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Remark</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.remark || '-')}</span>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                    ${viewConditionLabel('Hose', item.hose_condition)}
                    ${viewConditionLabel('Nozzle', item.nozzle_condition)}
                    ${viewConditionLabel('Coupling', item.coupling_condition)}
                    ${viewConditionLabel('Wrench', item.wrench_condition)}
                    ${viewConditionLabel('Valve', item.valve_condition)}
                    ${viewConditionLabel('Extra Coupling', item.coupling_extra_condition)}
                </div>
            </div>
        `).join('');
    }

    document.getElementById('viewModal').classList.remove('hidden');
}

function viewConditionLabel(label, value) {
    const yes = boolValue(value);
    return `
        <div class="flex items-center justify-between bg-white border border-gray-200 rounded px-3 py-1.5 text-sm">
            <span class="text-gray-600">${label}</span>
            <span class="ml-2 font-semibold ${yes ? 'text-green-600' : 'text-red-500'}">${yes ? 'Yes' : 'No'}</span>
        </div>
    `;
}

document.getElementById('hydrantForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('hid_id').value;
    const items = collectHydrantItems();
    const payload = {
        reference_no: document.getElementById('reference_no').value,
        inspection_date: document.getElementById('inspection_date').value,
        location_id: document.getElementById('location_id').value || null,
        inspector_id: document.getElementById('inspector_id').value || null,
        notes: document.getElementById('notes').value || null,
        status: document.getElementById('status').value,
    };
    const formData = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        if (value !== null && value !== undefined) formData.append(key, value);
    });

    items.forEach((item, index) => {
        Object.entries(item).forEach(([key, value]) => {
            if (value === null || value === undefined) return;
            if (value instanceof File) {
                formData.append(`items[${index}][${key}]`, value);
                return;
            }
            formData.append(`items[${index}][${key}]`, typeof value === 'boolean' ? (value ? '1' : '0') : value);
        });
    });

    if (id) formData.append('_method', 'PUT');

    const url = id ? `${API_URL}/fire-hydrants/${id}` : `${API_URL}/fire-hydrants`;
    const method = 'POST';
    const res = await fetch(url, {
        method,
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        body: formData
    });
    if (!res.ok) { alert('Save failed'); return; }
    closeForm();
    loadHydrants();
});

async function deleteItem(id) {
    if (!confirm('Delete this inspection?')) return;
    const res = await fetch(`${API_URL}/fire-hydrants/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (res.ok) loadHydrants();
}

function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
        .finally(() => { localStorage.clear(); window.location.href='/'; });
}

referenceDataPromise = loadReferenceData();
loadHydrants();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\resources\views/pages/fire_hydrants.blade.php ENDPATH**/ ?>
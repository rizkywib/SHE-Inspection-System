<?php $__env->startSection('title', 'Fire Extinguisher Inspections'); ?>

<?php $__env->startSection('content'); ?>
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Fire Extinguisher Inspections</h1>
        <p class="text-gray-600 mt-1">Manage and track fire extinguisher inspections</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Inspection
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Inspection</h2>
        <form id="extinguisherForm" class="space-y-6">
            <input type="hidden" id="eid_id">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Inspection Data</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Area ID</label>
                            <input id="area_id" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">QR Code ID</label>
                            <input id="qr_code_id" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                </div>

                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Assignment & Notes</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inspector ID</label>
                            <select id="inspector_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">- Current User -</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                            <select id="assigned_to" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">- Not Assigned -</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea id="notes" rows="7" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Fire Extinguisher Items</h3>
                        <p class="text-sm text-gray-500 mt-1">Isi item APAR sesuai kolom database.</p>
                    </div>
                    <button type="button" onclick="addExtinguisherItem()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Item
                    </button>
                </div>
                <div id="extinguisherItems" class="space-y-4"></div>
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
                <button type="button" onclick="closeForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">Cancel</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="extinguisherTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
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
                        <span class="block text-gray-500 font-medium">Reference No</span>
                        <span id="viewReferenceNo" class="text-gray-900 font-semibold"></span>
                    </div>
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
                    <div>
                        <span class="block text-gray-500 font-medium">Assigned To</span>
                        <span id="viewAssigned" class="text-gray-900 font-semibold"></span>
                    </div>
                    <div class="md:col-span-3">
                        <span class="block text-gray-500 font-medium">Notes</span>
                        <span id="viewNotes" class="text-gray-900"></span>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-5">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Fire Extinguisher Items</h3>
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
let extinguishers = [];
let referenceDataPromise;

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"').replace(/'/g, '&#039;');
}
function formatDateOnly(value) { return value ? String(value).slice(0, 10) : ''; }
function boolValue(value) { return value === true || value === 1 || value === '1'; }
function optionLabel(item, fallbackPrefix) { return `${item.id} - ${item.name || item.email || fallbackPrefix}`; }

async function fetchList(path) {
    try {
        const res = await fetch(`${API_URL}${path}`, { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
        if (!res.ok) return [];
        const json = await res.json();
        return Array.isArray(json.data) ? json.data : [];
    } catch (error) {
        return [];
    }
}
function fillSelect(id, data, emptyLabel, fallbackPrefix) {
    document.getElementById(id).innerHTML = `<option value="">${emptyLabel}</option>` + data.map(item => `<option value="${item.id}">${escapeHtml(optionLabel(item, fallbackPrefix))}</option>`).join('');
}
async function loadReferenceData() {
    const [locations, users] = await Promise.all([fetchList('/locations?simple=1'), fetchList('/users')]);
    fillSelect('location_id', locations, '- Select Location -', 'Location');
    fillSelect('inspector_id', users, '- Current User -', 'User');
    fillSelect('assigned_to', users, '- Not Assigned -', 'User');
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
        const res = await fetch(`${API_URL}/fire-extinguishers/next-reference`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        const json = await res.json();
        input.value = json.data?.reference_no || 'Auto generated';
    } catch (error) {
        input.value = 'Auto generated';
    }
}

function conditionCheckbox(field, label, item) {
    return `<label class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700"><input data-field="${field}" type="checkbox" ${boolValue(item[field]) ? 'checked' : ''} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span>${label}</span></label>`;
}
function addExtinguisherItem(item = {}) {
    const container = document.getElementById('extinguisherItems');
    const index = container.children.length + 1;
    const card = document.createElement('div');
    card.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 extinguisher-item';
    card.innerHTML = `
        <div class="flex items-center justify-between gap-3 mb-4">
            <h4 class="font-semibold text-gray-900">Item APAR <span class="item-number">${index}</span></h4>
            <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800 text-sm font-medium"><i class="fas fa-trash mr-1"></i>Remove</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input data-field="name" required value="${escapeHtml(item.name || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label><input data-field="type" value="${escapeHtml(item.type || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Location Detail</label><input data-field="location_detail" value="${escapeHtml(item.location_detail || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-2">Condition</label><div class="grid grid-cols-1 sm:grid-cols-3 gap-3">${conditionCheckbox('pressure_condition', 'Pressure', item)}${conditionCheckbox('seal_condition', 'Seal', item)}${conditionCheckbox('nozzle_condition', 'Nozzle', item)}</div></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label><input data-field="expiry_date" type="date" value="${escapeHtml(formatDateOnly(item.expiry_date))}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Photo Before</label><input data-field="photo_before" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Photo After</label><input data-field="photo_after" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white"></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label><input data-field="item_lat" type="number" step="any" value="${escapeHtml(item.item_lat || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label><input data-field="item_lng" type="number" step="any" value="${escapeHtml(item.item_lng || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Remark</label><textarea data-field="remark" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2">${escapeHtml(item.remark || '')}</textarea></div>
        </div>`;
    container.appendChild(card);
    renumberItems();
}
function removeItem(button) { button.closest('.extinguisher-item').remove(); renumberItems(); }
function renumberItems() { document.querySelectorAll('#extinguisherItems .extinguisher-item').forEach((item, index) => { item.querySelector('.item-number').textContent = index + 1; }); }
function resetItems(items = []) { document.getElementById('extinguisherItems').innerHTML = ''; (items.length ? items : [{}]).forEach(item => addExtinguisherItem(item)); }
function collectItems() {
    return Array.from(document.querySelectorAll('#extinguisherItems .extinguisher-item')).map(card => {
        const item = {
            name: card.querySelector('[data-field="name"]').value,
            type: card.querySelector('[data-field="type"]').value || null,
            location_detail: card.querySelector('[data-field="location_detail"]').value || null,
            pressure_condition: card.querySelector('[data-field="pressure_condition"]').checked,
            seal_condition: card.querySelector('[data-field="seal_condition"]').checked,
            nozzle_condition: card.querySelector('[data-field="nozzle_condition"]').checked,
            remark: card.querySelector('[data-field="remark"]').value || null,
            expiry_date: card.querySelector('[data-field="expiry_date"]').value || null,
            photo_before: card.querySelector('[data-field="photo_before"]').files[0] || null,
            photo_after: card.querySelector('[data-field="photo_after"]').files[0] || null,
            item_lat: card.querySelector('[data-field="item_lat"]').value || null,
            item_lng: card.querySelector('[data-field="item_lng"]').value || null,
        };
        return Object.values(item).some(Boolean) ? item : null;
    }).filter(Boolean);
}
function getCurrentCoordinates() {
    if (!navigator.geolocation) return Promise.resolve(null);
    return new Promise(resolve => navigator.geolocation.getCurrentPosition(p => resolve({ lat: p.coords.latitude, lng: p.coords.longitude }), () => resolve(null), { enableHighAccuracy: true, timeout: 5000, maximumAge: 60000 }));
}

async function loadExtinguishers() {
    const res = await fetch(`${API_URL}/fire-extinguishers`, { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
    const json = await res.json();
    const tbody = document.getElementById('extinguisherTable');
    extinguishers = Array.isArray(json.data) ? json.data : [];
    if (extinguishers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No inspections found</td></tr>';
        return;
    }
    tbody.innerHTML = extinguishers.map((e, index) => `<tr class="hover:bg-gray-50 transition"><td class="px-6 py-4 text-sm text-gray-900 text-center">${index + 1}</td><td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(e.reference_no)}</td><td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(formatDateOnly(e.inspection_date))}</td><td class="px-6 py-4 text-sm"><span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${e.status==='completed'?'bg-green-100 text-green-800':e.status==='signed'?'bg-blue-100 text-blue-800':'bg-yellow-100 text-yellow-800'}">${escapeHtml(e.status)}</span></td><td class="px-6 py-4 text-sm"><button onclick="viewItem(${e.id})" class="text-green-600 hover:text-green-800 mr-3 font-medium"><i class="fas fa-eye mr-1"></i>View</button><button onclick="editItem(${e.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium"><i class="fas fa-edit mr-1"></i>Edit</button><button onclick="deleteItem(${e.id})" class="text-red-600 hover:text-red-800 font-medium"><i class="fas fa-trash mr-1"></i>Delete</button></td></tr>`).join('');
}
function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Inspection';
    document.getElementById('extinguisherForm').reset();
    document.getElementById('eid_id').value = '';
    document.getElementById('status').value = 'draft';
    document.getElementById('inspector_id').value = user.id || '';
    fillNextReferenceNo();
    resetItems();
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }

function closeView() {
    document.getElementById('viewModal').classList.add('hidden');
}

async function viewItem(id) {
    const res = await fetch(`${API_URL}/fire-extinguishers/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const detail = json.data;

    document.getElementById('viewReferenceNo').textContent = detail.reference_no || '-';
    document.getElementById('viewDate').textContent = formatDateOnly(detail.inspection_date) || '-';

    const locName = detail.location ? detail.location.name : (detail.location_id || '-');
    document.getElementById('viewLocation').textContent = locName;

    const statusSpan = document.getElementById('viewStatus');
    statusSpan.textContent = detail.status || '-';
    statusSpan.className = 'px-3 py-1 inline-flex text-xs font-semibold rounded-full ' +
        (detail.status === 'completed' ? 'bg-green-100 text-green-800' :
         detail.status === 'signed' ? 'bg-blue-100 text-blue-800' :
         'bg-yellow-100 text-yellow-800');

    const inspName = detail.inspector ? detail.inspector.name : (detail.inspector_id || '-');
    document.getElementById('viewInspector').textContent = inspName;

    const assignedName = detail.assigned_to ? detail.assigned_to.name : (detail.assigned_to || '-');
    document.getElementById('viewAssigned').textContent = assignedName;

    document.getElementById('viewNotes').textContent = detail.notes || '-';

    const itemsContainer = document.getElementById('viewItems');
    const items = Array.isArray(detail.items) ? detail.items : [];
    if (items.length === 0) {
        itemsContainer.innerHTML = '<p class="text-sm text-gray-400 italic">No items</p>';
    } else {
        itemsContainer.innerHTML = items.map((item, i) => `
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Item APAR ${i + 1}</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                    <div>
                        <span class="block text-gray-500">Name</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.name || '-')}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Type</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.type || '-')}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Location Detail</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.location_detail || '-')}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Expiry Date</span>
                        <span class="font-medium text-gray-900">${escapeHtml(formatDateOnly(item.expiry_date))}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Remark</span>
                        <span class="font-medium text-gray-900">${escapeHtml(item.remark || '-')}</span>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    ${item.pressure_condition ? '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Pressure OK</span>' : '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">Pressure Bad</span>'}
                    ${item.seal_condition ? '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Seal OK</span>' : '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">Seal Bad</span>'}
                    ${item.nozzle_condition ? '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Nozzle OK</span>' : '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">Nozzle Bad</span>'}
                </div>
            </div>
        `).join('');
    }

    document.getElementById('viewModal').classList.remove('hidden');
}

async function editItem(id) {
    const e = extinguishers.find(item => item.id === id);
    if (!e) return;

    const res = await fetch(`${API_URL}/fire-extinguishers/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const detailJson = await res.json();
    const detail = detailJson.data || e;

    await openForm({ generateReference: false });
    document.getElementById('formTitle').textContent = 'Edit Inspection';
    document.getElementById('eid_id').value = detail.id;
    document.getElementById('reference_no').value = detail.reference_no || '';
    document.getElementById('inspection_date').value = formatDateOnly(detail.inspection_date);
    document.getElementById('location_id').value = detail.location_id || '';
    document.getElementById('area_id').value = detail.area_id || '';
    document.getElementById('qr_code_id').value = detail.qr_code_id || '';
    document.getElementById('inspector_id').value = detail.inspector_id || '';
    document.getElementById('assigned_to').value = detail.assigned_to || '';
    document.getElementById('notes').value = detail.notes || '';
    document.getElementById('status').value = detail.status || 'draft';
    resetItems(Array.isArray(detail.items) ? detail.items : []);
}

document.getElementById('extinguisherForm').addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('eid_id').value;
    const coords = await getCurrentCoordinates();
    const payload = {
        reference_no: document.getElementById('reference_no').value,
        inspection_date: document.getElementById('inspection_date').value,
        location_id: document.getElementById('location_id').value || null,
        area_id: document.getElementById('area_id').value || null,
        qr_code_id: document.getElementById('qr_code_id').value || null,
        inspector_id: document.getElementById('inspector_id').value || null,
        assigned_to: document.getElementById('assigned_to').value || null,
        checkin_lat: coords ? coords.lat : null,
        checkin_lng: coords ? coords.lng : null,
        notes: document.getElementById('notes').value || null,
        status: document.getElementById('status').value,
    };
    const formData = new FormData();
    Object.entries(payload).forEach(([key, value]) => { if (value !== null && value !== undefined) formData.append(key, value); });
    collectItems().forEach((item, index) => Object.entries(item).forEach(([key, value]) => {
        if (value === null || value === undefined) return;
        formData.append(`items[${index}][${key}]`, value instanceof File ? value : (typeof value === 'boolean' ? (value ? '1' : '0') : value));
    }));
    if (id) formData.append('_method', 'PUT');
    const res = await fetch(id ? `${API_URL}/fire-extinguishers/${id}` : `${API_URL}/fire-extinguishers`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        body: formData
    });
    if (!res.ok) { alert('Save failed'); return; }
    closeForm();
    loadExtinguishers();
});
async function deleteItem(id) {
    if (!confirm('Delete this inspection?')) return;
    const res = await fetch(`${API_URL}/fire-extinguishers/${id}`, { method: 'DELETE', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
    if (res.ok) loadExtinguishers();
}
function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } }).finally(() => { localStorage.clear(); window.location.href='/'; });
}

referenceDataPromise = loadReferenceData();
loadExtinguishers();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\resources\views/pages/fire_extinguishers.blade.php ENDPATH**/ ?>
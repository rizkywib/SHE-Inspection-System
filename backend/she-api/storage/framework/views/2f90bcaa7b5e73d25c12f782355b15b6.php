<?php $__env->startSection('title', 'Fire Alarm Inspections'); ?>

<?php $__env->startSection('content'); ?>
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Fire Alarm Inspections</h1>
        <p class="text-gray-600 mt-1">Manage and track fire alarm inspections</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Inspection
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Inspection</h2>
        <form id="alarmForm" class="space-y-6">
            <input type="hidden" id="aid_id">

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Inspection Data</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Reference No</label><input id="reference_no" required class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label><input id="inspection_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Location ID</label><select id="location_id" class="w-full border border-gray-300 rounded-lg px-4 py-2"><option value="">- Select Location -</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Area ID</label><input id="area_id" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">QR Code ID</label><input id="qr_code_id" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2"><option value="draft">Draft</option><option value="completed">Completed</option><option value="signed">Signed</option></select></div>
                    </div>
                </div>
                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Assignment & Notes</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Inspector ID</label><select id="inspector_id" class="w-full border border-gray-300 rounded-lg px-4 py-2"><option value="">- Current User -</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label><select id="assigned_to" class="w-full border border-gray-300 rounded-lg px-4 py-2"><option value="">- Not Assigned -</option></select></div>
                        <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea id="notes" rows="7" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea></div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Fire Alarm Items</h3>
                        <p class="text-sm text-gray-500 mt-1">Isi item alarm sesuai kolom database.</p>
                    </div>
                    <button type="button" onclick="addAlarmItem()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm"><i class="fas fa-plus mr-2"></i>Add Item</button>
                </div>
                <div id="alarmItems" class="space-y-4"></div>
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"><i class="fas fa-save mr-2"></i>Save</button>
                <button type="button" onclick="closeForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">Cancel</button>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="alarmTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let alarms = [];

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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
function conditionCheckbox(field, label, item) {
    return `<label class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700"><input data-field="${field}" type="checkbox" ${boolValue(item[field]) ? 'checked' : ''} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span>${label}</span></label>`;
}
function addAlarmItem(item = {}) {
    const container = document.getElementById('alarmItems');
    const card = document.createElement('div');
    card.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 alarm-item';
    card.innerHTML = `
        <div class="flex items-center justify-between gap-3 mb-4"><h4 class="font-semibold text-gray-900">Item Alarm <span class="item-number"></span></h4><button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800 text-sm font-medium"><i class="fas fa-trash mr-1"></i>Remove</button></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input data-field="name" required value="${escapeHtml(item.name || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Alarm Number</label><input data-field="alarm_number" value="${escapeHtml(item.alarm_number || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label><input data-field="type" value="${escapeHtml(item.type || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-1">Location Detail</label><input data-field="location_detail" value="${escapeHtml(item.location_detail || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-2">Condition</label><div class="grid grid-cols-1 sm:grid-cols-2 gap-3">${conditionCheckbox('condition_good', 'Condition Good', item)}${conditionCheckbox('correction_needed', 'Correction Needed', item)}</div></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Photo Before</label><input data-field="photo_before" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Photo After</label><input data-field="photo_after" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white"></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label><input data-field="item_lat" type="number" step="any" value="${escapeHtml(item.item_lat || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label><input data-field="item_lng" type="number" step="any" value="${escapeHtml(item.item_lng || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div></div>
            <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-1">Remark</label><textarea data-field="remark" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2">${escapeHtml(item.remark || '')}</textarea></div>
        </div>`;
    container.appendChild(card);
    renumberItems();
}
function removeItem(button) { button.closest('.alarm-item').remove(); renumberItems(); }
function renumberItems() { document.querySelectorAll('#alarmItems .item-number').forEach((el, i) => el.textContent = i + 1); }
function resetItems(items = []) { document.getElementById('alarmItems').innerHTML = ''; (items.length ? items : [{}]).forEach(item => addAlarmItem(item)); }
function collectItems() {
    return Array.from(document.querySelectorAll('#alarmItems .alarm-item')).map(card => {
        const item = {
            name: card.querySelector('[data-field="name"]').value,
            alarm_number: card.querySelector('[data-field="alarm_number"]').value || null,
            type: card.querySelector('[data-field="type"]').value || null,
            location_detail: card.querySelector('[data-field="location_detail"]').value || null,
            condition_good: card.querySelector('[data-field="condition_good"]').checked,
            correction_needed: card.querySelector('[data-field="correction_needed"]').checked,
            remark: card.querySelector('[data-field="remark"]').value || null,
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
async function loadAlarms() {
    const res = await fetch(`${API_URL}/fire-alarms`, { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
    const json = await res.json();
    const tbody = document.getElementById('alarmTable');
    alarms = Array.isArray(json.data) ? json.data : [];
    if (alarms.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No inspections found</td></tr>';
        return;
    }
    tbody.innerHTML = alarms.map(a => `<tr class="hover:bg-gray-50 transition"><td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(a.id)}</td><td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(a.reference_no)}</td><td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(formatDateOnly(a.inspection_date))}</td><td class="px-6 py-4 text-sm"><span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${a.status==='completed'?'bg-green-100 text-green-800':a.status==='signed'?'bg-blue-100 text-blue-800':'bg-yellow-100 text-yellow-800'}">${escapeHtml(a.status)}</span></td><td class="px-6 py-4 text-sm"><button onclick="editItem(${a.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium"><i class="fas fa-edit mr-1"></i>Edit</button><button onclick="deleteItem(${a.id})" class="text-red-600 hover:text-red-800 font-medium"><i class="fas fa-trash mr-1"></i>Delete</button></td></tr>`).join('');
}
function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Inspection';
    document.getElementById('alarmForm').reset();
    document.getElementById('aid_id').value = '';
    document.getElementById('status').value = 'draft';
    document.getElementById('inspector_id').value = user.id || '';
    resetItems();
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }
async function editItem(id) {
    const res = await fetch(`${API_URL}/fire-alarms/${id}`, { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
    const json = await res.json();
    const a = json.data || {};
    openForm();
    document.getElementById('formTitle').textContent = 'Edit Inspection';
    document.getElementById('aid_id').value = a.id || '';
    document.getElementById('reference_no').value = a.reference_no || '';
    document.getElementById('inspection_date').value = formatDateOnly(a.inspection_date);
    document.getElementById('location_id').value = a.location_id || '';
    document.getElementById('area_id').value = a.area_id || '';
    document.getElementById('qr_code_id').value = a.qr_code_id || '';
    document.getElementById('inspector_id').value = a.inspector_id || '';
    document.getElementById('assigned_to').value = a.assigned_to || '';
    document.getElementById('notes').value = a.notes || '';
    document.getElementById('status').value = a.status || 'draft';
    resetItems(Array.isArray(a.items) ? a.items : []);
}
document.getElementById('alarmForm').addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('aid_id').value;
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
    const res = await fetch(id ? `${API_URL}/fire-alarms/${id}` : `${API_URL}/fire-alarms`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        body: formData
    });
    if (!res.ok) { alert('Save failed'); return; }
    closeForm();
    loadAlarms();
});
async function deleteItem(id) {
    if (!confirm('Delete this inspection?')) return;
    const res = await fetch(`${API_URL}/fire-alarms/${id}`, { method: 'DELETE', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
    if (res.ok) loadAlarms();
}
function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } }).finally(() => { localStorage.clear(); window.location.href='/'; });
}
loadReferenceData();
loadAlarms();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\resources\views/pages/fire_alarms.blade.php ENDPATH**/ ?>
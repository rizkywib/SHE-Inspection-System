@extends('layouts.app')

@section('title', 'Fire Alarm Inspections')

@section('content')
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
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label><input id="inspection_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Location ID</label><select id="location_id" class="w-full border border-gray-300 rounded-lg px-4 py-2"><option value="">- Select Location -</option></select></div>
                    </div>
                </div>
                <div class="xl:col-span-6 space-y-5">
                    <div class="border-b border-gray-200 pb-3">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Assignment</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">User</label><select id="inspector_id" class="w-full border border-gray-300 rounded-lg px-4 py-2"><option value="">- Current User -</option></select></div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Area</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Inspected</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspected By</th>
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
let points = [];
let itemSeq = 0;

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
    const [locations, users, pointData] = await Promise.all([fetchList('/fire-alarm-locations'), fetchList('/users'), fetchList('/points')]);
    points = pointData;
    document.getElementById('location_id').innerHTML = `<option value="">- Select Location -</option>` + locations.map(item => `<option value="${item.id_location}">${escapeHtml(item.name)}</option>`).join('');
    fillSelect('inspector_id', users, '- Current User -', 'User');
}
function conditionRadios(field, label, item, seq) {
    const isYes = boolValue(item[field]);
    const isNo = !isYes && item[field] !== undefined && item[field] !== null && item[field] !== '';
    return `<div><label class="block text-sm font-medium text-gray-700 mb-1">${label}</label><div class="flex items-center gap-4 bg-white border border-gray-200 rounded-lg px-3 py-2"><label class="flex items-center gap-1 text-sm text-gray-700"><input type="radio" name="${field}_${seq}" data-field="${field}" value="1" ${isYes ? 'checked' : ''} class="text-blue-600 focus:ring-blue-500">Yes</label><label class="flex items-center gap-1 text-sm text-gray-700"><input type="radio" name="${field}_${seq}" data-field="${field}" value="0" ${isNo ? 'checked' : ''} class="text-blue-600 focus:ring-blue-500">No</label></div></div>`;
}
function pointOptions() {
    return `<option value="">- Select Point -</option>` + points.map(p => `<option value="${p.id}">${escapeHtml(p.name_point)}</option>`).join('');
}
function onPointChange(select) {
    const card = select.closest('.alarm-item');
    const p = points.find(point => point.id === Number(select.value));
    card.querySelector('[data-field="type"]').value = p ? (p.ket1 || '') : '';
    card.querySelector('[data-field="location_detail"]').value = p ? (p.ket2 || '') : '';
}
function addAlarmItem(item = {}) {
    const container = document.getElementById('alarmItems');
    const card = document.createElement('div');
    const seq = ++itemSeq;
    card.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 alarm-item';
    card.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Alarm Number</label><input data-field="alarm_number" value="${escapeHtml(item.alarm_number || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><select data-field="name" required onchange="onPointChange(this)" class="w-full border border-gray-300 rounded-lg px-4 py-2">${pointOptions()}</select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label><input data-field="type" readonly value="${escapeHtml(item.type || '')}" class="w-full border border-gray-300 bg-gray-100 rounded-lg px-4 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Location Detail</label><input data-field="location_detail" readonly value="${escapeHtml(item.location_detail || '')}" class="w-full border border-gray-300 bg-gray-100 rounded-lg px-4 py-2"></div>
            <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-2">Condition</label><div class="grid grid-cols-1 sm:grid-cols-2 gap-3">${conditionRadios('condition_good', 'Condition Good', item, seq)}${conditionRadios('correction_needed', 'Correction Needed', item, seq)}</div></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Photo Before</label><input data-field="photo_before" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Photo After</label><input data-field="photo_after" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white"></div>
            <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-1">Remark</label><textarea data-field="remark" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2">${escapeHtml(item.remark || '')}</textarea></div>
        </div>`;
    container.appendChild(card);
    if (item.name) {
        const nameSelect = card.querySelector('[data-field="name"]');
        const p = points.find(point => point.name_point === item.name);
        if (p) {
            nameSelect.value = p.id;
            onPointChange(nameSelect);
        }
    }
}
function resetItems(items = []) { document.getElementById('alarmItems').innerHTML = ''; (items.length ? items : [{}]).forEach(item => addAlarmItem(item)); }
function collectItems() {
    return Array.from(document.querySelectorAll('#alarmItems .alarm-item')).map(card => {
        const nameSelect = card.querySelector('[data-field="name"]');
        const selectedPoint = points.find(point => point.id === Number(nameSelect.value));
        const radioValue = field => { const el = card.querySelector(`[data-field="${field}"]:checked`); return el ? el.value === '1' : false; };
        const item = {
            name: selectedPoint ? selectedPoint.name_point : nameSelect.value,
            alarm_number: card.querySelector('[data-field="alarm_number"]').value || null,
            type: card.querySelector('[data-field="type"]').value || null,
            location_detail: card.querySelector('[data-field="location_detail"]').value || null,
            condition_good: radioValue('condition_good'),
            correction_needed: radioValue('correction_needed'),
            remark: card.querySelector('[data-field="remark"]').value || null,
            photo_before: card.querySelector('[data-field="photo_before"]').files[0] || null,
            photo_after: card.querySelector('[data-field="photo_after"]').files[0] || null,
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
    tbody.innerHTML = alarms.map((a, i) => `<tr class="hover:bg-gray-50 transition"><td class="px-6 py-4 text-sm text-gray-900">${i + 1}</td><td class="px-6 py-4 text-sm text-gray-900 font-medium"><a href="/dashboard/fire-alarms/${a.id}/items" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">${escapeHtml((a.location && a.location.name) || (a.area && a.area.name) || '-')}</a></td><td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(formatDateOnly(a.inspection_date))}</td><td class="px-6 py-4 text-sm text-gray-500">${escapeHtml((a.inspector && a.inspector.name) || '-')}</td><td class="px-6 py-4 text-sm"><button onclick="exportItem(${a.id})" class="text-green-600 hover:text-green-800 mr-3 font-medium"><i class="fas fa-file-excel mr-1"></i>Export</button><button onclick="printItem(${a.id})" class="text-purple-600 hover:text-purple-800 mr-3 font-medium"><i class="fas fa-print mr-1"></i>Print</button><button onclick="editItem(${a.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium"><i class="fas fa-edit mr-1"></i>Edit</button><button onclick="deleteItem(${a.id})" class="text-red-600 hover:text-red-800 font-medium"><i class="fas fa-trash mr-1"></i>Delete</button></td></tr>`).join('');
}
function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Inspection';
    document.getElementById('alarmForm').reset();
    document.getElementById('aid_id').value = '';
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
    document.getElementById('inspection_date').value = formatDateOnly(a.inspection_date);
    document.getElementById('location_id').value = a.location_id || '';
    document.getElementById('inspector_id').value = a.inspector_id || '';
    resetItems(Array.isArray(a.items) ? a.items : []);
}
document.getElementById('alarmForm').addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('aid_id').value;
    const coords = await getCurrentCoordinates();
    const payload = {
        inspection_date: document.getElementById('inspection_date').value,
        location_id: document.getElementById('location_id').value || null,
        inspector_id: document.getElementById('inspector_id').value || null,
        checkin_lat: coords ? coords.lat : null,
        checkin_lng: coords ? coords.lng : null,
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
function conditionMark(value) {
    return boolValue(value) ? '<span class="check">&#10003;</span>' : '<span class="check">X</span>';
}
function signatureDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Asia/Bangkok'
    }).format(date);
}
function printLogoHtml() {
    return '<img src="/images/ecogreen-logo-print.png" class="print-company-logo" alt="Ecogreen Oleochemicals">';
}
function signatureImage(path) {
    if (!path) return '<div class="print-sign-space"></div>';
    const url = `/${String(path).replace(/^\/+/, '')}`;
    return `<img src="${escapeHtml(url)}" class="print-sign-image" alt="Signature">`;
}
function alarmReport(detail) {
    const locationName = (detail.location && detail.location.name) || (detail.area && detail.area.name) || detail.location_id || '-';
    const inspectorName = (detail.inspector && detail.inspector.name) || detail.inspector_id || '-';
    const inspectorPosition = (detail.inspector && detail.inspector.position) || 'Safety Inspector';
    const signer = detail.signer || null;
    const signerName = (signer && signer.name) || 'Belum di ttd';
    const signerPosition = (signer && signer.position) || 'Safety Supervisor';
    const signedDate = signer ? signatureDate(detail.signed_at) : '';
    const itemRows = (detail.items || []).map((item, index) => `<tr>
        <td class="center">${index + 1}</td>
        <td>${escapeHtml(item.name || '-')}</td>
        <td>${escapeHtml(item.alarm_number || '-')}</td>
        <td>${escapeHtml(item.type || '-')}</td>
        <td>${escapeHtml(item.location_detail || '-')}</td>
        <td class="center">${conditionMark(item.condition_good)}</td>
        <td class="center">${conditionMark(item.correction_needed)}</td>
        <td>${escapeHtml(item.remark || '-')}</td>
    </tr>`).join('');

    return `<div class="print-page">
        <div class="print-top">
            <div class="print-brand">${printLogoHtml()}<span>PT. Ecogreen Oleochemicals</span></div>
            <div class="print-doc-code">EOB-Saf-004 Rev. 4 31/12/2018</div>
        </div>
        <div class="print-meta">
            <div>Batam Plant</div>
            <div>Location : <strong>${escapeHtml(locationName)}</strong></div>
            <div>Date Inspected : <strong>${escapeHtml(formatDatePrint(detail.inspection_date))}</strong></div>
        </div>
        <table class="print-table">
            <thead>
                <tr class="print-title-row"><th colspan="8">FIRE ALARM MONTHLY INSPECTION</th></tr>
                <tr>
                    <th>No</th>
                    <th>Name</th>
                    <th>Alarm Number</th>
                    <th>Type</th>
                    <th>Specified Location</th>
                    <th>Condition Good</th>
                    <th>Correction Needed</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>${itemRows || '<tr><td colspan="8" class="center">No items</td></tr>'}</tbody>
        </table>
        <div class="print-notes">
            <div>Note :</div>
            <div><div>&#8730; = Function Well</div><div>X = Need Correction</div></div>
        </div>
        <div class="print-signatures">
            <div class="print-sign-block">
                <div>Inspected by,</div>
                ${signatureImage(detail.inspector && detail.inspector.signature_path)}
                <div class="print-sign-name">${escapeHtml(inspectorName)}</div>
                <div>${escapeHtml(inspectorPosition)}</div>
            </div>
            <div class="print-sign-block right">
                <div>Noted by,</div>
                <div class="print-signature-line">
                    ${signatureImage(signer && signer.signature_path)}
                    ${signedDate ? `<span class="print-sign-date">${escapeHtml(signedDate)}</span>` : ''}
                </div>
                <div class="print-sign-name">${escapeHtml(signerName)}</div>
                <div>${escapeHtml(signerPosition)}</div>
            </div>
        </div>
    </div>`;
}
function reportStyles() {
    return `@page{size:A4 landscape;margin:8mm}*{box-sizing:border-box}body{margin:0;padding:8mm;background:#e5e7eb}.print-page{font-family:Arial,sans-serif;color:#000;width:100%;font-size:11px;line-height:1.25;background:#fff}.print-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px}.print-brand{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700}.print-company-logo{width:38px;height:50px;object-fit:contain;display:block}.print-doc-code{font-size:11px;text-align:right}.print-meta{margin-bottom:20px}.print-meta div{margin:4px 0}.print-table{border-collapse:collapse;width:100%;table-layout:fixed;font-size:10px}.print-table th,.print-table td{border:1px solid #000;padding:3px 4px;vertical-align:middle}.print-table th{font-weight:700;text-align:center}.print-title-row th{background:#000;color:#fff;font-size:14px;padding:4px 0}.center{text-align:center}.check{font-size:17px;font-weight:700;line-height:1}.print-notes{display:flex;justify-content:flex-end;gap:70px;margin-top:4px}.print-signatures{display:flex;justify-content:space-between;margin-top:54px}.print-sign-block{width:260px}.print-sign-block.right{margin-right:38px}.print-sign-image{height:42px;max-width:120px;object-fit:contain;display:block;margin:12px 0 4px 8px}.print-signature-line{display:flex;align-items:flex-end;gap:8px;min-height:58px}.print-sign-date{font-size:8px;margin-bottom:5px;white-space:nowrap}.print-sign-space{height:58px}.print-sign-name{font-weight:700;text-decoration:underline}`;
}
function formatDatePrint(value) {
    if (!value) return '';
    const date = new Date(String(value).slice(0, 10));
    if (Number.isNaN(date.getTime())) return String(value).slice(0, 10);
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Asia/Bangkok'
    }).format(date);
}
async function alarmDetail(id) {
    const res = await fetch(`${API_URL}/fire-alarms/${id}`, { cache: 'no-store', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } });
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Gagal memuat inspection');
    return json.data;
}
async function exportItem(id) {
    try {
        const detail = await alarmDetail(id);
        const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"><style>${reportStyles()}</style></head><body>${alarmReport(detail)}</body></html>`;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `fire_alarm_inspection_${detail.reference_no || id}.xls`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } catch (error) {
        alert(error.message || 'Export failed');
    }
}
async function printItem(id) {
    const preview = window.open('', '_blank');
    if (!preview) {
        alert('Pop-up diblokir. Izinkan pop-up untuk membuka print preview.');
        return;
    }
    preview.document.write('<p style="font-family:Arial;padding:24px">Preparing print preview...</p>');
    try {
        const detail = await alarmDetail(id);
        preview.document.open();
        preview.document.write(`<!DOCTYPE html><html><head><title>Fire Alarm Inspection</title><style>${reportStyles()}@media print{body{padding:0}}</style></head><body>${alarmReport(detail)}</body></html>`);
        preview.document.close();
        preview.focus();
        preview.print();
    } catch (error) {
        preview.document.body.innerHTML = `<p style="color:#b91c1c;font-family:Arial;padding:24px">${escapeHtml(error.message)}</p>`;
    }
}
function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } }).finally(() => { localStorage.clear(); window.location.href='/'; });
}
window.printItem = printItem;
window.exportItem = exportItem;
loadReferenceData();
loadAlarms();
</script>
@endsection

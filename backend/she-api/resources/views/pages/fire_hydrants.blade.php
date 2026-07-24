@extends('layouts.app')

@section('title', 'Fire Hydrant Inspections')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Fire Hydrant Inspections</h1>
        
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
            <input type="hidden" id="reference_no">
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

    <style>
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
    }
    .no-print { display: none !important; }
    .print-page {
        font-family: Arial, sans-serif;
        color: #000;
        padding: 0;
        margin: 0;
        width: 100%;
        font-size: 11px;
        line-height: 1.25;
    }
    #printContent { padding: 0 !important; }
    .print-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
    .print-brand { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; }
    .print-logo { width: 30px; height: 30px; position: relative; display: inline-block; }
    .print-logo span { position: absolute; display: block; background: #27930f; border-radius: 999px 999px 999px 4px; transform: rotate(-35deg); }
    .print-logo span:nth-child(1) { width: 13px; height: 20px; left: 2px; top: 1px; }
    .print-logo span:nth-child(2) { width: 12px; height: 18px; left: 15px; top: 0; }
    .print-logo span:nth-child(3) { width: 10px; height: 16px; left: 8px; top: 14px; }
    .print-doc-code { font-size: 11px; text-align: right; }
    .print-meta { margin-bottom: 20px; font-size: 11px; }
    .print-meta div { margin: 4px 0; }
    .print-table { border-collapse: collapse; width: 100%; table-layout: fixed; font-size: 10px; }
    .print-table th, .print-table td { border: 1px solid #000; padding: 2px 3px; vertical-align: middle; }
    .print-table th { font-weight: 700; text-align: center; }
    .print-table .print-title-row th { background: #000; color: #fff; font-size: 14px; padding: 4px 0; }
    .print-table .center { text-align: center; }
    .print-table .check { font-family: Arial, sans-serif; font-size: 17px; font-weight: 700; line-height: 1; }
    .print-notes { display: flex; justify-content: flex-end; gap: 70px; margin-top: 4px; font-size: 11px; }
    .print-signatures { display: flex; justify-content: space-between; margin-top: 54px; font-size: 11px; }
    .print-sign-block { width: 260px; }
    .print-sign-block.right { margin-right: 38px; }
    .print-sign-image { height: 42px; max-width: 120px; object-fit: contain; display: block; margin: 12px 0 4px 8px; }
    .print-signature-line { display: flex; align-items: flex-end; gap: 8px; min-height: 58px; }
    .print-sign-date { font-size: 8px; color: #444; margin-bottom: 5px; white-space: nowrap; }
    .print-sign-space { height: 58px; }
    .print-sign-name { font-weight: 700; text-decoration: underline; }
}
</style>

<!-- Print Area (hidden, shown during print) -->
<div id="printArea" class="hidden">
    <div id="printContent" class="bg-white p-8" style="font-family: Arial, sans-serif; color: #000;">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg overflow-hidden no-print">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspected By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="hydrantTable" class="bg-white divide-y divide-gray-200">
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
                        <span class="block text-gray-500 font-medium">Inspection Date</span>
                        <span id="viewDate" class="text-gray-900 font-semibold"></span>
                    </div>
                    <div>
                        <span class="block text-gray-500 font-medium">Location</span>
                        <span id="viewLocation" class="text-gray-900 font-semibold"></span>
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm" id="viewItemsTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hydrant Number</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location Detail</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hose</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nozzle</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Coupling</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Wrench</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Valve</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Extra Coupling</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remark</th>
                                </tr>
                            </thead>
                            <tbody id="viewItems" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="px-8 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeView()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Location Detail Modal -->
<div id="locationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto" onclick="if(event.target===this)closeLocationModal()">
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl transform transition-all" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-8 py-5 border-b border-gray-200">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Fire Hydrant Items Checklist</h2>
                    <p id="locationModalTitle" class="text-sm text-blue-600 font-medium mt-0.5"></p>
                </div>
                <button onclick="closeLocationModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="px-8 py-6">
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo Before</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo After</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Inspection</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="locationDetailTable" class="bg-white divide-y divide-gray-200">
                            <tr><td colspan="15" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="px-8 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeLocationModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">Close</button>
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
    if (overwrite || !nameInput.value) nameInput.value = nameValue;
    if (overwrite || !locationInput.value) locationInput.value = locationValue;
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

function itemPhotoPreview(field, label, path = '') {
    const url = photoUrl(path);
    if (!url) {
        return `<div data-photo-preview="${field}" class="mt-2 text-xs text-gray-400">No photo saved</div>`;
    }
    const image = canPreviewImage(url)
        ? `<img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="mt-2 h-24 w-32 object-cover rounded-lg border border-gray-200 bg-white">`
        : `<div class="mt-2 text-xs text-gray-500 bg-white border border-gray-200 rounded-lg px-3 py-2">Preview tidak tersedia untuk HEIC/HEIF</div>`;
    return `
        <div data-photo-preview="${field}" class="mt-2">
            ${image}
            <a href="${escapeHtml(url)}" target="_blank" class="inline-flex items-center mt-2 text-xs font-medium text-blue-600 hover:text-blue-800">
                <i class="fas fa-external-link-alt mr-1"></i>Open saved photo
            </a>
        </div>
    `;
}

function updateSelectedPhotoPreview(input) {
    const wrapper = input.closest('[data-photo-wrapper]');
    const preview = wrapper?.querySelector(`[data-photo-preview="${input.dataset.field}"]`);
    const file = input.files[0];
    if (!preview || !file) return;
    const localUrl = URL.createObjectURL(file);
    const isPreviewable = file.type.startsWith('image/') && !/\.(heic|heif)$/i.test(file.name);
    preview.innerHTML = isPreviewable
        ? `<img src="${localUrl}" alt="Selected photo" class="mt-2 h-24 w-32 object-cover rounded-lg border border-gray-200 bg-white"><div class="mt-1 text-xs text-gray-500">${escapeHtml(file.name)}</div>`
        : `<div class="mt-2 text-xs text-gray-500 bg-white border border-gray-200 rounded-lg px-3 py-2">${escapeHtml(file.name)} selected</div>`;
}

function viewPhotoPreview(label, path) {
    const url = photoUrl(path);
    if (!url) {
        return `
            <div class="bg-white border border-gray-200 rounded-lg px-3 py-3 text-sm text-gray-400">
                <span class="block text-gray-500 font-medium mb-1">${label}</span>
                No photo
            </div>
        `;
    }
    const image = canPreviewImage(url)
        ? `<img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-32 w-full object-cover rounded-lg border border-gray-200 bg-white">`
        : `<div class="h-32 flex items-center justify-center text-xs text-gray-500 bg-white border border-gray-200 rounded-lg">HEIC/HEIF preview unavailable</div>`;
    return `
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <span class="block text-gray-500 font-medium mb-2">${label}</span>
            ${image}
            <a href="${escapeHtml(url)}" target="_blank" class="inline-flex items-center mt-2 text-xs font-medium text-blue-600 hover:text-blue-800">
                <i class="fas fa-external-link-alt mr-1"></i>Open photo
            </a>
        </div>
    `;
}

function viewPhotoPreviewSmall(label, path) {
    const url = photoUrl(path);
    if (!url) {
        return `
            <div class="bg-white border border-gray-200 rounded-lg p-2 text-sm text-gray-400">
                <span class="block text-gray-500 font-medium text-xs mb-1">${label}</span>
                <span class="text-xs">No photo</span>
            </div>
        `;
    }
    const image = canPreviewImage(url)
        ? `<img src="${escapeHtml(url)}" alt="${escapeHtml(label)}" class="h-16 w-full object-cover rounded border border-gray-200 bg-white">`
        : `<div class="h-16 flex items-center justify-center text-xs text-gray-500 bg-white border border-gray-200 rounded">HEIC/HEIF</div>`;
    return `
        <div class="bg-white border border-gray-200 rounded-lg p-2">
            <span class="block text-gray-500 font-medium text-xs mb-1">${label}</span>
            ${image}
            <a href="${escapeHtml(url)}" target="_blank" class="inline-flex items-center mt-1 text-xs font-medium text-blue-600 hover:text-blue-800">
                <i class="fas fa-external-link-alt mr-1"></i>Open
            </a>
        </div>
    `;
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
            <div data-photo-wrapper>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo Before</label>
                <input data-field="photo_before" type="file" accept="image/*,.heic,.heif" onchange="updateSelectedPhotoPreview(this)" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                ${itemPhotoPreview('photo_before', 'Photo Before', item.photo_before || '')}
            </div>
            <div data-photo-wrapper>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo After</label>
                <input data-field="photo_after" type="file" accept="image/*,.heic,.heif" onchange="updateSelectedPhotoPreview(this)" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                ${itemPhotoPreview('photo_after', 'Photo After', item.photo_after || '')}
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
        const hasText = [item.hydrant_number, item.name, item.location_detail, item.remark].some(Boolean);
        const hasCondition = [item.hose_condition, item.nozzle_condition, item.coupling_condition, item.wrench_condition, item.valve_condition, item.coupling_extra_condition].some(Boolean);
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
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No inspections found</td></tr>';
        return;
    }
    tbody.innerHTML = hydrants.map((h, index) => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm text-gray-900 text-center">${index + 1}</td>
            <td class="px-6 py-4 text-sm text-blue-600 hover:text-blue-800 cursor-pointer font-medium" onclick="event.stopPropagation(); window.location.href='/dashboard/fire-hydrant-checklist?location_id=${h.location_id || 'null'}&inspection_id=${h.id}'">${escapeHtml(h.location ? (h.location.name) : (h.location_id || '-'))}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(formatDateOnly(h.inspection_date))}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(h.inspector ? h.inspector.name : (h.inspector_id || '-'))}</td>
            <td class="px-6 py-4 text-sm">
                <button onclick="exportItem(${h.id})" class="text-green-600 hover:text-green-800 mr-3 font-medium">
                    <i class="fas fa-file-excel mr-1"></i>Export
                </button>
                <button onclick="printItem(${h.id})" class="text-purple-600 hover:text-purple-800 mr-3 font-medium">
                    <i class="fas fa-print mr-1"></i>Print
                </button>
                <button onclick="signItem(${h.id})" ${h.signed_by ? 'disabled' : ''} class="mr-3 font-medium ${h.signed_by ? 'text-gray-400 cursor-not-allowed' : 'text-blue-600 hover:text-blue-800'}" title="${h.signed_by ? 'Signed by ' + escapeHtml(h.signer?.name || '-') : 'Sign this inspection'}">
                    <i class="fas fa-signature mr-1"></i>${h.signed_by ? 'Signed' : 'Signature'}
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
    resetHydrantItems(Array.isArray(detail.items) ? detail.items : []);
}

function closeView() {
    document.getElementById('viewModal').classList.add('hidden');
}

function closeLocationModal() {
    document.getElementById('locationModal').classList.add('hidden');
}

async function showLocationDetail(locationId) {
    if (!locationId) return;
    const loc = locations.find(l => String(l.id_location) === String(locationId));
    document.getElementById('locationModalTitle').textContent = loc ? loc.name : ('Location #' + locationId);
    document.getElementById('locationModal').classList.remove('hidden');

    const res = await fetch(`${API_URL}/fire-hydrants?location_id=${locationId}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const inspections = Array.isArray(json.data) ? json.data : [];
    
    const tbody = document.getElementById('locationDetailTable');
    
    if (inspections.length === 0) {
        tbody.innerHTML = '<tr><td colspan="15" class="px-6 py-8 text-center text-gray-500">No hydrant inspections found for this location</td></tr>';
        return;
    }

    const allItems = [];
    inspections.forEach(inspection => {
        if (Array.isArray(inspection.items)) {
            inspection.items.forEach((item, itemIndex) => {
                allItems.push({
                    ...item,
                    inspection_id: inspection.id,
                    item_index: itemIndex,
                    inspection_date: inspection.inspection_date
                });
            });
        }
    });

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
                <td class="px-4 py-3 text-sm text-center">${item.hose_condition ? '✓' : '✗'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.nozzle_condition ? '✓' : '✗'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.coupling_condition ? '✓' : '✗'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.wrench_condition ? '✓' : '✗'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.valve_condition ? '✓' : '✗'}</td>
                <td class="px-4 py-3 text-sm text-center">${item.coupling_extra_condition ? '✓' : '✗'}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(item.remark || '-')}</td>
                <td class="px-4 py-3 text-sm text-center">${photoBefore}</td>
                <td class="px-4 py-3 text-sm text-center">${photoAfter}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${lastInspection}</td>
                <td class="px-4 py-3 text-sm text-center">
                    <a href="/dashboard/fire-hydrants/edit?id=${item.inspection_id}&item=${item.item_index}&location_id=${encodeURIComponent(locationId)}&inspection_id=${item.inspection_id}" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                </td>
            </tr>
        `;
    }).join('');
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
    const inspName = detail.inspector ? detail.inspector.name : (detail.inspector_id || '-');
    document.getElementById('viewInspector').textContent = inspName;
    document.getElementById('viewNotes').textContent = detail.notes || '-';

    const itemsContainer = document.getElementById('viewItems');
    const items = Array.isArray(detail.items) ? detail.items : [];
    if (items.length === 0) {
        itemsContainer.innerHTML = '<tr><td colspan="11" class="px-4 py-6 text-center text-sm text-gray-400 italic">No items</td></tr>';
    } else {
        itemsContainer.innerHTML = items.map((item, i) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-3 py-2 text-sm text-gray-900 text-center">${i + 1}</td>
                <td class="px-3 py-2 text-sm text-gray-900 font-medium">${escapeHtml(item.hydrant_number || '-')}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(item.name || '-')}</td>
                <td class="px-3 py-2 text-sm text-gray-500">${escapeHtml(item.location_detail || '-')}</td>
                <td class="px-3 py-2 text-sm text-center">${boolValue(item.hose_condition) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-3 py-2 text-sm text-center">${boolValue(item.nozzle_condition) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-3 py-2 text-sm text-center">${boolValue(item.coupling_condition) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-3 py-2 text-sm text-center">${boolValue(item.wrench_condition) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-3 py-2 text-sm text-center">${boolValue(item.valve_condition) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-3 py-2 text-sm text-center">${boolValue(item.coupling_extra_condition) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-red-500 font-bold">✗</span>'}</td>
                <td class="px-3 py-2 text-sm text-gray-500 max-w-[150px] truncate" title="${escapeHtml(item.remark || '')}">${escapeHtml(item.remark || '-')}</td>
            </tr>
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
    if (!res.ok) {
        const error = await res.json().catch(() => null);
        const message = error?.message || Object.values(error?.errors || {}).flat()[0] || 'Save failed';
        alert(message);
        return;
    }
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

async function signItem(id) {
    const inspection = hydrants.find(item => item.id === id);
    if (!inspection || inspection.signed_by) return;
    if (!confirm('Add your signature to this inspection?')) return;

    const res = await fetch(`${API_URL}/fire-hydrants/${id}/sign`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });

    if (!res.ok) {
        const error = await res.json().catch(() => null);
        alert(error?.message || 'Failed to sign inspection');
        return;
    }

    await loadHydrants();
}

function printConditionMark(value) {
    return boolValue(value) ? '<span class="check">&#10003;</span>' : '<span class="check">X</span>';
}

function formatDatePrint(value) {
    if (!value) return '-';
    const d = new Date(value + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return value;
    return String(d.getDate()).padStart(2, '0') + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + d.getFullYear();
}

function formatSignatureDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'Asia/Bangkok'
    }).format(date);
}

function printLogoHtml() {
    return '<span class="print-logo"><span></span><span></span><span></span></span>';
}

function printSignatureHtml(path) {
    const url = photoUrl(path);
    if (!url || !canPreviewImage(url)) {
        return '<div class="print-sign-space"></div>';
    }
    return '<img src="' + escapeHtml(url) + '" class="print-sign-image" alt="Inspector signature">';
}

function buildPrintHtml(detail) {
    const locName = detail.location ? detail.location.name : (detail.location_id || '-');
    const inspName = detail.inspector ? detail.inspector.name : (detail.inspector_id || '-');
    const inspPosition = detail.inspector?.position || 'Safety Inspector';
    const signedUser = detail.signer || null;
    const inspectorSignature = detail.inspector?.signature_path || '';
    const notedSignature = signedUser?.signature_path || '';
    const notedName = signedUser?.name || 'Belum di ttd';
    const notedPosition = signedUser?.position || 'Safety Supervisor';
    const signatureDate = signedUser ? formatSignatureDate(detail.signed_at) : '';
    const items = Array.isArray(detail.items) ? detail.items : [];
    
    let itemsHtml = '';
    if (items.length === 0) {
        itemsHtml = '<tr><td colspan="11" class="center" style="padding:10px;font-style:italic;">No items</td></tr>';
    } else {
        items.forEach((item, i) => {
            itemsHtml += '<tr>' +
                '<td class="center">' + (i + 1) + '</td>' +
                '<td>' + escapeHtml(item.hydrant_number || '-') + '</td>' +
                '<td>' + escapeHtml(item.name || '-') + '</td>' +
                '<td>' + escapeHtml(item.location_detail || '-') + '</td>' +
                '<td class="center">' + printConditionMark(item.hose_condition) + '</td>' +
                '<td class="center">' + printConditionMark(item.nozzle_condition) + '</td>' +
                '<td class="center">' + printConditionMark(item.coupling_condition) + '</td>' +
                '<td class="center">' + printConditionMark(item.wrench_condition) + '</td>' +
                '<td class="center">' + printConditionMark(item.valve_condition) + '</td>' +
                '<td class="center">' + printConditionMark(item.coupling_extra_condition) + '</td>' +
                '<td>' + escapeHtml(item.remark || '-') + '</td>' +
            '</tr>';
        });
    }

    return `
        <div class="print-page">
            <div class="print-top">
                <div class="print-brand">
                    ${printLogoHtml()}
                    <span>PT. Ecogreen Oleochemicals</span>
                </div>
                <div class="print-doc-code">EOB-Saf-004 Rev. 4 31/12/2018</div>
            </div>

            <div class="print-meta">
                <div>Batam Plan</div>
                <div>Location : <strong>${escapeHtml(locName)}</strong></div>
                <div>Date Inspected : <strong>${formatDatePrint(detail.inspection_date)}</strong></div>
            </div>
            
            <table class="print-table">
                <colgroup>
                    <col style="width: 2.6%;">
                    <col style="width: 24.6%;">
                    <col style="width: 7.3%;">
                    <col style="width: 22.4%;">
                    <col style="width: 4.1%;">
                    <col style="width: 5.8%;">
                    <col style="width: 7.6%;">
                    <col style="width: 6.7%;">
                    <col style="width: 4.8%;">
                    <col style="width: 7.8%;">
                    <col style="width: 6.3%;">
                </colgroup>
                <thead>
                    <tr class="print-title-row">
                        <th colspan="11">FIRE HYDRANT MONTHLY INSPECTION</th>
                    </tr>
                    <tr>
                        <th rowspan="2">NO</th>
                        <th rowspan="2">Nama</th>
                        <th rowspan="2">NOMOR<br>HYDRANT</th>
                        <th rowspan="2">LOCATION</th>
                        <th colspan="4">HYDRANT BOX</th>
                        <th colspan="2">HYDRANT PILLAR</th>
                        <th rowspan="2">REMARK</th>
                    </tr>
                    <tr>
                        <th>HOSE</th>
                        <th>NOZZLE</th>
                        <th>COUPLING</th>
                        <th>WRENCH</th>
                        <th>VALVE</th>
                        <th>COUPLING</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>

            <div class="print-notes">
                <div>Note :</div>
                <div>
                    <div>&#8730; = Function Well</div>
                    <div>X = Need Correction</div>
                </div>
            </div>

            <div class="print-signatures">
                <div class="print-sign-block">
                    <div>Inspected by,</div>
                    ${printSignatureHtml(inspectorSignature)}
                    <div class="print-sign-name">${escapeHtml(inspName)}</div>
                    <div>${escapeHtml(inspPosition)}</div>
                </div>
                <div class="print-sign-block right">
                    <div>Noted by,</div>
                    <div class="print-signature-line">
                        ${printSignatureHtml(notedSignature)}
                        ${signatureDate ? `<span class="print-sign-date">${escapeHtml(signatureDate)}</span>` : ''}
                    </div>
                    <div class="print-sign-name">${escapeHtml(notedName)}</div>
                    <div>${escapeHtml(notedPosition)}</div>
                </div>
            </div>
        </div>
    `;
}

async function waitForPrintImages(container) {
    const images = Array.from(container.querySelectorAll('img'));
    await Promise.all(images.map(image => {
        if (image.complete) return Promise.resolve();

        return new Promise(resolve => {
            const finish = () => resolve();
            image.addEventListener('load', finish, { once: true });
            image.addEventListener('error', finish, { once: true });
            setTimeout(finish, 3000);
        });
    }));
}

function buildPrintPreviewDocument(detail) {
    const title = `Fire Hydrant Inspection - ${detail.reference_no || detail.id || ''}`;

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHtml(title)}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 8mm; background: #e5e7eb; }
        .preview-sheet { width: 281mm; min-height: 194mm; margin: 0 auto; padding: 8mm; background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,.18); }
        .print-page { font-family: Arial, sans-serif; color: #000; padding: 0; margin: 0; width: 100%; font-size: 11px; line-height: 1.25; }
        .print-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
        .print-brand { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; }
        .print-logo { width: 30px; height: 30px; position: relative; display: inline-block; }
        .print-logo span { position: absolute; display: block; background: #27930f; border-radius: 999px 999px 999px 4px; transform: rotate(-35deg); }
        .print-logo span:nth-child(1) { width: 13px; height: 20px; left: 2px; top: 1px; }
        .print-logo span:nth-child(2) { width: 12px; height: 18px; left: 15px; top: 0; }
        .print-logo span:nth-child(3) { width: 10px; height: 16px; left: 8px; top: 14px; }
        .print-doc-code { font-size: 11px; text-align: right; }
        .print-meta { margin-bottom: 20px; font-size: 11px; }
        .print-meta div { margin: 4px 0; }
        .print-table { border-collapse: collapse; width: 100%; table-layout: fixed; font-size: 10px; }
        .print-table th, .print-table td { border: 1px solid #000; padding: 2px 3px; vertical-align: middle; }
        .print-table th { font-weight: 700; text-align: center; }
        .print-table .print-title-row th { background: #000; color: #fff; font-size: 14px; padding: 4px 0; }
        .print-table .center { text-align: center; }
        .print-table .check { font-family: Arial, sans-serif; font-size: 17px; font-weight: 700; line-height: 1; }
        .print-notes { display: flex; justify-content: flex-end; gap: 70px; margin-top: 4px; font-size: 11px; }
        .print-signatures { display: flex; justify-content: space-between; margin-top: 54px; font-size: 11px; }
        .print-sign-block { width: 260px; }
        .print-sign-block.right { margin-right: 38px; }
        .print-sign-image { height: 42px; max-width: 120px; object-fit: contain; display: block; margin: 12px 0 4px 8px; }
        .print-signature-line { display: flex; align-items: flex-end; gap: 8px; min-height: 58px; }
        .print-sign-date { font-size: 8px; color: #444; margin-bottom: 5px; white-space: nowrap; }
        .print-sign-space { height: 58px; }
        .print-sign-name { font-weight: 700; text-decoration: underline; }
        @media print {
            body { padding: 0; background: #fff; }
            .preview-sheet { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <main id="printPreview" class="preview-sheet">${buildPrintHtml(detail)}</main>
</body>
</html>`;
}

async function printItem(id) {
    const previewWindow = window.open('', '_blank');
    if (!previewWindow) {
        alert('Pop-up diblokir. Izinkan pop-up untuk membuka PDF print preview.');
        return;
    }

    previewWindow.document.write('<!DOCTYPE html><title>Preparing PDF...</title><p style="font-family:Arial;padding:24px">Preparing PDF print preview...</p>');

    try {
        await ensureReferenceData();
        const res = await fetch(`${API_URL}/fire-hydrants/${id}`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Failed to load inspection data');

        const json = await res.json();
        const detail = json.data;

        previewWindow.document.open();
        previewWindow.document.write(buildPrintPreviewDocument(detail));
        previewWindow.document.close();

        await waitForPrintImages(previewWindow.document);
        previewWindow.focus();
        previewWindow.print();
    } catch (error) {
        previewWindow.document.body.innerHTML = `<p style="font-family:Arial;padding:24px;color:#b91c1c">${escapeHtml(error.message || 'Failed to open print preview')}</p>`;
    }
}

function csvEscape(value) {
    const text = String(value ?? '');
    if (text.includes(',') || text.includes('"') || text.includes('\n')) {
        return '"' + text.replace(/"/g, '""') + '"';
    }
    return text;
}

async function exportToExcel() {
    await ensureReferenceData();
    const res = await fetch(`${API_URL}/fire-hydrants`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const data = Array.isArray(json.data) ? json.data : [];

    const rows = [['No', 'Location', 'Inspector', 'Inspection Date', 'Reference No', 'Notes']];

    data.forEach((h, index) => {
        rows.push([
            index + 1,
            h.location ? h.location.name : (h.location_id || ''),
            h.inspector ? h.inspector.name : (h.inspector_id || ''),
            formatDateOnly(h.inspection_date),
            h.reference_no || '',
            (h.notes || '').replace(/\n/g, ' ')
        ]);
    });

    const csvContent = rows.map(row => row.map(csvEscape).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'fire_hydrant_inspections_' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

async function exportItem(id) {
    await ensureReferenceData();
    const res = await fetch(`${API_URL}/fire-hydrants/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const detail = json.data;

    const locName = detail.location ? detail.location.name : (detail.location_id || '-');
    const inspName = detail.inspector ? detail.inspector.name : (detail.inspector_id || '-');
    const items = Array.isArray(detail.items) ? detail.items : [];

    let tableRows = '';
    items.forEach((item, i) => {
        tableRows += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + escapeHtml(item.hydrant_number || '-') + '</td>' +
            '<td>' + escapeHtml(item.name || '-') + '</td>' +
            '<td>' + escapeHtml(item.location_detail || '-') + '</td>' +
            '<td class="center">' + (boolValue(item.hose_condition) ? '&#10003;' : 'X') + '</td>' +
            '<td class="center">' + (boolValue(item.nozzle_condition) ? '&#10003;' : 'X') + '</td>' +
            '<td class="center">' + (boolValue(item.coupling_condition) ? '&#10003;' : 'X') + '</td>' +
            '<td class="center">' + (boolValue(item.wrench_condition) ? '&#10003;' : 'X') + '</td>' +
            '<td class="center">' + (boolValue(item.valve_condition) ? '&#10003;' : 'X') + '</td>' +
            '<td class="center">' + (boolValue(item.coupling_extra_condition) ? '&#10003;' : 'X') + '</td>' +
            '<td>' + escapeHtml(item.remark || '-') + '</td>' +
        '</tr>';
    });

    const htmlContent = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <title>Fire Hydrant Inspection</title>
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
                th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; }
                th { background-color: #c0c0c0; font-weight: bold; text-align: center; }
                .header { margin-bottom: 12px; font-family: Arial, sans-serif; }
                .header h2 { margin: 0 0 8px 0; font-size: 16px; }
                .header p { margin: 4px 0; font-size: 11px; }
                .center { text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>FIRE HYDRANT MONTHLY INSPECTION</h2>
                <p><strong>Location:</strong> ${escapeHtml(locName)}</p>
                <p><strong>Date Inspected:</strong> ${formatDateOnly(detail.inspection_date)}</p>
                <p><strong>Inspector:</strong> ${escapeHtml(inspName)}</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Hydrant Number</th>
                        <th>Name</th>
                        <th>Location Detail</th>
                        <th>Hose</th>
                        <th>Nozzle</th>
                        <th>Coupling</th>
                        <th>Wrench</th>
                        <th>Valve</th>
                        <th>Extra Coupling</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'fire_hydrant_inspection_' + (detail.reference_no || id) + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
        .finally(() => { localStorage.clear(); window.location.href='/'; });
}

referenceDataPromise = loadReferenceData();

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('location_id')) {
    document.getElementById('location_id').value = urlParams.get('location_id');
    openForm({ generateReference: true });
}

loadHydrants();
</script>
@endsection

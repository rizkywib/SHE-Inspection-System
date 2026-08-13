@extends('layouts.app')

@section('title', 'Inspection')
@section('nav-inspections', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Inspection</h1>
                <p id="pageSubtitle" class="text-gray-600 mt-1">Daftar data inspection</p>
            </div>
            <button id="newInspectionButton" type="button" onclick="showInspectionForm()"
                class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
                <i class="fas fa-plus mr-2"></i>Inspection Baru
            </button>
        </div>

        <div id="messageBox" class="hidden mb-6 rounded-lg border px-4 py-3" role="alert"></div>

        <div id="filterBar" class="mb-4 flex flex-wrap items-center justify-between gap-4 bg-white rounded-xl shadow border border-gray-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Show</label>
                <select id="pageSize" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-500">entries</span>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Search:</label>
                <input id="searchFilter" type="text" placeholder="Search lokasi, tipe, keterangan, status..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
            </div>
        </div>

        <section id="inspectionList" class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal & Jam</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Inspection Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Gambar</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="inspectionTable" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Memuat data inspection...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="paginationControls" class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div id="paginationInfo" class="text-sm text-gray-600"></div>
                <div class="flex items-center gap-2">
                    <button id="prevPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                    <span id="pageNumbers" class="text-sm text-gray-700"></span>
                    <button id="nextPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                </div>
            </div>
        </section>

        <form id="inspectionForm" class="hidden max-w-4xl mx-auto space-y-6" enctype="multipart/form-data">
            <input id="incident_id" type="hidden">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5 md:p-6">
                <h2 id="formHeading" class="text-xl font-bold text-gray-900 mb-5">Inspection Baru</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="incident_date" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="far fa-calendar-alt text-blue-600 mr-2"></i>Tanggal
                        </label>
                        <input id="incident_date" name="incident_date" type="date" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="incident_time" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="far fa-clock text-blue-600 mr-2"></i>Jam
                        </label>
                        <input id="incident_time" name="incident_time" type="time" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label for="location_text" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-location-dot text-blue-600 mr-2"></i>Lokasi
                        </label>
                        <input id="location_text" name="location_text" type="text" maxlength="255" required
                            placeholder="Masukkan lokasi inspection"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="incident_type_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tags text-blue-600 mr-2"></i>Inspection Type
                        </label>
                        <select id="incident_type_id" name="incident_type_id" required disabled
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100">
                            <option value="">Memuat Inspection Type...</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="far fa-flag text-blue-600 mr-2"></i>Status
                        </label>
                        <select id="status" name="status" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="open">Open</option>
                            <option value="close">Close</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="far fa-file-lines text-blue-600 mr-2"></i>Keterangan
                        </label>
                        <textarea id="description" name="description" rows="5" maxlength="5000" required
                            placeholder="Masukkan keterangan inspection"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 resize-y focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5 md:p-6">
                <label for="image" class="block text-sm font-semibold text-gray-700 mb-3">Gambar Inspection</label>
                <p id="imageHelp" class="text-sm text-gray-500 mb-3">Gambar wajib dipilih.</p>
                <div id="uploadArea" class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-500 transition">
                    <img id="imagePreview" class="hidden mx-auto mb-4 max-h-72 rounded-lg object-contain" alt="Preview gambar inspection">
                    <div id="uploadPlaceholder">
                        <i class="fas fa-cloud-arrow-up text-4xl text-blue-500 mb-3"></i>
                        <p class="font-medium text-gray-700">Upload Gambar</p>
                        <p class="text-sm text-gray-500 mt-1">JPG, PNG, atau WebP, maksimal 5 MB</p>
                    </div>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                </div>
                <div id="selectedFile" class="hidden mt-3 text-sm text-gray-600"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button type="button" onclick="showInspectionList()"
                    class="w-full border border-gray-300 bg-white text-gray-700 px-6 py-3.5 rounded-lg font-semibold hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke List
                </button>
                <button id="submitButton" type="submit"
                    class="btn-primary w-full text-white px-6 py-3.5 rounded-lg shadow-md font-semibold disabled:opacity-60 disabled:cursor-not-allowed">
                    <i id="submitIcon" class="fas fa-floppy-disk mr-2"></i>
                    <span id="submitText">Simpan</span>
                </button>
            </div>
        </form>

        <div id="detailModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 p-4 items-center justify-center">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Detail Inspection</h2>
                    <button type="button" onclick="closeDetail()" class="text-gray-400 hover:text-gray-700" aria-label="Tutup detail">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="detailContent" class="p-6"></div>
                <div class="px-6 py-4 border-t border-gray-200 text-right">
                    <button type="button" onclick="closeDetail()" class="bg-gray-600 text-white px-5 py-2 rounded-lg hover:bg-gray-700">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
const token = localStorage.getItem('token');
const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
const form = document.getElementById('inspectionForm');
const listSection = document.getElementById('inspectionList');
const newInspectionButton = document.getElementById('newInspectionButton');
const typeSelect = document.getElementById('incident_type_id');
const imageInput = document.getElementById('image');
const imagePreview = document.getElementById('imagePreview');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const selectedFile = document.getElementById('selectedFile');
let inspectionsData = [];
let currentPage = 1;
let pageSize = 25;
let searchFilter = '';

if (!token) {
    window.location.href = '/';
} else {
    document.getElementById('userName').textContent = currentUser.name || 'User';
}

function setCurrentDateTime() {
    const now = new Date();
    const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    document.getElementById('incident_date').value = localDate.toISOString().slice(0, 10);
    document.getElementById('incident_time').value = localDate.toISOString().slice(11, 16);
}

function showInspectionForm() {
    form.reset();
    document.getElementById('incident_id').value = '';
    document.getElementById('formHeading').textContent = 'Inspection Baru';
    document.getElementById('submitText').textContent = 'Simpan';
    document.getElementById('imageHelp').textContent = 'Gambar wajib dipilih.';
    imageInput.required = true;
    resetImagePreview();
    listSection.classList.add('hidden');
    form.classList.remove('hidden');
    newInspectionButton.classList.add('hidden');
    document.getElementById('pageSubtitle').textContent = 'Input dan simpan laporan inspection';
    document.getElementById('messageBox').classList.add('hidden');
    setCurrentDateTime();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showInspectionList() {
    form.reset();
    resetImagePreview();
    form.classList.add('hidden');
    listSection.classList.remove('hidden');
    newInspectionButton.classList.remove('hidden');
    document.getElementById('pageSubtitle').textContent = 'Daftar data inspection';
    document.getElementById('messageBox').classList.add('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showMessage(message, type = 'error') {
    const box = document.getElementById('messageBox');
    box.textContent = message;
    box.className = type === 'success'
        ? 'mb-6 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3'
        : 'mb-6 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function apiError(payload, fallback) {
    if (payload.message) return payload.message;
    if (payload.errors) {
        const first = Object.values(payload.errors)[0];
        return Array.isArray(first) ? first[0] : String(first);
    }
    return fallback;
}

async function loadIncidentTypes() {
    try {
        const response = await fetch(`${API_URL}/incident-types`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(apiError(payload, 'Inspection Type gagal dimuat.'));

        const types = Array.isArray(payload.data)
            ? payload.data.filter(item => item.is_active !== false && item.is_active !== 0)
            : [];
        typeSelect.innerHTML = '<option value="">Pilih Inspection Type</option>' + types
            .map(item => `<option value="${item.id}">${escapeHtml(item.name)}</option>`)
            .join('');
        typeSelect.disabled = false;
        if (types.length === 0) showMessage('Data Inspection Type belum tersedia.');
    } catch (error) {
        typeSelect.innerHTML = '<option value="">Inspection Type gagal dimuat</option>';
        showMessage(error.message || 'Inspection Type gagal dimuat.');
    }
}

async function loadInspections() {
    const table = document.getElementById('inspectionTable');
    try {
        const response = await fetch(`${API_URL}/incidents`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(apiError(payload, 'Data inspection gagal dimuat.'));

        inspectionsData = Array.isArray(payload.data) ? payload.data : [];
        renderTable();
    } catch (error) {
        table.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-10 text-center text-red-600">
                    ${escapeHtml(error.message || 'Data inspection gagal dimuat.')}
                    <button type="button" onclick="loadInspections()" class="block mx-auto mt-3 text-blue-600 hover:underline">Coba Lagi</button>
                </td>
            </tr>`;
    }
}

function matchesFilters(item) {
    const search = searchFilter.toLowerCase().trim();
    if (!search) return true;
    return String(item.id).includes(search) ||
        (item.location_text || '').toLowerCase().includes(search) ||
        (item.incident_type?.name || '').toLowerCase().includes(search) ||
        (item.description || '').toLowerCase().includes(search) ||
        (item.status || '').toLowerCase().includes(search) ||
        (item.reference_no || '').toLowerCase().includes(search);
}

function renderTable() {
    const table = document.getElementById('inspectionTable');

    const filtered = inspectionsData.filter(matchesFilters);
    const totalItems = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * pageSize;
    const end = Math.min(start + pageSize, totalItems);
    const pageItems = filtered.slice(start, end);

    if (totalItems === 0) {
        table.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <i class="far fa-clipboard text-4xl text-gray-300 mb-3 block"></i>
                    Belum ada data inspection
                </td>
            </tr>`;
    } else {
        table.innerHTML = pageItems.map(item => {
            const image = Array.isArray(item.images) && item.images.length > 0
                ? mediaUrl(item.images[0].image_path)
                : null;
            return `
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                        ${escapeHtml(formatDate(item.incident_date))}<br>
                        <span class="text-xs text-gray-400">${escapeHtml(formatTime(item.incident_time))}</span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-700 min-w-40">${escapeHtml(item.location_text || '-')}</td>
                    <td class="px-5 py-4 text-sm text-gray-700 whitespace-nowrap">${escapeHtml(item.incident_type?.name || '-')}</td>
                    <td class="px-5 py-4 text-sm text-gray-600 min-w-56 max-w-xs">
                        <p class="line-clamp-3">${escapeHtml(item.description || '-')}</p>
                    </td>
                    <td class="px-5 py-4 text-sm">${statusBadge(item.status)}</td>
                    <td class="px-5 py-4">
                        ${image
                            ? `<a href="${image}" target="_blank" rel="noopener"><img src="${image}" alt="Gambar inspection" class="w-16 h-16 rounded-lg object-cover border border-gray-200"></a>`
                            : '<span class="text-sm text-gray-400">-</span>'}
                    </td>
                    <td class="px-5 py-4 text-sm whitespace-nowrap">
                        <button type="button" onclick="viewInspection(${Number(item.id)})" class="text-sky-600 hover:text-sky-800 mr-3" title="Lihat">
                            <i class="fas fa-eye mr-1"></i>Lihat
                        </button>
                        <button type="button" onclick="editInspection(${Number(item.id)})" class="text-blue-600 hover:text-blue-800 mr-3" title="Edit">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button type="button" onclick="deleteInspection(${Number(item.id)})" class="text-red-600 hover:text-red-800" title="Hapus">
                            <i class="fas fa-trash mr-1"></i>Hapus
                        </button>
                    </td>
                </tr>`;
        }).join('');
    }

    const info = document.getElementById('paginationInfo');
    if (totalItems === 0) {
        info.textContent = 'Showing 0 to 0 of 0 entries';
    } else {
        info.textContent = `Showing ${start + 1} to ${end} of ${totalItems} entries`;
    }

    document.getElementById('pageNumbers').textContent = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
}

async function getInspection(id) {
    const response = await fetch(`${API_URL}/incidents/${id}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(apiError(payload, 'Detail inspection gagal dimuat.'));
    return payload.data;
}

async function viewInspection(id) {
    try {
        const item = await getInspection(id);
        const image = Array.isArray(item.images) && item.images.length > 0
            ? mediaUrl(item.images[0].image_path)
            : null;
        document.getElementById('detailContent').innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                ${detailField('Referensi', item.reference_no)}
                ${detailField('Tanggal', formatDate(item.incident_date))}
                ${detailField('Jam', formatTime(item.incident_time))}
                ${detailField('Lokasi', item.location_text)}
                ${detailField('Inspection Type', item.incident_type?.name)}
                <div><p class="text-xs font-semibold uppercase text-gray-400 mb-1">Status</p>${statusBadge(item.status)}</div>
                <div class="sm:col-span-2">${detailField('Keterangan', item.description)}</div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase text-gray-400 mb-2">Gambar</p>
                    ${image
                        ? `<a href="${image}" target="_blank" rel="noopener"><img src="${image}" alt="Gambar inspection" class="max-h-80 rounded-lg border border-gray-200 object-contain"></a>`
                        : '<p class="text-gray-500">-</p>'}
                </div>
            </div>`;
        const modal = document.getElementById('detailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } catch (error) {
        showMessage(error.message || 'Detail inspection gagal dimuat.');
    }
}

function detailField(label, value) {
    return `<div>
        <p class="text-xs font-semibold uppercase text-gray-400 mb-1">${escapeHtml(label)}</p>
        <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(value || '-')}</p>
    </div>`;
}

function closeDetail() {
    const modal = document.getElementById('detailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function editInspection(id) {
    showInspectionForm();
    document.getElementById('formHeading').textContent = 'Edit Inspection';
    document.getElementById('pageSubtitle').textContent = 'Perbarui data inspection';
    document.getElementById('submitText').textContent = 'Update';
    document.getElementById('imageHelp').textContent = 'Kosongkan jika gambar tidak ingin diganti.';
    imageInput.required = false;

    try {
        const results = await Promise.all([getInspection(id), loadIncidentTypes()]);
        const item = results[0];
        document.getElementById('incident_id').value = item.id;
        document.getElementById('incident_date').value = String(item.incident_date || '').split('T')[0];
        document.getElementById('incident_time').value = formatTime(item.incident_time) === '-'
            ? ''
            : formatTime(item.incident_time);
        document.getElementById('location_text').value = item.location_text || '';
        typeSelect.value = String(item.incident_type_id || '');
        document.getElementById('status').value = item.status === 'closed' ? 'close' : 'open';
        document.getElementById('description').value = item.description || '';

        if (Array.isArray(item.images) && item.images.length > 0) {
            imagePreview.src = mediaUrl(item.images[0].image_path);
            imagePreview.classList.remove('hidden');
            uploadPlaceholder.classList.add('hidden');
            selectedFile.textContent = 'Gambar saat ini - pilih file baru untuk mengganti.';
            selectedFile.classList.remove('hidden');
        }
    } catch (error) {
        showInspectionList();
        showMessage(error.message || 'Data inspection gagal dimuat.');
    }
}

async function deleteInspection(id) {
    if (!window.confirm('Hapus data inspection ini? Data dan gambar akan dihapus permanen.')) return;

    try {
        const response = await fetch(`${API_URL}/incidents/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(apiError(payload, 'Inspection gagal dihapus.'));
        await loadInspections();
        showMessage(payload.message || 'Inspection berhasil dihapus.', 'success');
    } catch (error) {
        showMessage(error.message || 'Inspection gagal dihapus.');
    }
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value).split('T')[0];
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    }).format(date);
}

function formatTime(value) {
    if (!value) return '-';
    return String(value).slice(0, 5);
}

function statusBadge(status) {
    if (status === 'closed') {
        return '<span class="inline-flex px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">Close</span>';
    }
    if (status === 'investigating') {
        return '<span class="inline-flex px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">Investigating</span>';
    }
    return '<span class="inline-flex px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Open</span>';
}

function mediaUrl(path) {
    if (!path) return null;
    const value = String(path);
    if (/^https?:\/\//i.test(value)) return value;
    return '/' + value.replace(/^\/+/, '');
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value == null ? '' : String(value);
    return element.innerHTML;
}

imageInput.addEventListener('change', () => {
    const file = imageInput.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        imageInput.value = '';
        showMessage('Ukuran gambar maksimal 5 MB.');
        return;
    }
    imagePreview.src = URL.createObjectURL(file);
    imagePreview.classList.remove('hidden');
    uploadPlaceholder.classList.add('hidden');
    selectedFile.textContent = file.name;
    selectedFile.classList.remove('hidden');
});

function resetImagePreview() {
    imagePreview.src = '';
    imagePreview.classList.add('hidden');
    uploadPlaceholder.classList.remove('hidden');
    selectedFile.textContent = '';
    selectedFile.classList.add('hidden');
}

form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;

    const submitButton = document.getElementById('submitButton');
    const submitIcon = document.getElementById('submitIcon');
    const submitText = document.getElementById('submitText');
    const incidentId = document.getElementById('incident_id').value;
    submitButton.disabled = true;
    submitIcon.className = 'fas fa-spinner fa-spin mr-2';
    submitText.textContent = incidentId ? 'Memperbarui...' : 'Menyimpan...';

    try {
        const formData = new FormData(form);
        if (incidentId) formData.append('_method', 'PUT');
        const response = await fetch(
            incidentId ? `${API_URL}/incidents/${incidentId}` : `${API_URL}/incidents`,
            {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            body: formData
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(apiError(payload, 'Inspection gagal disimpan.'));

        window.location.href = incidentId
            ? '/dashboard/inspections?updated=1'
            : '/dashboard/inspections?saved=1';
        return;
    } catch (error) {
        showMessage(error.message || 'Inspection gagal disimpan.');
    } finally {
        submitButton.disabled = false;
        submitIcon.className = 'fas fa-floppy-disk mr-2';
        submitText.textContent = incidentId ? 'Update' : 'Simpan';
    }
});

function logout() {
    fetch(`${API_URL}/auth/logout`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    }).finally(() => {
        localStorage.clear();
        window.location.href = '/';
    });
}

setCurrentDateTime();
loadIncidentTypes();
loadInspections();

document.getElementById('pageSize').addEventListener('change', function() {
    pageSize = parseInt(this.value);
    currentPage = 1;
    renderTable();
});

document.getElementById('searchFilter').addEventListener('input', function() {
    searchFilter = this.value;
    currentPage = 1;
    renderTable();
});

document.getElementById('prevPage').addEventListener('click', function() {
    if (currentPage > 1) {
        currentPage--;
        renderTable();
    }
});

document.getElementById('nextPage').addEventListener('click', function() {
    const totalItems = inspectionsData.filter(matchesFilters).length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage < totalPages) {
        currentPage++;
        renderTable();
    }
});

const query = new URLSearchParams(window.location.search);
if (query.get('saved') === '1') {
    showMessage('Inspection berhasil disimpan.', 'success');
    window.history.replaceState({}, '', '/dashboard/inspections');
} else if (query.get('updated') === '1') {
    showMessage('Inspection berhasil diperbarui.', 'success');
    window.history.replaceState({}, '', '/dashboard/inspections');
}
</script>
@endsection

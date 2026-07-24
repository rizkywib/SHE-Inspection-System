@extends('layouts.app')

@section('title', 'Permit Matrix')
@section('nav-permit-matrix', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Permit Matrix</h1>
            <p class="text-gray-600 mt-1">Hasil inspeksi Safe Work Permit</p>
        </div>
        <a id="createButton" href="/dashboard/permit-matrix/create" class="hidden btn-primary text-white px-5 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>Tambah
        </a>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3" role="alert"></div>

    <form id="filterForm" class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <div class="xl:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input id="search" name="search" placeholder="No. Permit, Inspector, Section/Equipment, Kontraktor" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Awal</label>
                <input id="date_from" name="date_from" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input id="date_to" name="date_to" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="inspector_id" class="block text-sm font-medium text-gray-700 mb-1">Inspector</label>
                <select id="inspector_id" name="inspector_id" class="w-full border border-gray-300 rounded-lg px-3 py-2"><option value="">Semua</option></select>
            </div>
            <div>
                <label for="permit_type_id" class="block text-sm font-medium text-gray-700 mb-1">Type Permit</label>
                <select id="permit_type_id" name="permit_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2"><option value="">Semua</option></select>
            </div>
            <div>
                <label for="supervision_area_id" class="block text-sm font-medium text-gray-700 mb-1">Area Pengawasan</label>
                <select id="supervision_area_id" name="supervision_area_id" class="w-full border border-gray-300 rounded-lg px-3 py-2"><option value="">Semua</option></select>
            </div>
            <div>
                <label for="main_area_id" class="block text-sm font-medium text-gray-700 mb-1">Main Area</label>
                <select id="main_area_id" name="main_area_id" class="w-full border border-gray-300 rounded-lg px-3 py-2"><option value="">Semua</option></select>
            </div>
            <div>
                <label for="sub_area_id" class="block text-sm font-medium text-gray-700 mb-1">Sub Area</label>
                <select id="sub_area_id" name="sub_area_id" class="w-full border border-gray-300 rounded-lg px-3 py-2"><option value="">Semua</option></select>
            </div>
            <div>
                <label for="finding_status" class="block text-sm font-medium text-gray-700 mb-1">Status Temuan</label>
                <select id="finding_status" name="finding_status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Semua</option>
                    <option value="with">Ada Temuan</option>
                    <option value="without">Tidak Ada Temuan</option>
                </select>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"><i class="fas fa-search mr-2"></i>Terapkan</button>
            <a href="/dashboard/permit-matrix" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach (['No.', 'Tanggal Permit', 'No. Permit', 'Nama Inspector', 'Type Permit', 'Area Pengawasan', 'Main Area', 'Sub Area', 'Nama Kontraktor', 'Status Temuan', 'Aksi'] as $heading)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-gray-200">
                    <tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-t px-5 py-4"></div>
    </div>
</div>

<script>
const token = localStorage.getItem('token');
let currentUser = JSON.parse(localStorage.getItem('user') || '{}');
let permissions = Array.isArray(currentUser.permissions) ? currentUser.permissions : [];
const can = permission => currentUser.role === 'super_admin' || permissions.includes(permission);
let masterData = {};

if (!token) window.location.href = '/';

const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const authHeaders = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
const params = new URLSearchParams(window.location.search);

function message(text, type = 'success') {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = `mb-5 rounded-lg border px-4 py-3 ${type === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800'}`;
}

function fillSelect(id, rows, label = row => row.name) {
    const select = document.getElementById(id);
    rows.forEach(row => select.insertAdjacentHTML('beforeend', `<option value="${row.id}">${escapeHtml(label(row))}</option>`));
}

function applyQueryToFilters() {
    [...document.getElementById('filterForm').elements].forEach(element => {
        if (element.name && params.has(element.name)) element.value = params.get(element.name);
    });
}

async function refreshCurrentUser() {
    const response = await fetch('/api/auth/me', {headers: authHeaders});
    if (response.status === 401) {
        localStorage.clear();
        window.location.href = '/';
        return false;
    }
    if (!response.ok) return false;

    currentUser = await response.json();
    permissions = Array.isArray(currentUser.permissions) ? currentUser.permissions : [];
    localStorage.setItem('user', JSON.stringify(currentUser));
    return true;
}

async function init() {
    if (!await refreshCurrentUser()) {
        return message('Profil dan permission pengguna gagal dimuat.', 'error');
    }
    if (can('safe-work-permit-inspection.create')) {
        document.getElementById('createButton').classList.remove('hidden');
    }

    const flash = sessionStorage.getItem('permitMatrixMessage');
    if (flash) {
        message(flash);
        sessionStorage.removeItem('permitMatrixMessage');
    }

    const masterResponse = await fetch('/api/safe-work-permit-inspections/master-data', {headers: authHeaders});
    if (masterResponse.status === 401) return window.location.href = '/';
    if (masterResponse.status === 403) return message('Anda tidak memiliki izin melihat Permit Matrix.', 'error');
    masterData = await masterResponse.json();
    fillSelect('inspector_id', masterData.inspectors);
    fillSelect('permit_type_id', masterData.permit_types);
    fillSelect('supervision_area_id', masterData.supervision_areas, row => row.code);
    fillSelect('main_area_id', masterData.main_areas);
    fillSelect('sub_area_id', masterData.sub_areas);
    applyQueryToFilters();
    await loadData(params.get('page') || 1);
}

async function loadData(page = 1) {
    const query = new URLSearchParams(params);
    query.set('page', page);
    const response = await fetch(`/api/safe-work-permit-inspections?${query}`, {headers: authHeaders});
    if (!response.ok) return message('Data Permit Matrix gagal dimuat.', 'error');
    renderTable(await response.json());
}

function renderTable(result) {
    const body = document.getElementById('tableBody');
    if (!result.data.length) {
        body.innerHTML = '<tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">Data tidak ditemukan.</td></tr>';
    } else {
        body.innerHTML = result.data.map((row, index) => {
            const actions = [
                can('safe-work-permit-inspection.view') ? `<a href="/dashboard/permit-matrix/${row.id}" class="text-blue-600 hover:text-blue-800" title="Detail"><i class="fas fa-eye"></i></a>` : '',
                can('safe-work-permit-inspection.update') ? `<a href="/dashboard/permit-matrix/${row.id}/edit" class="text-amber-600 hover:text-amber-800" title="Edit"><i class="fas fa-edit"></i></a>` : '',
                can('safe-work-permit-inspection.delete') ? `<button onclick="removeInspection(${row.id})" class="text-red-600 hover:text-red-800" title="Hapus"><i class="fas fa-trash"></i></button>` : '',
            ].join('');
            const withFinding = row.finding_status === 'Ada Temuan';
            return `<tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm">${result.from + index}</td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">${escapeHtml(row.permit_date)}</td>
                <td class="px-4 py-3 text-sm font-medium">${escapeHtml(row.permit_number)}</td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.inspector?.name)}</td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.permit_type?.name)}</td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.supervision_area?.code)}</td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.main_area?.name)}</td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.sub_area?.name)}</td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.contractor_name)}</td>
                <td class="px-4 py-3 text-sm"><span class="px-2 py-1 rounded-full text-xs font-semibold ${withFinding ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">${row.finding_status}</span></td>
                <td class="px-4 py-3"><div class="flex gap-3">${actions}</div></td>
            </tr>`;
        }).join('');
    }

    document.getElementById('pagination').innerHTML = `
        <p class="text-sm text-gray-600">Menampilkan ${result.from || 0}–${result.to || 0} dari ${result.total} data</p>
        <div class="flex gap-2">
            <button ${result.current_page <= 1 ? 'disabled' : ''} onclick="goToPage(${result.current_page - 1})" class="px-3 py-2 border rounded-lg disabled:opacity-40">Sebelumnya</button>
            <span class="px-3 py-2 text-sm">Halaman ${result.current_page} / ${result.last_page}</span>
            <button ${result.current_page >= result.last_page ? 'disabled' : ''} onclick="goToPage(${result.current_page + 1})" class="px-3 py-2 border rounded-lg disabled:opacity-40">Berikutnya</button>
        </div>`;
}

function goToPage(page) {
    params.set('page', page);
    window.location.search = params.toString();
}

async function removeInspection(id) {
    if (!confirm('Hapus data Permit Matrix ini? Tindakan ini tidak dapat dibatalkan.')) return;
    const response = await fetch(`/api/safe-work-permit-inspections/${id}`, {method: 'DELETE', headers: authHeaders});
    const data = await response.json();
    if (!response.ok) return message(data.message || 'Data gagal dihapus.', 'error');
    message(data.message);
    await loadData(params.get('page') || 1);
}

document.getElementById('filterForm').addEventListener('submit', event => {
    event.preventDefault();
    const query = new URLSearchParams(new FormData(event.currentTarget));
    [...query.entries()].forEach(([key, value]) => { if (!value) query.delete(key); });
    window.location.search = query.toString();
});

init().catch(() => message('Terjadi kesalahan saat memuat halaman.', 'error'));
</script>
@endsection

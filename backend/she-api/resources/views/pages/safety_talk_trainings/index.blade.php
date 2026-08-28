@extends('layouts.app')

@section('title', 'Safety Talk / Training On Site')
@section('nav-safety-talk-trainings', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Data Safety Talk / Training On Site</h1>
            <p class="text-gray-600 mt-1">Kelola kegiatan penyampaian Safety Talk di lapangan</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button id="exportButton" onclick="exportExcel()" class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg shadow-md">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </button>
            <a id="createButton" href="/dashboard/safety-talk-trainings/create" class="hidden btn-primary text-white px-5 py-3 rounded-lg shadow-md">
                <i class="fas fa-plus mr-2"></i>Tambah Data
            </a>
        </div>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3" role="alert"></div>

    <form id="filterForm" class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="xl:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                <input id="search" name="search" placeholder="Nama pembicara atau topik" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input id="date_from" name="date_from" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                <input id="date_to" name="date_to" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="implementation_area" class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                <select id="implementation_area" name="implementation_area" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Semua Area</option>
                    @foreach (range(1, 6) as $area)<option value="{{ $area }}">Area {{ $area }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="speaker_id" class="block text-sm font-medium text-gray-700 mb-1">Pembicara</label>
                <select id="speaker_id" name="speaker_id" class="w-full border border-gray-300 rounded-lg px-3 py-2"><option value="">Semua Pembicara</option></select>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"><i class="fas fa-search mr-2"></i>Terapkan</button>
            <a href="/dashboard/safety-talk-trainings" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach (['No.', 'Tanggal', 'Pembicara', 'Topik / Materi', 'Area', 'Ecogreen', 'Outsourcing', 'Contractor', 'Total', 'Durasi', 'Foto', 'Dibuat Oleh', 'Aksi'] as $heading)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-gray-200">
                    <tr><td colspan="13" class="px-4 py-8 text-center text-gray-500">Memuat data...</td></tr>
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
const isAdmin = () => currentUser.role === 'super_admin' || currentUser.role === 'admin';
const authHeaders = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
const params = new URLSearchParams(window.location.search);
if (!token) window.location.href = '/';

const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const formatDate = value => value ? new Date(`${value}T00:00:00`).toLocaleDateString('id-ID') : '-';

function message(text, type = 'success') {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = `mb-5 rounded-lg border px-4 py-3 ${type === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800'}`;
}

async function refreshUser() {
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
    if (!await refreshUser()) return message('Profil pengguna gagal dimuat.', 'error');
    if (!can('safety-talk-training.view')) return message('Anda tidak memiliki izin melihat data Safety Talk.', 'error');
    if (can('safety-talk-training.create')) document.getElementById('createButton').classList.remove('hidden');

    const flash = sessionStorage.getItem('safetyTalkMessage');
    if (flash) {
        message(flash);
        sessionStorage.removeItem('safetyTalkMessage');
    }

    const masterResponse = await fetch('/api/safety-talk-trainings/master-data', {headers: authHeaders});
    if (!masterResponse.ok) return message('Master pembicara gagal dimuat.', 'error');
    const master = await masterResponse.json();
    const speakerSelect = document.getElementById('speaker_id');
    master.speakers.forEach(row => speakerSelect.insertAdjacentHTML('beforeend', `<option value="${row.id}">${escapeHtml(row.name)}</option>`));
    [...document.getElementById('filterForm').elements].forEach(element => {
        if (element.name && params.has(element.name)) element.value = params.get(element.name);
    });
    await loadData(params.get('page') || 1);
}

async function loadData(page = 1) {
    const query = new URLSearchParams(params);
    query.set('page', page);
    const response = await fetch(`/api/safety-talk-trainings?${query}`, {headers: authHeaders});
    if (!response.ok) return message('Data Safety Talk gagal dimuat.', 'error');
    renderTable(await response.json());
}

function renderTable(result) {
    const body = document.getElementById('tableBody');
    if (!result.data.length) {
        body.innerHTML = '<tr><td colspan="13" class="px-4 py-10 text-center text-gray-500"><i class="fas fa-inbox text-3xl mb-3 block"></i>Belum ada data Safety Talk.</td></tr>';
    } else {
        body.innerHTML = result.data.map((row, index) => {
            const actions = [
                `<a href="/dashboard/safety-talk-trainings/${row.id}" class="text-blue-600 hover:text-blue-800" title="Lihat"><i class="fas fa-eye"></i></a>`,
                isAdmin() ? `<a href="/dashboard/safety-talk-trainings/${row.id}/edit" class="text-amber-600 hover:text-amber-800" title="Edit"><i class="fas fa-edit"></i></a>` : '',
                isAdmin() ? `<button onclick="removeTraining(${row.id})" class="text-red-600 hover:text-red-800" title="Hapus"><i class="fas fa-trash"></i></button>` : '',
            ].join('');
            const topic = row.topic.length > 80 ? `${row.topic.slice(0, 80)}…` : row.topic;
            return `<tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm">${result.from + index}</td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">${formatDate(row.implementation_date)}</td>
                <td class="px-4 py-3 text-sm font-medium">${escapeHtml(row.speaker?.name || row.legacy_speaker?.name)}</td>
                <td class="px-4 py-3 text-sm min-w-[240px]">${escapeHtml(topic)}</td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">Area ${row.implementation_area}</td>
                <td class="px-4 py-3 text-sm text-center">${row.ecogreen_participants}</td>
                <td class="px-4 py-3 text-sm text-center">${row.outsourcing_participants}</td>
                <td class="px-4 py-3 text-sm text-center">${row.contractor_participants}</td>
                <td class="px-4 py-3 text-sm text-center font-semibold">${row.total_participants}</td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">${row.duration_minutes} menit</td>
                <td class="px-4 py-3"><a href="${escapeHtml(row.activity_photo_url)}" target="_blank"><img src="${escapeHtml(row.activity_photo_url)}" alt="Foto kegiatan" class="h-12 w-16 object-cover rounded border"></a></td>
                <td class="px-4 py-3 text-sm">${escapeHtml(row.creator?.name)}</td>
                <td class="px-4 py-3"><div class="flex gap-3">${actions}</div></td>
            </tr>`;
        }).join('');
    }
    document.getElementById('pagination').innerHTML = `
        <p class="text-sm text-gray-600">Menampilkan ${result.from || 0}–${result.to || 0} dari ${result.total} data</p>
        <div class="flex gap-2 items-center">
            <button ${result.current_page <= 1 ? 'disabled' : ''} onclick="goToPage(${result.current_page - 1})" class="px-3 py-2 border rounded-lg disabled:opacity-40">Sebelumnya</button>
            <span class="text-sm">Halaman ${result.current_page} / ${result.last_page}</span>
            <button ${result.current_page >= result.last_page ? 'disabled' : ''} onclick="goToPage(${result.current_page + 1})" class="px-3 py-2 border rounded-lg disabled:opacity-40">Berikutnya</button>
        </div>`;
}

function goToPage(page) {
    params.set('page', page);
    window.location.search = params.toString();
}

async function exportExcel() {
    const query = new URLSearchParams(params);
    query.delete('page');
    let page = 1;
    const all = [];
    while (true) {
        query.set('page', page);
        query.set('per_page', '100');
        const response = await fetch(`/api/safety-talk-trainings?${query}`, {headers: authHeaders});
        if (!response.ok) return message('Data gagal diambil untuk export.', 'error');
        const result = await response.json();
        all.push(...result.data);
        if (page >= result.last_page) break;
        page += 1;
    }

    const rows = all.map((row, index) => `<tr>
        <td>${index + 1}</td>
        <td>${formatDate(row.implementation_date)}</td>
        <td>${escapeHtml(row.speaker?.name || row.legacy_speaker?.name)}</td>
        <td>${escapeHtml(row.topic)}</td>
        <td>Area ${row.implementation_area}</td>
        <td>${row.ecogreen_participants}</td>
        <td>${row.outsourcing_participants}</td>
        <td>${row.contractor_participants}</td>
        <td>${row.total_participants}</td>
        <td>${row.duration_minutes} menit</td>
        <td>${escapeHtml(row.creator?.name)}</td>
    </tr>`).join('');

    const htmlContent = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="utf-8">
        <title>Safety Talk / Training On Site</title>
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
            th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; white-space: nowrap; }
            th { background-color: #c0c0c0; font-weight: bold; text-align: center; }
            .header { margin-bottom: 12px; font-family: Arial, sans-serif; }
            .header h2 { margin: 0 0 8px 0; font-size: 16px; }
        </style>
    </head>
    <body>
        <div class="header"><h2>DATA SAFETY TALK / TRAINING ON SITE</h2></div>
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Tanggal</th><th>Pembicara</th><th>Topik / Materi</th><th>Area</th><th>Peserta Ecogreen</th><th>Peserta Outsourcing</th><th>Peserta Contractor</th><th>Total Peserta</th><th>Durasi</th><th>Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </body>
    </html>`;

    const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'safety_talk_' + new Date().toISOString().slice(0, 10) + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

async function removeTraining(id) {
    if (!confirm('Hapus data Safety Talk ini? Foto kegiatan juga akan dihapus.')) return;
    const response = await fetch(`/api/safety-talk-trainings/${id}`, {method: 'DELETE', headers: authHeaders});
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

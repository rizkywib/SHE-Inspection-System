@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Activity Log</h1>
        <p class="text-gray-600 mt-1">Riwayat aktivitas pengguna pada sistem (khusus admin)</p>
    </div>

    <div id="messageBox" class="hidden mb-6 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3" role="alert"></div>

    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input id="searchFilter" type="text" placeholder="Nama, modul, endpoint, IP..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modul</label>
                <select id="moduleFilter" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Modul</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                <select id="actionFilter" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Aksi</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input id="dateFromFilter" type="date"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input id="dateToFilter" type="date"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tampilkan</label>
                    <select id="pageSize" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <button type="button" onclick="resetFilters()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modul</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endpoint</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody id="logTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div id="paginationInfo" class="text-sm text-gray-600"></div>
            <div class="flex items-center gap-2">
                <button id="prevPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <span id="pageNumbers" class="text-sm text-gray-700"></span>
                <button id="nextPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let currentPage = 1;
let pageSize = 25;
let lastPage = 1;

if (!token) window.location.href = '/';
if (!['super_admin', 'admin'].includes(user.role)) window.location.href = '/dashboard';

document.getElementById('userName').textContent = user.name || 'User';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showError(text) {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.classList.remove('hidden');
}

const ACTION_STYLES = {
    create: 'bg-green-100 text-green-800',
    item_create: 'bg-green-100 text-green-800',
    login: 'bg-blue-100 text-blue-800',
    logout: 'bg-gray-200 text-gray-800',
    update: 'bg-yellow-100 text-yellow-800',
    item_update: 'bg-yellow-100 text-yellow-800',
    checkin: 'bg-indigo-100 text-indigo-800',
    sign: 'bg-purple-100 text-purple-800',
    scan: 'bg-cyan-100 text-cyan-800',
    generate: 'bg-cyan-100 text-cyan-800',
    image_upload: 'bg-teal-100 text-teal-800',
    investigate: 'bg-orange-100 text-orange-800',
    delete: 'bg-red-100 text-red-800',
    item_delete: 'bg-red-100 text-red-800',
};

function actionBadge(action) {
    const safe = escapeHtml(action || '-');
    const style = ACTION_STYLES[action] || 'bg-gray-100 text-gray-800';
    return `<span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${style}">${safe}</span>`;
}

function statusBadge(status) {
    if (status === null || status === undefined) return '-';
    const ok = Number(status) >= 200 && Number(status) < 400;
    return `<span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${ok ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${escapeHtml(status)}</span>`;
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return escapeHtml(value);
    return date.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function buildQuery() {
    const params = new URLSearchParams();
    params.set('page', currentPage);
    params.set('per_page', pageSize);

    const search = document.getElementById('searchFilter').value.trim();
    const module = document.getElementById('moduleFilter').value;
    const action = document.getElementById('actionFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;

    if (search) params.set('search', search);
    if (module) params.set('module', module);
    if (action) params.set('action', action);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    return params.toString();
}

async function loadLogs() {
    const tbody = document.getElementById('logTable');
    try {
        const res = await fetch(`${API_URL}/activity-logs?${buildQuery()}`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });

        if (res.status === 401) { localStorage.clear(); window.location.href = '/'; return; }
        if (res.status === 403) { window.location.href = '/dashboard'; return; }

        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Gagal memuat activity log.');

        renderOptions(json.filters || {});
        renderTable(Array.isArray(json.data) ? json.data : [], json.meta || {});
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-red-600">Gagal memuat data.</td></tr>';
        showError(error.message || 'Gagal memuat activity log.');
    }
}

function renderOptions(filters) {
    const moduleSelect = document.getElementById('moduleFilter');
    const actionSelect = document.getElementById('actionFilter');
    const selectedModule = moduleSelect.value;
    const selectedAction = actionSelect.value;

    moduleSelect.innerHTML = '<option value="">Semua Modul</option>' +
        (filters.modules || []).map(m => `<option value="${escapeHtml(m)}">${escapeHtml(m)}</option>`).join('');
    actionSelect.innerHTML = '<option value="">Semua Aksi</option>' +
        (filters.actions || []).map(a => `<option value="${escapeHtml(a)}">${escapeHtml(a)}</option>`).join('');

    moduleSelect.value = selectedModule;
    actionSelect.value = selectedAction;
}

function renderTable(logs, meta) {
    const tbody = document.getElementById('logTable');

    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Belum ada aktivitas.</td></tr>';
    } else {
        tbody.innerHTML = logs.map(log => `
            <tr class="hover:bg-gray-50 transition align-top">
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">${formatDate(log.created_at)}</td>
                <td class="px-6 py-4 text-sm">
                    <div class="font-medium text-gray-900">${escapeHtml(log.user_name || 'Guest')}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(String(log.user_role || '').replaceAll('_', ' '))}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">${escapeHtml(log.module || '-')}</td>
                <td class="px-6 py-4 text-sm">${actionBadge(log.action)}</td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <span class="font-mono text-xs">${escapeHtml(log.method || '')} /${escapeHtml(log.path || '')}</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">${escapeHtml(log.ip_address || '-')}</td>
                <td class="px-6 py-4 text-sm">${statusBadge(log.status_code)}</td>
            </tr>
        `).join('');
    }

    lastPage = meta.last_page || 1;
    currentPage = meta.current_page || 1;
    const total = meta.total || 0;
    const perPage = meta.per_page || pageSize;

    const info = document.getElementById('paginationInfo');
    if (total === 0) {
        info.textContent = 'Showing 0 to 0 of 0 entries';
    } else {
        const start = (currentPage - 1) * perPage + 1;
        const end = Math.min(start + logs.length - 1, total);
        info.textContent = `Showing ${start} to ${end} of ${total} entries`;
    }

    document.getElementById('pageNumbers').textContent = `Page ${currentPage} of ${lastPage}`;
    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= lastPage;
}

function resetFilters() {
    document.getElementById('searchFilter').value = '';
    document.getElementById('moduleFilter').value = '';
    document.getElementById('actionFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';
    currentPage = 1;
    loadLogs();
}

document.getElementById('pageSize').addEventListener('change', function() {
    pageSize = parseInt(this.value, 10);
    currentPage = 1;
    loadLogs();
});

let searchTimer = null;
document.getElementById('searchFilter').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentPage = 1; loadLogs(); }, 300);
});

['moduleFilter', 'actionFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        currentPage = 1;
        loadLogs();
    });
});

document.getElementById('prevPage').addEventListener('click', function() {
    if (currentPage > 1) { currentPage--; loadLogs(); }
});

document.getElementById('nextPage').addEventListener('click', function() {
    if (currentPage < lastPage) { currentPage++; loadLogs(); }
});

loadLogs();
</script>
@endsection

@extends('layouts.app')

@section('title', 'Job Performance')
@section('nav-permit-job-performances', 'active')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Job Performance</h1>
        <p class="text-gray-600 mt-1">Kelola daftar Job Performance untuk dropdown Permit Matrix</p>
    </div>

    <div id="messageBox" class="hidden mb-6 rounded-lg border px-4 py-3" role="alert"></div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Job Performance
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Job Performance</h2>
        <form id="jpForm" class="space-y-6">
            <input type="hidden" id="jp_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input id="name" required maxlength="255" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Contoh: Pemeriksaan pekerjaan hot work">
                    <p class="text-xs text-gray-500 mt-1">Nama ini yang tampil di dropdown Job Performance Permit Matrix.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="is_active" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button id="saveButton" type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <i id="saveIcon" class="fas fa-save mr-2"></i><span id="saveText">Save</span>
                </button>
            </div>
        </form>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Show</label>
            <select id="pageSize" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-sm text-gray-500">entries</span>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Search:</label>
            <input id="searchFilter" type="text" placeholder="Search job performances..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="jpTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
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
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let items = [];
let currentPage = 1;
let pageSize = 25;
let searchFilter = '';

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
}

function showMessage(text, type = 'success') {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = type === 'success'
        ? 'mb-6 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3'
        : 'mb-6 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3';
}

function apiError(payload, fallback) {
    if (payload.message) return payload.message;
    if (payload.errors) {
        const first = Object.values(payload.errors)[0];
        return Array.isArray(first) ? first[0] : String(first);
    }
    return fallback;
}

function statusBadge(status) {
    const active = Number(status) === 1;
    const classes = active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
    return `<span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${classes}">${active ? 'Active' : 'Inactive'}</span>`;
}

async function loadItems() {
    try {
        const res = await fetch(`${API_URL}/permit-job-performances`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });

        if (res.status === 401) {
            localStorage.clear();
            window.location.href = '/';
            return;
        }

        if (!res.ok) {
            const error = await res.json().catch(() => ({}));
            const errorText = apiError(error, `Error loading data: ${res.status}`);
            console.error('API Error:', res.status, error);
            document.getElementById('jpTable').innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">${escapeHtml(errorText)}</td></tr>`;
            showMessage(errorText, 'error');
            return;
        }

        const json = await res.json();
        items = Array.isArray(json.data) ? json.data : [];
        renderTable();
    } catch (error) {
        console.error('Load error:', error);
        document.getElementById('jpTable').innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">Error: ${error.message}</td></tr>`;
        showMessage(error.message || 'Data Job Performance gagal dimuat.', 'error');
    }
}

function renderTable() {
    const tbody = document.getElementById('jpTable');
    const search = searchFilter.toLowerCase().trim();

    let filtered = items;
    if (search) {
        filtered = items.filter(item => {
            return String(item.id).includes(search) ||
                (item.name || '').toLowerCase().includes(search);
        });
    }

    const totalItems = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * pageSize;
    const end = Math.min(start + pageSize, totalItems);
    const pageItems = filtered.slice(start, end);

    if (totalItems === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No job performance found</td></tr>';
    } else {
        tbody.innerHTML = pageItems.map((item, index) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-900">${start + index + 1}</td>
                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(item.name)}</td>
                <td class="px-6 py-4 text-sm">${statusBadge(item.is_active)}</td>
                <td class="px-6 py-4 text-sm">
                    <button onclick="editItem(${item.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button onclick="deleteItem(${item.id})" class="text-red-600 hover:text-red-800 font-medium">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </td>
            </tr>
        `).join('');
    }

    const info = document.getElementById('paginationInfo');
    if (totalItems === 0) {
        info.textContent = 'Showing 0 to 0 of 0 entries';
    } else {
        info.textContent = `Showing ${start + 1} to ${end} of ${totalItems} entries`;
    }

    const pageNumbers = document.getElementById('pageNumbers');
    pageNumbers.textContent = `Page ${currentPage} of ${totalPages}`;

    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
}

function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Job Performance';
    document.getElementById('jpForm').reset();
    document.getElementById('jp_id').value = '';
    document.getElementById('is_active').value = '1';
}

function closeForm() {
    document.getElementById('formCard').classList.add('hidden');
}

function editItem(id) {
    const item = items.find(row => row.id === id);
    if (!item) return;

    openForm();
    document.getElementById('formTitle').textContent = 'Edit Job Performance';
    document.getElementById('jp_id').value = item.id;
    document.getElementById('name').value = item.name || '';
    document.getElementById('is_active').value = Number(item.is_active) === 1 ? '1' : '0';
}

document.getElementById('jpForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('jp_id').value;
    const saveButton = document.getElementById('saveButton');
    const saveIcon = document.getElementById('saveIcon');
    const saveText = document.getElementById('saveText');
    const payload = {
        name: document.getElementById('name').value.trim(),
        is_active: Number(document.getElementById('is_active').value),
    };
    const url = id ? `${API_URL}/permit-job-performances/${id}` : `${API_URL}/permit-job-performances`;
    const method = id ? 'PUT' : 'POST';

    saveButton.disabled = true;
    saveIcon.className = 'fas fa-spinner fa-spin mr-2';
    saveText.textContent = id ? 'Updating...' : 'Saving...';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!res.ok) {
            const error = await res.json().catch(() => ({}));
            const errorMsg = apiError(error, 'Save failed.');
            showMessage(errorMsg, 'error');
            console.error('Save error:', error);
            return;
        }

        const result = await res.json();
        closeForm();
        await loadItems();
        showMessage(result.message || (id
            ? 'Job Performance updated successfully.'
            : 'Job Performance saved successfully.'));
    } catch (error) {
        showMessage(error.message || 'Save failed.', 'error');
        console.error('Save error:', error);
    } finally {
        saveButton.disabled = false;
        saveIcon.className = 'fas fa-save mr-2';
        saveText.textContent = 'Save';
    }
});

async function deleteItem(id) {
    if (!confirm('Delete this job performance?')) return;
    try {
        const res = await fetch(`${API_URL}/permit-job-performances/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        const result = await res.json().catch(() => ({}));
        if (!res.ok) {
            showMessage(apiError(result, 'Delete failed.'), 'error');
            return;
        }
        await loadItems();
        showMessage(result.message || 'Job Performance deleted successfully.');
    } catch (error) {
        showMessage(error.message || 'Delete failed.', 'error');
        console.error('Delete error:', error);
    }
}

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
    const totalItems = items.filter(item => {
        const search = searchFilter.toLowerCase().trim();
        if (!search) return true;
        return String(item.id).includes(search) ||
            (item.name || '').toLowerCase().includes(search);
    }).length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage < totalPages) {
        currentPage++;
        renderTable();
    }
});

loadItems();
</script>
@endsection
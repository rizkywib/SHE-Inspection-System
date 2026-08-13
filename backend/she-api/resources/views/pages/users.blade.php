@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Users</h1>
        <p class="text-gray-600 mt-1">Manage system users and permissions</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-user-plus mr-2"></i>New User
        </button>
    </div>

    <div id="messageBox" class="hidden mb-6 rounded-lg border px-4 py-3" role="alert"></div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
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
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Role:</label>
            <select id="roleFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Roles</option>
                <option value="inspector">Inspector</option>
                <option value="admin">Admin</option>
                <option value="supervisor">Supervisor</option>
                <option value="super_admin">Super Admin</option>
                <option value="she_section_head">SHE Section Head</option>
                <option value="user_dept_head">User Dept Head</option>
                <option value="viewer">Viewer</option>
            </select>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Search:</label>
            <input id="searchFilter" type="text" placeholder="Search name, username, phone..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
        </div>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create User</h2>
        <form id="userForm" class="space-y-4">
            <input type="hidden" id="user_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input id="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input id="username" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No Telepon</label>
                    <input id="phone" type="tel" maxlength="30" placeholder="Contoh: 081234567890" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input id="password" type="password" class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="button" onclick="togglePassword()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <i id="passwordIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select id="role" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="inspector">Inspector</option>
                        <option value="admin">Admin</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="she_section_head">SHE Section Head</option>
                        <option value="user_dept_head">User Dept Head</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanda Tangan</label>
                    <input id="signature" type="file" accept="image/*" onchange="previewSelectedSignature(this)" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <div id="signaturePreview" class="mt-3 text-sm text-gray-400">Belum ada tanda tangan</div>
                </div>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Telepon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanda Tangan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
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
let users = [];
let currentPage = 1;
let pageSize = 25;
let searchFilter = '';
let roleFilter = '';

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showMessage(text, type = 'success') {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = type === 'success'
        ? 'mb-6 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3'
        : 'mb-6 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3';
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function imageUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('data:') || value.startsWith('blob:')) {
        return value;
    }

    return `/${value.replace(/^\/+/, '').replace(/^public\//, '')}`;
}

function signaturePreviewHtml(path) {
    const url = imageUrl(path);
    if (!url) {
        return '<span class="text-sm text-gray-400">Belum ada tanda tangan</span>';
    }

    return `
        <div class="inline-flex flex-col items-start gap-2">
            <img src="${escapeHtml(url)}" alt="Tanda tangan" class="h-16 max-w-[220px] object-contain rounded border border-gray-200 bg-white p-2">
            <a href="${escapeHtml(url)}" target="_blank" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open image</a>
        </div>
    `;
}

function previewSelectedSignature(input) {
    const file = input.files[0];
    if (!file) return;

    document.getElementById('signaturePreview').innerHTML = `
        <div class="inline-flex flex-col items-start gap-2">
            <img src="${URL.createObjectURL(file)}" alt="Preview tanda tangan" class="h-16 max-w-[220px] object-contain rounded border border-gray-200 bg-white p-2">
            <span class="text-xs text-gray-500">${escapeHtml(file.name)}</span>
        </div>
    `;
}

async function loadUsers() {
    const res = await fetch(`${API_URL}/users`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    users = Array.isArray(json.data) ? json.data : [];
    renderTable();
}

function matchesFilters(u) {
    const search = searchFilter.toLowerCase().trim();
    if (roleFilter && u.role !== roleFilter) return false;
    if (!search) return true;
    return String(u.id).includes(search) ||
        (u.name || '').toLowerCase().includes(search) ||
        (u.username || '').toLowerCase().includes(search) ||
        (u.phone || '').toLowerCase().includes(search) ||
        (u.role || '').toLowerCase().includes(search);
}

function renderTable() {
    const tbody = document.getElementById('userTable');

    const filtered = users.filter(matchesFilters);
    const totalItems = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * pageSize;
    const end = Math.min(start + pageSize, totalItems);
    const pageItems = filtered.slice(start, end);

    if (totalItems === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No users found</td></tr>';
    } else {
        tbody.innerHTML = pageItems.map((u, index) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-900">${start + index + 1}</td>
                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(u.name)}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(u.username)}</td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">${escapeHtml(u.phone || '-')}</td>
                <td class="px-6 py-4 text-sm">
                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full ${u.role==='super_admin'?'bg-purple-100 text-purple-800':u.role==='admin'?'bg-blue-100 text-blue-800':u.role==='inspector'?'bg-green-100 text-green-800':u.role==='supervisor'?'bg-yellow-100 text-yellow-800':'bg-gray-100 text-gray-800'}">${escapeHtml(u.role)}</span>
                </td>
                <td class="px-6 py-4 text-sm">${signaturePreviewHtml(u.signature_path)}</td>
                <td class="px-6 py-4 text-sm">
                    <button onclick="editItem(${u.id})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button onclick="deleteItem(${u.id})" class="text-red-600 hover:text-red-800 font-medium">
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

    document.getElementById('pageNumbers').textContent = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
}

function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create User';
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('signaturePreview').innerHTML = signaturePreviewHtml('');
    document.getElementById('messageBox').classList.add('hidden');
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('fa-eye');
        passwordIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('fa-eye-slash');
        passwordIcon.classList.add('fa-eye');
    }
}

function editItem(id) {
    const u = users.find(item => Number(item.id) === Number(id));
    if (!u) return;

    openForm();
    document.getElementById('formTitle').textContent = 'Edit User';
    document.getElementById('user_id').value = u.id;
    document.getElementById('name').value = u.name;
    document.getElementById('username').value = u.username;
    document.getElementById('phone').value = u.phone || '';
    document.getElementById('role').value = u.role;
    document.getElementById('password').value = '';
    document.getElementById('password').type = 'password';
    document.getElementById('passwordIcon').classList.remove('fa-eye-slash');
    document.getElementById('passwordIcon').classList.add('fa-eye');
    document.getElementById('signaturePreview').innerHTML = signaturePreviewHtml(u.signature_path);
}

document.getElementById('userForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('user_id').value;
    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('username', document.getElementById('username').value);
    formData.append('phone', document.getElementById('phone').value.trim());
    formData.append('role', document.getElementById('role').value);

    const password = document.getElementById('password').value;
    if (password) formData.append('password', password);

    const signature = document.getElementById('signature').files[0];
    if (signature) formData.append('signature', signature);
    if (id) formData.append('_method', 'PUT');

    const url = id ? `${API_URL}/users/${id}` : `${API_URL}/users`;
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
            body: formData
        });
        if (!res.ok) {
            const error = await res.json().catch(() => null);
            const message = error?.message || Object.values(error?.errors || {}).flat()[0] || 'Save failed';
            showMessage(message, 'error');
            return;
        }
        closeForm();
        await loadUsers();
        showMessage(id ? 'User berhasil diperbarui.' : 'User berhasil disimpan.', 'success');
    } catch (error) {
        showMessage(error.message || 'Save failed.', 'error');
    }
});

async function deleteItem(id) {
    if (!confirm('Delete this user?')) return;
    try {
        const res = await fetch(`${API_URL}/users/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (res.ok) {
            await loadUsers();
            showMessage('User berhasil dihapus.', 'success');
        } else {
            const error = await res.json().catch(() => null);
            showMessage(error?.message || 'Delete failed.', 'error');
        }
    } catch (error) {
        showMessage(error.message || 'Delete failed.', 'error');
    }
}

function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
        .finally(() => { localStorage.clear(); window.location.href='/'; });
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

document.getElementById('roleFilter').addEventListener('change', function() {
    roleFilter = this.value;
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
    const totalItems = users.filter(matchesFilters).length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage < totalPages) {
        currentPage++;
        renderTable();
    }
});

loadUsers();
</script>
@endsection

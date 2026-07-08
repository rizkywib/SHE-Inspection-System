<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('content'); ?>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input id="password" type="password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select id="role" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="inspector">Inspector</option>
                        <option value="admin">Admin</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="super_admin">Super Admin</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanda Tangan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let users = [];

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
    const tbody = document.getElementById('userTable');
    users = Array.isArray(json.data) ? json.data : [];
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No users found</td></tr>';
        return;
    }
    tbody.innerHTML = users.map(u => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(u.id)}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(u.name)}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${escapeHtml(u.username)}</td>
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

function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create User';
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('signaturePreview').innerHTML = signaturePreviewHtml('');
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }

function editItem(id) {
    const u = users.find(item => Number(item.id) === Number(id));
    if (!u) return;

    openForm();
    document.getElementById('formTitle').textContent = 'Edit User';
    document.getElementById('user_id').value = u.id;
    document.getElementById('name').value = u.name;
    document.getElementById('username').value = u.username;
    document.getElementById('role').value = u.role;
    document.getElementById('password').value = '';
    document.getElementById('signaturePreview').innerHTML = signaturePreviewHtml(u.signature_path);
}

document.getElementById('userForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('user_id').value;
    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('username', document.getElementById('username').value);
    formData.append('role', document.getElementById('role').value);

    const password = document.getElementById('password').value;
    if (password) formData.append('password', password);

    const signature = document.getElementById('signature').files[0];
    if (signature) formData.append('signature', signature);
    if (id) formData.append('_method', 'PUT');

    const url = id ? `${API_URL}/users/${id}` : `${API_URL}/users`;
    const res = await fetch(url, {
        method: 'POST',
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
    loadUsers();
});

async function deleteItem(id) {
    if (!confirm('Delete this user?')) return;
    const res = await fetch(`${API_URL}/users/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (res.ok) loadUsers();
}

function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
        .finally(() => { localStorage.clear(); window.location.href='/'; });
}

loadUsers();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\resources\views/pages/users.blade.php ENDPATH**/ ?>
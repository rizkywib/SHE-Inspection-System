@extends('layouts.app')

@section('title', 'Fire Extinguisher Locations')
@section('nav-fire-extinguisher-locations', 'active')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Fire Extinguisher Locations</h1>
        <p class="text-gray-600 mt-1">Manage fire extinguisher location master data</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Location
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Location</h2>
        <form id="locationForm" class="space-y-4">
            <input type="hidden" id="id_location">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input id="name" required maxlength="200" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="locationTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let locations = [];

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

async function loadLocations() {
    const res = await fetch(`${API_URL}/fire-extinguisher-locations`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const tbody = document.getElementById('locationTable');
    locations = Array.isArray(json.data) ? json.data : [];

    if (locations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No locations found</td></tr>';
        return;
    }

    tbody.innerHTML = locations.map(item => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(item.id_location)}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">${escapeHtml(item.name)}</td>
            <td class="px-6 py-4 text-sm">
                <button onclick="editItem(${item.id_location})" class="text-blue-600 hover:text-blue-800 mr-3 font-medium">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteItem(${item.id_location})" class="text-red-600 hover:text-red-800 font-medium">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </td>
        </tr>
    `).join('');
}

function openForm() {
    document.getElementById('formCard').classList.remove('hidden');
    document.getElementById('formTitle').textContent = 'Create Location';
    document.getElementById('locationForm').reset();
    document.getElementById('id_location').value = '';
}

function closeForm() {
    document.getElementById('formCard').classList.add('hidden');
}

function editItem(id) {
    const item = locations.find(location => Number(location.id_location) === Number(id));
    if (!item) return;

    openForm();
    document.getElementById('formTitle').textContent = 'Edit Location';
    document.getElementById('id_location').value = item.id_location;
    document.getElementById('name').value = item.name || '';
}

document.getElementById('locationForm').addEventListener('submit', async event => {
    event.preventDefault();
    const id = document.getElementById('id_location').value;
    const payload = { name: document.getElementById('name').value };
    const res = await fetch(id ? `${API_URL}/fire-extinguisher-locations/${id}` : `${API_URL}/fire-extinguisher-locations`, {
        method: id ? 'PUT' : 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (!res.ok) {
        alert('Save failed');
        return;
    }

    closeForm();
    loadLocations();
});

async function deleteItem(id) {
    if (!confirm('Delete this location?')) return;
    const res = await fetch(`${API_URL}/fire-extinguisher-locations/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    if (res.ok) loadLocations();
}

function logout() {
    fetch(`${API_URL}/auth/logout`, { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
        .finally(() => { localStorage.clear(); window.location.href='/'; });
}

loadLocations();
</script>
@endsection

@extends('layouts.app')

@section('title', 'Locations')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Locations</h1>
        <p class="text-gray-600 mt-1">Manage inspection locations and sites</p>
    </div>

    <div class="mb-6">
        <button onclick="openForm()" class="btn-primary text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Location
        </button>
    </div>

    <div id="formCard" class="hidden bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-gray-900 mb-4">Create Location</h2>
        <form id="locationForm" class="space-y-4">
            <input type="hidden" id="loc_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input id="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input id="address" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input id="latitude" type="number" step="any" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input id="longitude" type="number" step="any" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="locationTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

async function loadLocations() {
    const res = await fetch(`${API_URL}/locations`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const tbody = document.getElementById('locationTable');
    if (!Array.isArray(json.data) || json.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No locations found</td></tr>';
        return;
    }
    tbody.innerHTML = json.data.map(loc => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm text-gray-900">${loc.id}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">${loc.name}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${loc.address || '-'}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${loc.latitude && loc.longitude ? `${loc.latitude}, ${loc.longitude}` : '-'}</td>
            <td class="px-6 py-4 text-sm">
                <button onclick='editItem(${JSON.stringify(loc)})' class="text-blue-600 hover:text-blue-800 mr-3 font-medium">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <button onclick="deleteItem(${loc.id})" class="text-red-600 hover:text-red-800 font-medium">
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
    document.getElementById('loc_id').value = '';
}
function closeForm() { document.getElementById('formCard').classList.add('hidden'); }

function editItem(loc) {
    openForm();
    document.getElementById('formTitle').textContent = 'Edit Location';
    document.getElementById('loc_id').value = loc.id;
    document.getElementById('name').value = loc.name;
    document.getElementById('address').value = loc.address || '';
    document.getElementById('latitude').value = loc.latitude || '';
    document.getElementById('longitude').value = loc.longitude || '';
}

document.getElementById('locationForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('loc_id').value;
    const payload = {
        name: document.getElementById('name').value,
        address: document.getElementById('address').value,
        latitude: document.getElementById('latitude').value || null,
        longitude: document.getElementById('longitude').value || null,
    };
    const url = id ? `${API_URL}/locations/${id}` : `${API_URL}/locations`;
    const method = id ? 'PUT' : 'POST';
    const res = await fetch(url, {
        method,
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    if (!res.ok) { alert('Save failed'); return; }
    closeForm();
    loadLocations();
});

async function deleteItem(id) {
    if (!confirm('Delete this location?')) return;
    const res = await fetch(`${API_URL}/locations/${id}`, {
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
@extends('layouts.app')

@section('title', 'Dashboard')

@section('nav-dashboard', 'active')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-gray-600">Welcome to ECOGREEN SHE Inspection Management System</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fire Hydrants</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="hydrantCount">-</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-fire-extinguisher text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fire Extinguishers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="extinguisherCount">-</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-fire text-red-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Locations</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="locationCount">-</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Inspectors</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="inspectorCount">-</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Inspections</h2>
            <div id="recentInspections" class="space-y-3">
                <p class="text-gray-500 text-center py-8">Loading...</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="/dashboard/fire-hydrants" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-fire-extinguisher text-blue-600 text-xl mr-4"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Fire Hydrant Inspections</p>
                        <p class="text-sm text-gray-600">View and manage hydrant inspections</p>
                    </div>
                </a>
                <a href="/dashboard/fire-extinguishers" class="flex items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition">
                    <i class="fas fa-fire text-red-600 text-xl mr-4"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Fire Extinguisher Inspections</p>
                        <p class="text-sm text-gray-600">View and manage extinguisher inspections</p>
                    </div>
                </a>
                <a href="/dashboard/points" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <i class="fas fa-map-marker-alt text-green-600 text-xl mr-4"></i>
                    <div>
                        <p class="font-semibold text-gray-900">Points Management</p>
                        <p class="text-sm text-gray-600">Manage inspection points and locations</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');

if (!token) window.location.href = '/';
document.getElementById('userName').textContent = user.name || 'User';

async function fetchCount(path) {
    try {
        const res = await fetch(`${API_URL}${path}`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (!res.ok) return 0;
        const json = await res.json();
        return Array.isArray(json.data) ? json.data.length : 0;
    } catch (error) {
        return 0;
    }
}

async function loadDashboardData() {
    const [hydrants, extinguishers, locations, inspectors] = await Promise.all([
        fetchCount('/fire-hydrants'),
        fetchCount('/fire-extinguishers'),
        fetchCount('/points'),
        fetchCount('/users')
    ]);

    document.getElementById('hydrantCount').textContent = hydrants;
    document.getElementById('extinguisherCount').textContent = extinguishers;
    document.getElementById('locationCount').textContent = locations;
    document.getElementById('inspectorCount').textContent = inspectors;
}

async function loadRecentInspections() {
    try {
        const res = await fetch(`${API_URL}/fire-hydrants?per_page=5`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        const json = await res.json();
        const inspections = Array.isArray(json.data) ? json.data.slice(0, 5) : [];
        
        const container = document.getElementById('recentInspections');
        
        if (inspections.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">No recent inspections</p>';
            return;
        }

        container.innerHTML = inspections.map(inspection => `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 rounded-full p-2">
                        <i class="fas fa-clipboard-check text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 text-sm">${escapeHtml(inspection.reference_no || 'No Reference')}</p>
                        <p class="text-xs text-gray-500">${escapeHtml(inspection.location?.name || inspection.location_id || 'Unknown Location')}</p>
                    </div>
                </div>
                <span class="text-xs text-gray-500">${escapeHtml(formatDateOnly(inspection.inspection_date))}</span>
            </div>
        `).join('');
    } catch (error) {
        document.getElementById('recentInspections').innerHTML = '<p class="text-gray-500 text-center py-8">Failed to load inspections</p>';
    }
}

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

loadDashboardData();
loadRecentInspections();
</script>
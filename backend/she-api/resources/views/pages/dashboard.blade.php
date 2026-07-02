@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back! Here's an overview of your inspection system.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card-hover bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fire Hydrants</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" id="hydrantCount">-</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-fire-extinguisher text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="card-hover bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fire Extinguishers</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" id="extinguisherCount">-</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-fire text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="card-hover bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fire Alarms</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" id="alarmCount">-</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-bell text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="card-hover bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Inspections Today</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" id="todayInspections">-</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-clipboard-check text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="/dashboard/fire-hydrants" class="card-hover flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100">
                <i class="fas fa-fire-extinguisher text-blue-600 text-2xl mr-4"></i>
                <div>
                    <p class="font-semibold text-gray-900">Fire Hydrants</p>
                    <p class="text-sm text-gray-600">Manage inspections</p>
                </div>
            </a>
            <a href="/dashboard/fire-extinguishers" class="card-hover flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100">
                <i class="fas fa-fire text-green-600 text-2xl mr-4"></i>
                <div>
                    <p class="font-semibold text-gray-900">Fire Extinguishers</p>
                    <p class="text-sm text-gray-600">Manage inspections</p>
                </div>
            </a>
            <a href="/dashboard/fire-alarms" class="card-hover flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100">
                <i class="fas fa-bell text-yellow-600 text-2xl mr-4"></i>
                <div>
                    <p class="font-semibold text-gray-900">Fire Alarms</p>
                    <p class="text-sm text-gray-600">Manage inspections</p>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
const API_URL = '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');

if (!token) window.location.href = '/';

document.getElementById('userName').textContent = user.name || 'User';

async function loadDashboard() {
    try {
        const headers = { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' };
        
        const [hydrants, extinguishers, alarms] = await Promise.all([
            fetch(`${API_URL}/fire-hydrants`, { headers }).then(r => r.json()),
            fetch(`${API_URL}/fire-extinguishers`, { headers }).then(r => r.json()),
            fetch(`${API_URL}/fire-alarms`, { headers }).then(r => r.json())
        ]);

        document.getElementById('hydrantCount').textContent = Array.isArray(hydrants.data) ? hydrants.data.length : '-';
        document.getElementById('extinguisherCount').textContent = Array.isArray(extinguishers.data) ? extinguishers.data.length : '-';
        document.getElementById('alarmCount').textContent = Array.isArray(alarms.data) ? alarms.data.length : '-';
    } catch (err) {
        console.error('Failed to load dashboard:', err);
    }
}

function logout() {
    fetch(`${API_URL}/auth/logout`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    }).finally(() => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/';
    });
}

loadDashboard();
</script>
@endsection
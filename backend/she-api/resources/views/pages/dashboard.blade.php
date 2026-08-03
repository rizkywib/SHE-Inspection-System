@extends('layouts.app')

@section('title', 'Dashboard')
@section('nav-dashboard', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto space-y-7">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">SHE Overview</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">Dashboard</h1>
                <p class="mt-1 text-slate-500">Ringkasan inspection dan aktivitas keselamatan terbaru.</p>
            </div>
            <p id="dashboardUpdatedAt" class="text-xs text-slate-400">Memuat data terbaru...</p>
        </header>

        <div id="dashboardError" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"></div>

        <section aria-labelledby="summaryHeading">
            <h2 id="summaryHeading" class="sr-only">Ringkasan data inspection</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <article class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Inspection</p>
                            <p id="totalInspectionCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-400">Menu Inspection</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                            <i class="fas fa-clipboard-check"></i>
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Inspection Open</p>
                            <p id="openInspectionCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-400">Memerlukan tindak lanjut</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Fire Hydrant</p>
                            <p id="hydrantCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-400">Inspection tersimpan</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm">
                            <i class="fas fa-fire-extinguisher"></i>
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Fire Extinguisher</p>
                            <p id="extinguisherCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-400">Inspection tersimpan</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm">
                            <i class="fas fa-fire"></i>
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Inspection Points</p>
                            <p id="pointCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-400">Point aktif</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                            <i class="fas fa-location-dot"></i>
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Active Inspectors</p>
                            <p id="inspectorCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-400">User role inspector</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-600 text-white shadow-sm">
                            <i class="fas fa-user-shield"></i>
                        </span>
                    </div>
                </article>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <section class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden" aria-labelledby="chartHeading">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 px-5 py-5 md:px-6">
                    <div>
                        <h2 id="chartHeading" class="text-lg font-bold text-slate-900">Inspection Berdasarkan Incident Type</h2>
                        <p class="mt-1 text-sm text-slate-500">Distribusi jumlah data dari menu Inspection.</p>
                    </div>
                    <div class="inline-flex items-center gap-2 self-start rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                        <span id="chartTotalCount">0</span>
                        <span>Inspection</span>
                    </div>
                </div>
                <div id="incidentTypeChart" class="max-h-[520px] overflow-y-auto px-5 py-6 md:px-6" role="img" aria-label="Chart jumlah inspection berdasarkan Incident Type">
                    <div class="space-y-5 animate-pulse" aria-hidden="true">
                        <div class="h-12 rounded-xl bg-slate-100"></div>
                        <div class="h-12 rounded-xl bg-slate-100"></div>
                        <div class="h-12 rounded-xl bg-slate-100"></div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden" aria-labelledby="recentHeading">
                <div class="border-b border-slate-100 px-5 py-5">
                    <h2 id="recentHeading" class="text-lg font-bold text-slate-900">Inspection Terbaru</h2>
                    <p class="mt-1 text-sm text-slate-500">Enam aktivitas terakhir.</p>
                </div>
                <div id="recentInspections" class="divide-y divide-slate-100">
                    <p class="px-5 py-10 text-center text-sm text-slate-400">Memuat inspection...</p>
                </div>
                <a href="/dashboard/inspections" class="flex items-center justify-center gap-2 border-t border-slate-100 px-5 py-4 text-sm font-semibold text-blue-600 hover:bg-blue-50">
                    Lihat Semua Inspection
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </section>
        </div>
    </div>
</div>

<script>
const dashboardToken = localStorage.getItem('token');

if (!dashboardToken) {
    window.location.href = '/';
} else {
    loadDashboard();
}

async function dashboardRequest(path) {
    const response = await fetch(`/api/dashboard/${path}`, {
        headers: {
            'Authorization': `Bearer ${dashboardToken}`,
            'Accept': 'application/json',
        },
    });

    if (response.status === 401) {
        localStorage.clear();
        window.location.href = '/';
        throw new Error('Sesi login telah berakhir.');
    }

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Data dashboard gagal dimuat.');
    return payload.data;
}

async function loadDashboard() {
    try {
        const [stats, recent, summary] = await Promise.all([
            dashboardRequest('stats'),
            dashboardRequest('recent-inspections'),
            dashboardRequest('incident-summary'),
        ]);

        renderStats(stats);
        renderIncidentTypeChart(summary);
        renderRecentInspections(recent);
        document.getElementById('dashboardUpdatedAt').textContent = `Diperbarui ${formatDateTime(new Date())}`;
    } catch (error) {
        const alert = document.getElementById('dashboardError');
        alert.textContent = error.message || 'Data dashboard gagal dimuat.';
        alert.classList.remove('hidden');
        document.getElementById('dashboardUpdatedAt').textContent = 'Data belum dapat diperbarui';
        document.getElementById('incidentTypeChart').innerHTML = emptyState('Chart belum dapat dimuat.');
        document.getElementById('recentInspections').innerHTML = emptyState('Inspection terbaru belum dapat dimuat.');
    }
}

function renderStats(stats) {
    const mappings = {
        totalInspectionCount: stats.total_inspections,
        openInspectionCount: stats.open_inspections,
        hydrantCount: stats.fire_hydrants,
        extinguisherCount: stats.fire_extinguishers,
        pointCount: stats.inspection_points,
        inspectorCount: stats.active_inspectors,
    };

    Object.entries(mappings).forEach(([id, value]) => {
        document.getElementById(id).textContent = formatNumber(value);
    });
}

function renderIncidentTypeChart(summary) {
    const chart = document.getElementById('incidentTypeChart');
    const types = Array.isArray(summary.types) ? summary.types : [];
    const total = Number(summary.total || 0);
    const maximum = Math.max(...types.map(item => Number(item.total || 0)), 1);
    const colors = ['#2563eb', '#0891b2', '#059669', '#7c3aed', '#ea580c', '#db2777'];

    document.getElementById('chartTotalCount').textContent = formatNumber(total);

    if (types.length === 0) {
        chart.innerHTML = emptyState('Belum ada data inspection untuk ditampilkan pada chart.');
        return;
    }

    chart.innerHTML = `<div class="space-y-5">${types.map((item, index) => {
        const count = Number(item.total || 0);
        const percentage = Math.max((count / maximum) * 100, count > 0 ? 3 : 0);
        return `
            <div>
                <div class="mb-2 flex items-start justify-between gap-4">
                    <p class="text-sm font-medium leading-5 text-slate-700">${escapeHtml(item.name)}</p>
                    <p class="shrink-0 text-sm font-bold text-slate-900">${formatNumber(count)}</p>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full" style="width:${percentage.toFixed(2)}%;background:${colors[index % colors.length]}" aria-hidden="true"></div>
                </div>
            </div>`;
    }).join('')}</div>`;
}

function renderRecentInspections(inspections) {
    const container = document.getElementById('recentInspections');
    const rows = Array.isArray(inspections) ? inspections : [];

    if (rows.length === 0) {
        container.innerHTML = emptyState('Belum ada data inspection.');
        return;
    }

    container.innerHTML = rows.map(item => `
        <a href="/dashboard/inspections" class="block px-5 py-4 hover:bg-slate-50">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="fas fa-clipboard-check text-sm"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="truncate text-sm font-semibold text-slate-800">${escapeHtml(item.incident_type?.name || 'Inspection')}</p>
                        ${statusBadge(item.status)}
                    </div>
                    <p class="mt-1 truncate text-xs text-slate-500">${escapeHtml(item.location_text || 'Lokasi belum diisi')}</p>
                    <p class="mt-1 text-xs text-slate-400">${escapeHtml(formatInspectionDate(item.incident_date, item.incident_time))}</p>
                </div>
            </div>
        </a>
    `).join('');
}

function statusBadge(status) {
    const isClosed = ['close', 'closed'].includes(String(status || '').toLowerCase());
    const label = isClosed ? 'Closed' : 'Open';
    const classes = isClosed
        ? 'bg-emerald-50 text-emerald-700'
        : 'bg-amber-50 text-amber-700';
    return `<span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold ${classes}">${label}</span>`;
}

function emptyState(message) {
    return `<div class="px-5 py-10 text-center">
        <i class="far fa-chart-bar mb-3 block text-3xl text-slate-300"></i>
        <p class="text-sm text-slate-500">${escapeHtml(message)}</p>
    </div>`;
}

function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
}

function formatInspectionDate(date, time) {
    if (!date) return '-';
    const rawDate = String(date).slice(0, 10);
    const rawTime = time ? String(time).slice(0, 5) : '';
    const parsed = new Date(`${rawDate}T${rawTime || '00:00'}:00`);
    if (Number.isNaN(parsed.getTime())) return rawDate;
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(rawTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(parsed);
}

function formatDateTime(value) {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(value);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endsection

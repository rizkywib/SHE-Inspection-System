@extends('layouts.app')

@section('title', 'Dashboard')
@section('nav-dashboard', 'active')

@section('content')
<div class="min-h-full bg-[#f5f8f6] px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="relative overflow-hidden rounded-3xl bg-[#0e5735] px-5 py-6 text-white shadow-xl shadow-emerald-950/10 sm:px-8 sm:py-8" aria-labelledby="dashboardTitle">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full border-[28px] border-white/10"></div>
            <div class="pointer-events-none absolute -bottom-28 right-28 h-48 w-48 rounded-full border-[20px] border-emerald-300/10"></div>
            <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <div class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-200">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10"><i class="fas fa-shield-heart"></i></span>
                        SHE Overview
                    </div>
                    <h1 id="dashboardTitle" class="text-3xl font-bold tracking-tight sm:text-4xl">
                        Selamat datang, <span id="dashboardUserName">User</span>
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-emerald-50/80 sm:text-base">
                        Pantau kondisi keselamatan kerja dan tindak lanjut inspection dari satu tempat.
                    </p>
                    <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-emerald-100/80">
                        <span><i class="far fa-calendar mr-2"></i><span id="dashboardToday">-</span></span>
                        <span><i class="fas fa-user-shield mr-2"></i><span id="dashboardUserRole">-</span></span>
                        <span><i class="fas fa-sync-alt mr-2"></i><span id="dashboardUpdatedAt">Memuat data terbaru...</span></span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button id="refreshDashboardButton" type="button" onclick="loadDashboard()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60">
                        <i id="refreshDashboardIcon" class="fas fa-rotate-right"></i>
                        <span>Perbarui</span>
                    </button>
                    <a href="/dashboard/inspections" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#0e5735] shadow-sm hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-white/60">
                        <i class="fas fa-plus"></i>
                        Inspection Baru
                    </a>
                </div>
            </div>
        </section>

        <div id="dashboardError" class="hidden flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between" role="alert">
            <span id="dashboardErrorMessage"></span>
            <button type="button" onclick="loadDashboard()" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-red-100 px-3 py-2 font-semibold text-red-700 hover:bg-red-200">
                <i class="fas fa-rotate-right"></i> Coba lagi
            </button>
        </div>

        <section aria-labelledby="summaryHeading">
            <div class="mb-3 flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#177245]">Ringkasan utama</p>
                    <h2 id="summaryHeading" class="mt-1 text-xl font-bold text-slate-900">Kondisi SHE hari ini</h2>
                </div>
                <span class="hidden text-xs text-slate-400 sm:block">Data dari seluruh modul</span>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                <article class="stat-card group rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-2"><span class="stat-icon bg-emerald-50 text-[#177245]"><i class="fas fa-clipboard-check"></i></span><span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">All</span></div>
                    <p class="mt-5 text-2xl font-extrabold text-slate-900" id="totalInspectionCount">0</p>
                    <p class="mt-1 text-xs font-medium leading-4 text-slate-500">Total inspection</p>
                </article>
                <article class="stat-card group rounded-2xl border border-amber-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-2"><span class="stat-icon bg-amber-50 text-amber-600"><i class="fas fa-hourglass-half"></i></span><span class="text-[10px] font-bold uppercase tracking-wide text-amber-600">Action</span></div>
                    <p class="mt-5 text-2xl font-extrabold text-slate-900" id="openInspectionCount">0</p>
                    <p class="mt-1 text-xs font-medium leading-4 text-slate-500">Perlu tindak lanjut</p>
                </article>
                <article class="stat-card group rounded-2xl border border-sky-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-2"><span class="stat-icon bg-sky-50 text-sky-600"><i class="fas fa-faucet-drip"></i></span><span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Asset</span></div>
                    <p class="mt-5 text-2xl font-extrabold text-slate-900" id="hydrantCount">0</p>
                    <p class="mt-1 text-xs font-medium leading-4 text-slate-500">Fire hydrant</p>
                </article>
                <article class="stat-card group rounded-2xl border border-rose-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-2"><span class="stat-icon bg-rose-50 text-rose-600"><i class="fas fa-fire-extinguisher"></i></span><span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Asset</span></div>
                    <p class="mt-5 text-2xl font-extrabold text-slate-900" id="extinguisherCount">0</p>
                    <p class="mt-1 text-xs font-medium leading-4 text-slate-500">Fire extinguisher</p>
                </article>
                <article class="stat-card group rounded-2xl border border-violet-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-2"><span class="stat-icon bg-violet-50 text-violet-600"><i class="fas fa-location-dot"></i></span><span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Master</span></div>
                    <p class="mt-5 text-2xl font-extrabold text-slate-900" id="pointCount">0</p>
                    <p class="mt-1 text-xs font-medium leading-4 text-slate-500">Active points</p>
                </article>
                <article class="stat-card group rounded-2xl border border-teal-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-2"><span class="stat-icon bg-teal-50 text-teal-600"><i class="fas fa-user-shield"></i></span><span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">People</span></div>
                    <p class="mt-5 text-2xl font-extrabold text-slate-900" id="inspectorCount">0</p>
                    <p class="mt-1 text-xs font-medium leading-4 text-slate-500">Active inspectors</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="quickActionsHeading">
            <div class="mb-3">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#177245]">Akses cepat</p>
                <h2 id="quickActionsHeading" class="mt-1 text-xl font-bold text-slate-900">Mulai dari sini</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a href="/dashboard/inspections" class="quick-action border-blue-100 bg-blue-50/60 hover:border-blue-300 hover:bg-blue-50">
                    <span class="bg-blue-600 text-white"><i class="fas fa-clipboard-check"></i></span><span><strong>Inspection</strong><small>Buat laporan baru</small></span><i class="fas fa-arrow-right ml-auto text-xs text-blue-500"></i>
                </a>
                <a href="/dashboard/fire-hydrants" class="quick-action border-sky-100 bg-sky-50/60 hover:border-sky-300 hover:bg-sky-50">
                    <span class="bg-sky-600 text-white"><i class="fas fa-faucet-drip"></i></span><span><strong>Hydrant</strong><small>Kelola inspeksi</small></span><i class="fas fa-arrow-right ml-auto text-xs text-sky-500"></i>
                </a>
                <a href="/dashboard/fire-extinguishers#inspection-list" class="quick-action border-rose-100 bg-rose-50/60 hover:border-rose-300 hover:bg-rose-50">
                    <span class="bg-rose-500 text-white"><i class="fas fa-fire-extinguisher"></i></span><span><strong>APAR</strong><small>Kelola inspeksi</small></span><i class="fas fa-arrow-right ml-auto text-xs text-rose-500"></i>
                </a>
                <a href="/dashboard/permit-matrix" class="quick-action border-amber-100 bg-amber-50/60 hover:border-amber-300 hover:bg-amber-50">
                    <span class="bg-amber-500 text-white"><i class="fas fa-file-signature"></i></span><span><strong>Permit Matrix</strong><small>Lihat permit kerja</small></span><i class="fas fa-arrow-right ml-auto text-xs text-amber-500"></i>
                </a>
            </div>
        </section>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.75fr)]">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="chartHeading">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#177245]">Distribusi data</p><h2 id="chartHeading" class="mt-1 text-lg font-bold text-slate-900">Inspection berdasarkan tipe</h2><p class="mt-1 text-sm text-slate-500">Perbandingan laporan menurut incident type.</p></div>
                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-right"><p class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">Total laporan</p><p id="chartTotalCount" class="text-xl font-extrabold text-[#177245]">0</p></div>
                </div>
                <div id="incidentTypeChart" class="px-5 py-6 sm:px-8" aria-label="Diagram jumlah inspection berdasarkan tipe">
                    <div class="space-y-4 animate-pulse" aria-hidden="true"><div class="mx-auto h-52 w-52 rounded-full bg-slate-100"></div><div class="h-10 rounded-xl bg-slate-100"></div></div>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recentHeading">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-5">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#177245]">Aktivitas</p><h2 id="recentHeading" class="mt-1 text-lg font-bold text-slate-900">Inspection terbaru</h2><p class="mt-1 text-sm text-slate-500">Enam laporan terakhir.</p></div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500"><i class="fas fa-clock-rotate-left"></i></span>
                </div>
                <div id="recentInspections" class="divide-y divide-slate-100"><p class="px-5 py-10 text-center text-sm text-slate-400">Memuat inspection...</p></div>
                <a href="/dashboard/inspections" class="flex items-center justify-center gap-2 border-t border-slate-100 px-5 py-4 text-sm font-bold text-[#177245] hover:bg-emerald-50">Lihat semua inspection <i class="fas fa-arrow-right text-xs"></i></a>
            </section>
        </div>
    </div>
</div>

<style>
    .stat-card { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15, 81, 48, .09); }
    .stat-icon { display: inline-flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: .8rem; font-size: 1rem; }
    .quick-action { display: flex; min-height: 5.25rem; align-items: center; gap: .7rem; border-width: 1px; border-radius: 1rem; padding: .8rem; transition: transform .2s ease, border-color .2s ease, background-color .2s ease; }
    .quick-action:hover { transform: translateY(-2px); }
    .quick-action > span:first-child { display: inline-flex; height: 2.35rem; width: 2.35rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: .7rem; font-size: .9rem; }
    .quick-action strong, .quick-action small { display: block; }
    .quick-action strong { color: #19382b; font-size: .78rem; line-height: 1.2; }
    .quick-action small { margin-top: .25rem; color: #64748b; font-size: .68rem; line-height: 1.2; }
    @media (prefers-reduced-motion: reduce) { .stat-card, .quick-action { transition: none; } .stat-card:hover, .quick-action:hover { transform: none; } }
</style>

<script>
const dashboardToken = localStorage.getItem('token');
const dashboardUser = JSON.parse(localStorage.getItem('user') || '{}');

if (!dashboardToken) {
    window.location.href = '/';
} else {
    renderDashboardUser();
    loadDashboard();
}

function renderDashboardUser() {
    const name = String(dashboardUser.name || dashboardUser.username || 'User').trim();
    document.getElementById('dashboardUserName').textContent = name.split(/\s+/)[0];
    document.getElementById('dashboardUserRole').textContent = String(dashboardUser.role || 'User').replaceAll('_', ' ');
    document.getElementById('dashboardToday').textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }).format(new Date());
}

async function dashboardRequest(path) {
    const response = await fetch(`/api/dashboard/${path}`, {
        headers: { 'Authorization': `Bearer ${dashboardToken}`, 'Accept': 'application/json' },
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
    const button = document.getElementById('refreshDashboardButton');
    const icon = document.getElementById('refreshDashboardIcon');
    const error = document.getElementById('dashboardError');
    button.disabled = true;
    button.classList.add('cursor-wait', 'opacity-80');
    icon.classList.add('fa-spin');
    error.classList.add('hidden');

    try {
        const [stats, recent, summary] = await Promise.all([
            dashboardRequest('stats'),
            dashboardRequest('recent-inspections'),
            dashboardRequest('incident-summary'),
        ]);
        renderStats(stats || {});
        renderIncidentTypeChart(summary || {});
        renderRecentInspections(recent);
        document.getElementById('dashboardUpdatedAt').textContent = `Diperbarui ${formatDateTime(new Date())}`;
    } catch (requestError) {
        document.getElementById('dashboardErrorMessage').textContent = requestError.message || 'Data dashboard gagal dimuat.';
        error.classList.remove('hidden');
        error.classList.add('flex');
        document.getElementById('dashboardUpdatedAt').textContent = 'Data belum dapat diperbarui';
        document.getElementById('incidentTypeChart').innerHTML = emptyState('Diagram belum dapat dimuat.');
        document.getElementById('recentInspections').innerHTML = emptyState('Inspection terbaru belum dapat dimuat.');
    } finally {
        button.disabled = false;
        button.classList.remove('cursor-wait', 'opacity-80');
        icon.classList.remove('fa-spin');
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
    Object.entries(mappings).forEach(([id, value]) => { document.getElementById(id).textContent = formatNumber(value); });
}

function renderIncidentTypeChart(summary) {
    const chart = document.getElementById('incidentTypeChart');
    const types = Array.isArray(summary.types) ? summary.types : [];
    const total = Number(summary.total || 0);
    const displayedTotal = types.reduce((sum, item) => sum + Number(item.total || 0), 0);
    const colors = ['#177245', '#2563eb', '#0891b2', '#7c3aed', '#ea580c', '#db2777', '#d97706', '#0d9488'];
    document.getElementById('chartTotalCount').textContent = formatNumber(total);

    if (!types.length || displayedTotal <= 0) {
        chart.innerHTML = emptyState('Belum ada data inspection untuk ditampilkan.');
        return;
    }

    let currentAngle = 0;
    const slices = types.map((item, index) => {
        const count = Number(item.total || 0);
        const percentage = (count / displayedTotal) * 100;
        const startAngle = currentAngle;
        currentAngle += (count / displayedTotal) * 360;
        return { name: item.name, count, percentage, color: colors[index % colors.length], startAngle, endAngle: currentAngle };
    });
    const gradient = slices.map(slice => `${slice.color} ${slice.startAngle.toFixed(2)}deg ${slice.endAngle.toFixed(2)}deg`).join(', ');
    const accessibleSummary = slices.map(slice => `${slice.name}: ${formatNumber(slice.count)} inspection (${formatPercentage(slice.percentage)})`).join(', ');

    chart.innerHTML = `
        <div class="grid items-center gap-7 md:grid-cols-[minmax(220px,280px)_minmax(0,1fr)]">
            <div class="relative mx-auto aspect-square w-full max-w-[250px]">
                <div class="absolute inset-0 rounded-full shadow-inner ring-1 ring-slate-200" style="background:conic-gradient(${gradient})" role="img" aria-label="${escapeHtml(accessibleSummary)}"></div>
                <div class="absolute inset-[22%] flex flex-col items-center justify-center rounded-full bg-white text-center shadow-sm"><span class="text-2xl font-extrabold text-slate-900">${formatNumber(total)}</span><span class="mt-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">Inspection</span></div>
            </div>
            <ul class="grid gap-2.5 sm:grid-cols-2 md:grid-cols-1" aria-label="Legenda diagram inspection">
                ${slices.map(slice => `<li class="flex items-center gap-3 rounded-xl border border-slate-100 px-3 py-2.5"><span class="h-3 w-3 shrink-0 rounded-full" style="background:${slice.color}" aria-hidden="true"></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-700" title="${escapeHtml(slice.name)}">${escapeHtml(slice.name)}</p><p class="text-xs text-slate-400">${formatPercentage(slice.percentage)}</p></div><span class="text-sm font-extrabold text-slate-900">${formatNumber(slice.count)}</span></li>`).join('')}
            </ul>
        </div>`;
}

function renderRecentInspections(inspections) {
    const container = document.getElementById('recentInspections');
    const rows = Array.isArray(inspections) ? inspections : [];
    if (!rows.length) { container.innerHTML = emptyState('Belum ada data inspection.'); return; }
    container.innerHTML = rows.map(item => `
        <a href="/dashboard/inspections" class="block px-5 py-3.5 hover:bg-emerald-50/50">
            <div class="flex items-start gap-3"><span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#177245]"><i class="fas fa-clipboard-check text-sm"></i></span><div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><p class="truncate text-sm font-bold text-slate-800">${escapeHtml(item.incident_type?.name || 'Inspection')}</p>${statusBadge(item.status)}</div><p class="mt-1 truncate text-xs text-slate-500"><i class="fas fa-location-dot mr-1 text-slate-400"></i>${escapeHtml(item.location_text || 'Lokasi belum diisi')}</p><p class="mt-1 text-xs text-slate-400">${escapeHtml(formatInspectionDate(item.incident_date, item.incident_time))}</p></div></div>
        </a>`).join('');
}

function statusBadge(status) {
    const isClosed = ['close', 'closed'].includes(String(status || '').toLowerCase());
    return `<span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold ${isClosed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${isClosed ? 'Closed' : 'Open'}</span>`;
}

function emptyState(message) { return `<div class="px-5 py-10 text-center"><i class="far fa-chart-bar mb-3 block text-3xl text-slate-300"></i><p class="text-sm text-slate-500">${escapeHtml(message)}</p></div>`; }
function formatNumber(value) { return new Intl.NumberFormat('id-ID').format(Number(value || 0)); }
function formatPercentage(value) { return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(Number(value || 0))}%`; }
function formatInspectionDate(date, time) {
    if (!date) return '-';
    const rawDate = String(date).slice(0, 10); const rawTime = time ? String(time).slice(0, 5) : '';
    const parsed = new Date(`${rawDate}T${rawTime || '00:00'}:00`);
    if (Number.isNaN(parsed.getTime())) return rawDate;
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', ...(rawTime ? { hour: '2-digit', minute: '2-digit' } : {}) }).format(parsed);
}
function formatDateTime(value) { return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(value); }
function escapeHtml(value) { return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
</script>
@endsection

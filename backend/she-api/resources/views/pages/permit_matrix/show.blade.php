@extends('layouts.app')

@section('title', 'Detail Permit Matrix')
@section('nav-permit-matrix', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <a href="/dashboard/permit-matrix" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            <h1 class="text-3xl font-bold text-gray-900 mt-3">Detail Permit Matrix</h1>
        </div>
        <a id="editButton" href="/dashboard/permit-matrix/{{ $inspectionId }}/edit" class="hidden bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-lg">
            <i class="fas fa-edit mr-2"></i>Edit
        </a>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800"></div>
    <div id="detailCard" class="bg-white rounded-xl shadow p-6">
        <p class="text-gray-500">Memuat data...</p>
    </div>
</div>

<script>
const token = localStorage.getItem('token');
const inspectionId = @json($inspectionId);
if (!token) window.location.href = '/';

const escapeHtml = value => String(value ?? '-').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const row = (label, value, full = false) => `<div class="${full ? 'md:col-span-2' : ''}"><dt class="text-sm font-medium text-gray-500">${label}</dt><dd class="mt-1 text-gray-900 whitespace-pre-wrap">${escapeHtml(value)}</dd></div>`;

async function loadDetail() {
    const profileResponse = await fetch('/api/auth/me', {
        headers: {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'},
    });
    if (profileResponse.status === 401) {
        localStorage.clear();
        return window.location.href = '/';
    }
    if (profileResponse.ok) {
        const user = await profileResponse.json();
        const permissions = Array.isArray(user.permissions) ? user.permissions : [];
        localStorage.setItem('user', JSON.stringify(user));
        if (user.role === 'super_admin' || permissions.includes('safe-work-permit-inspection.update')) {
            document.getElementById('editButton').classList.remove('hidden');
        }
    }

    const response = await fetch(`/api/safe-work-permit-inspections/${inspectionId}`, {
        headers: {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'},
    });
    if (!response.ok) {
        const message = document.getElementById('messageBox');
        message.textContent = response.status === 403 ? 'Anda tidak memiliki izin melihat data ini.' : 'Data Permit Matrix tidak ditemukan.';
        message.classList.remove('hidden');
        document.getElementById('detailCard').classList.add('hidden');
        return;
    }

    const data = (await response.json()).data;
    const withFinding = data.finding_status === 'Ada Temuan';
    document.getElementById('detailCard').innerHTML = `
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
            ${row('Tanggal Permit', data.permit_date)}
            ${row('No. Permit', data.permit_number)}
            ${row('Nama Inspector', data.inspector?.name)}
            ${row('Type Permit', data.permit_type?.name)}
            ${row('Area Pengawasan', data.supervision_area?.code)}
            ${row('Main Area', data.main_area?.name)}
            ${row('Sub Area', data.sub_area?.name)}
            ${row('Section / Equipment', data.section_equipment)}
            ${row('Job Performance', data.job_performance, true)}
            ${row('Authorized Craftman', data.authorized_craftman)}
            ${row('Authorized Facility', data.authorized_facility)}
            ${row('Nama Kontraktor', data.contractor_name, true)}
            ${row('Uraian Pekerjaan', data.work_description, true)}
            <div class="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">Status Temuan</dt>
                <dd class="mt-2"><span class="px-3 py-1 rounded-full text-xs font-semibold ${withFinding ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">${data.finding_status}</span></dd>
            </div>
            ${row('Temuan Terkait Safe Work Permit', data.permit_findings || 'Tidak ada temuan.', true)}
        </dl>`;
}

loadDetail();
</script>
@endsection

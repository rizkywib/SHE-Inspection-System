@extends('layouts.app')
@section('title', 'Detail Safety Talk')
@section('nav-safety-talk-trainings', 'active')
@section('content')
<div class="p-4 md:p-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <a href="/dashboard/safety-talk-trainings" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            <h1 class="text-3xl font-bold text-gray-900 mt-3">Detail Safety Talk / Training On Site</h1>
        </div>
        <div class="flex gap-3">
            <a id="editButton" href="/dashboard/safety-talk-trainings/{{ $trainingId }}/edit" class="hidden bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-lg"><i class="fas fa-edit mr-2"></i>Edit</a>
            <button id="deleteButton" class="hidden bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-lg"><i class="fas fa-trash mr-2"></i>Hapus</button>
        </div>
    </div>
    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800"></div>
    <div id="detailCard" class="bg-white rounded-xl shadow p-6"><p class="text-gray-500">Memuat data...</p></div>
</div>
<script>
const token = localStorage.getItem('token');
const trainingId = @json($trainingId);
const headers = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
if (!token) window.location.href = '/';
const escapeHtml = value => String(value ?? '-').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const formatDate = value => value ? new Date(`${value}T00:00:00`).toLocaleDateString('id-ID') : '-';
const formatDateTime = value => value ? new Date(value).toLocaleString('id-ID') : '-';
const item = (label, value, full = false) => `<div class="${full ? 'md:col-span-2' : ''}"><dt class="text-sm font-medium text-gray-500">${label}</dt><dd class="mt-1 text-gray-900 whitespace-pre-wrap">${escapeHtml(value)}</dd></div>`;
function error(text) {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.classList.remove('hidden');
    document.getElementById('detailCard').classList.add('hidden');
}
async function init() {
    const profileResponse = await fetch('/api/auth/me', {headers});
    if (profileResponse.status === 401) { localStorage.clear(); return window.location.href = '/'; }
    const user = await profileResponse.json();
    const permissions = Array.isArray(user.permissions) ? user.permissions : [];
    const can = permission => user.role === 'super_admin' || permissions.includes(permission);
    if (!can('safety-talk-training.view')) return error('Anda tidak memiliki izin melihat data ini.');

    const response = await fetch(`/api/safety-talk-trainings/${trainingId}`, {headers});
    if (!response.ok) return error('Data Safety Talk tidak ditemukan.');
    const data = (await response.json()).data;
    const canModify = user.role === 'super_admin' || user.role === 'admin';
    if (canModify) {
        document.getElementById('editButton').classList.remove('hidden');
        document.getElementById('deleteButton').classList.remove('hidden');
    }
    document.getElementById('detailCard').innerHTML = `
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
            ${item('Pembicara', data.speaker?.name || data.legacy_speaker?.name)}
            ${item('Tanggal Pelaksanaan', formatDate(data.implementation_date))}
            ${item('Topik / Materi', data.topic, true)}
            ${item('Peserta Ecogreen', data.ecogreen_participants)}
            ${item('Peserta Outsourcing', data.outsourcing_participants)}
            ${item('Peserta Contractor', data.contractor_participants)}
            ${item('Total Peserta', data.total_participants)}
            ${item('Durasi', `${data.duration_minutes} menit`)}
            ${item('Area Pelaksanaan', `Area ${data.implementation_area}`)}
            ${item('Dibuat Oleh', data.creator?.name)}
            ${item('Waktu Dibuat', formatDateTime(data.created_at))}
            ${item('Terakhir Diperbarui', formatDateTime(data.updated_at))}
            <div class="md:col-span-2"><dt class="text-sm font-medium text-gray-500 mb-2">Foto Kegiatan</dt><dd><a href="${escapeHtml(data.activity_photo_url)}" target="_blank"><img src="${escapeHtml(data.activity_photo_url)}" alt="Foto kegiatan Safety Talk" class="max-h-[520px] w-auto rounded-xl border shadow-sm object-contain"></a></dd></div>
        </dl>`;
}
document.getElementById('deleteButton').addEventListener('click', async () => {
    if (!confirm('Hapus data Safety Talk ini? Foto kegiatan juga akan dihapus.')) return;
    const response = await fetch(`/api/safety-talk-trainings/${trainingId}`, {method: 'DELETE', headers});
    const data = await response.json();
    if (!response.ok) return error(data.message || 'Data gagal dihapus.');
    sessionStorage.setItem('safetyTalkMessage', data.message);
    window.location.href = '/dashboard/safety-talk-trainings';
});
init().catch(() => error('Terjadi kesalahan saat memuat detail.'));
</script>
@endsection

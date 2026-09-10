@extends('layouts.app')

@section('title', 'Create Fire Alarm Item')
@section('nav-fire-alarms', 'active')

@section('content')
<div class="p-8">
    <div class="mb-6 flex items-center gap-4">
        <a href="/dashboard/fire-alarms/{{ $inspectionId }}/items" class="text-gray-500 hover:text-gray-800">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Create Fire Alarm Item</h1>
            <p class="text-gray-600 mt-1">Header inspection mengikuti data sebelumnya (readonly), hanya bagian item yang dapat diisi.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <form id="alarmItemForm" class="space-y-6">
            <input type="hidden" id="reference_no">
            <input type="hidden" id="location_id">
            <input type="hidden" id="inspector_id">

            <section>
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide border-b border-gray-200 pb-3 mb-4">Inspection Data</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label>
                        <input id="inspection_date" type="date" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input id="location_name" type="text" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inspector</label>
                        <input id="inspector_name" type="text" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-200 pt-6">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Fire Alarm Item</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alarm Number</label>
                        <input id="item_alarm_number" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <select id="item_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">- Select Point -</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <input id="item_type" type="text" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location Detail</label>
                        <input id="item_location_detail" type="text" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100">
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-200 pt-6">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Kondisi</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="border border-gray-200 rounded-lg px-3 py-3">
                        <span class="block text-sm font-medium text-gray-700 mb-2">Condition Good</span>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-1 text-sm"><input name="condition_good" type="radio" value="1"> Yes</label>
                            <label class="flex items-center gap-1 text-sm"><input name="condition_good" type="radio" value="0"> No</label>
                        </div>
                    </div>
                    <div class="border border-gray-200 rounded-lg px-3 py-3">
                        <span class="block text-sm font-medium text-gray-700 mb-2">Correction Needed</span>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-1 text-sm"><input name="correction_needed" type="radio" value="1"> Yes</label>
                            <label class="flex items-center gap-1 text-sm"><input name="correction_needed" type="radio" value="0"> No</label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sebelum</label>
                        <input id="item_photo_before" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sesudah</label>
                        <input id="item_photo_after" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remark</label>
                        <textarea id="item_remark" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-3 pt-2">
                <button id="saveButton" type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 disabled:opacity-60">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
                <a href="/dashboard/fire-alarms/{{ $inspectionId }}/items" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const API_URL = '/api';
    const inspectionId = @json($inspectionId);
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    let points = [];

    if (!token) {
        window.location.href = '/';
        return;
    }
    document.getElementById('userName').textContent = user.name || 'User';

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function formatDate(value) { return value ? String(value).slice(0, 10) : ''; }
    function getRadioValue(name) { return document.querySelector(`input[name="${name}"]:checked`)?.value ?? null; }
    function validationMessage(json, fallback) {
        const errors = json?.errors ? Object.values(json.errors).flat() : [];
        return errors.length ? errors.join('\n') : (json?.message || fallback);
    }

    async function apiFetch(path, options = {}) {
        const response = await fetch(`${API_URL}${path}`, {
            cache: 'no-store',
            ...options,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                ...(options.headers || {})
            }
        });
        if (response.status === 401) {
            localStorage.clear();
            window.location.href = '/';
            throw new Error('Session expired');
        }
        return response;
    }

    function onPointChange() {
        const point = points.find(row => Number(row.id) === Number(document.getElementById('item_name').value));
        document.getElementById('item_type').value = point?.ket1 || '';
        document.getElementById('item_location_detail').value = point?.ket2 || '';
    }

    async function loadPage() {
        try {
            const [pointsResponse, inspectionResponse] = await Promise.all([
                apiFetch('/points'),
                apiFetch(`/fire-alarms/${inspectionId}`)
            ]);
            const pointsJson = await pointsResponse.json();
            const inspectionJson = await inspectionResponse.json();
            if (!pointsResponse.ok) throw new Error(pointsJson.message || 'Gagal memuat master data');
            if (!inspectionResponse.ok) throw new Error(inspectionJson.message || 'Data inspection tidak ditemukan');

            points = Array.isArray(pointsJson.data) ? pointsJson.data : [];
            document.getElementById('item_name').innerHTML = `<option value="">- Select Point -</option>` + points.map(row =>
                `<option value="${row.id}">${escapeHtml(row.name_point)}</option>`
            ).join('');

            const inspection = inspectionJson.data || {};

            document.getElementById('reference_no').value = inspection.reference_no || '';
            document.getElementById('inspection_date').value = formatDate(inspection.inspection_date);
            document.getElementById('location_id').value = inspection.location_id || '';
            document.getElementById('inspector_id').value = inspection.inspector_id || '';
            document.getElementById('location_name').value = (inspection.location && inspection.location.name) || '-';
            document.getElementById('inspector_name').value = (inspection.inspector && inspection.inspector.name) || '-';
        } catch (error) {
            alert(error.message || 'Gagal memuat form');
        }
    }

    document.getElementById('item_name').addEventListener('change', onPointChange);
    document.getElementById('alarmItemForm').addEventListener('submit', async event => {
        event.preventDefault();
        const saveButton = document.getElementById('saveButton');
        const originalHtml = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

        try {
            const formData = new FormData();
            const values = {
                reference_no: document.getElementById('reference_no').value,
                inspection_date: document.getElementById('inspection_date').value,
                location_id: document.getElementById('location_id').value,
                inspector_id: document.getElementById('inspector_id').value,
                'items[0][name]': (points.find(row => Number(row.id) === Number(document.getElementById('item_name').value)) || {}).name_point || '',
                'items[0][alarm_number]': document.getElementById('item_alarm_number').value,
                'items[0][type]': document.getElementById('item_type').value,
                'items[0][location_detail]': document.getElementById('item_location_detail').value,
                'items[0][condition_good]': getRadioValue('condition_good'),
                'items[0][correction_needed]': getRadioValue('correction_needed'),
                'items[0][remark]': document.getElementById('item_remark').value
            };
            Object.entries(values).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') formData.append(key, value);
            });

            const beforePhoto = document.getElementById('item_photo_before').files[0];
            const afterPhoto = document.getElementById('item_photo_after').files[0];
            if (beforePhoto) formData.append('items[0][photo_before]', beforePhoto);
            if (afterPhoto) formData.append('items[0][photo_after]', afterPhoto);
            formData.append('_method', 'PUT');

            const response = await apiFetch(`/fire-alarms/${inspectionId}`, { method: 'POST', body: formData });
            const json = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(validationMessage(json, `Save failed (${response.status})`));
            window.location.href = `/dashboard/fire-alarms/${inspectionId}/items`;
        } catch (error) {
            alert(error.message || 'Save failed');
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = originalHtml;
        }
    });

    window.logout = function () {
        fetch(`${API_URL}/auth/logout`, {
            method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        }).finally(() => { localStorage.clear(); window.location.href = '/'; });
    };

    loadPage();
})();
</script>
@endsection
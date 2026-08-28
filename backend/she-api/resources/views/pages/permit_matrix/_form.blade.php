<div class="p-4 md:p-8">
    <div class="mb-6">
        <a href="/dashboard/permit-matrix" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-3">{{ $mode === 'create' ? 'Tambah' : 'Edit' }} Permit Matrix</h1>
        <p class="text-gray-600 mt-1">Field bertanda <span class="text-red-600">*</span> wajib diisi.</p>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3" role="alert"></div>

    <form id="permitForm" class="bg-white rounded-xl shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            @php
                $inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent';
            @endphp
            <div>
                <label for="permit_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Permit <span class="text-red-600">*</span></label>
                <input id="permit_date" name="permit_date" type="date" required class="{{ $inputClass }}">
                <p data-error="permit_date" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="inspector_id" class="block text-sm font-medium text-gray-700 mb-1">Nama Inspector <span class="text-red-600">*</span></label>
                <select id="inspector_id" name="inspector_id" required class="{{ $inputClass }}"><option value="">Pilih Inspector</option></select>
                <p data-error="inspector_id" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="permit_number" class="block text-sm font-medium text-gray-700 mb-1">No. Permit <span class="text-red-600">*</span></label>
                <input id="permit_number" name="permit_number" maxlength="255" required class="{{ $inputClass }}">
                <p data-error="permit_number" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="permit_type_id" class="block text-sm font-medium text-gray-700 mb-1">Type Permit <span class="text-red-600">*</span></label>
                <select id="permit_type_id" name="permit_type_id" required class="{{ $inputClass }}"><option value="">Pilih Type Permit</option></select>
                <p data-error="permit_type_id" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="supervision_area_id" class="block text-sm font-medium text-gray-700 mb-1">Area Pengawasan <span class="text-red-600">*</span></label>
                <select id="supervision_area_id" name="supervision_area_id" required class="{{ $inputClass }}"><option value="">Pilih Area Pengawasan</option></select>
                <p data-error="supervision_area_id" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="main_area_id" class="block text-sm font-medium text-gray-700 mb-1">Main Area <span class="text-red-600">*</span></label>
                <select id="main_area_id" name="main_area_id" required class="{{ $inputClass }}"><option value="">Pilih Main Area</option></select>
                <p data-error="main_area_id" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="sub_area_id" class="block text-sm font-medium text-gray-700 mb-1">Sub Area <span class="text-red-600">*</span></label>
                <select id="sub_area_id" name="sub_area_id" required class="{{ $inputClass }}"><option value="">Pilih Sub Area</option></select>
                <p class="text-xs text-gray-500 mt-1">Mapping ke Main Area dapat dikonfigurasi pada master Sub Area.</p>
                <p data-error="sub_area_id" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="section_equipment" class="block text-sm font-medium text-gray-700 mb-1">Section / Equipment <span class="text-red-600">*</span></label>
                <input id="section_equipment" name="section_equipment" maxlength="255" required class="{{ $inputClass }}">
                <p data-error="section_equipment" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div class="md:col-span-2">
                <label for="job_performance" class="block text-sm font-medium text-gray-700 mb-1">Job Performance <span class="text-red-600">*</span></label>
                <textarea id="job_performance" name="job_performance" rows="4" maxlength="65535" required class="{{ $inputClass }}"></textarea>
                <p data-error="job_performance" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="authorized_craftman" class="block text-sm font-medium text-gray-700 mb-1">Authorized Craftman <span class="text-red-600">*</span></label>
                <input id="authorized_craftman" name="authorized_craftman" maxlength="255" required class="{{ $inputClass }}">
                <p data-error="authorized_craftman" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="authorized_facility" class="block text-sm font-medium text-gray-700 mb-1">Authorized Facility <span class="text-red-600">*</span></label>
                <input id="authorized_facility" name="authorized_facility" maxlength="255" required class="{{ $inputClass }}">
                <p data-error="authorized_facility" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div class="md:col-span-2">
                <label for="contractor_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kontraktor <span class="text-red-600">*</span></label>
                <input id="contractor_name" name="contractor_name" maxlength="255" required class="{{ $inputClass }}">
                <p data-error="contractor_name" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div class="md:col-span-2">
                <label for="work_description" class="block text-sm font-medium text-gray-700 mb-1">Uraian Pekerjaan <span class="text-red-600">*</span></label>
                <textarea id="work_description" name="work_description" rows="4" maxlength="65535" required class="{{ $inputClass }}"></textarea>
                <p data-error="work_description" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div class="md:col-span-2">
                <label for="permit_findings" class="block text-sm font-medium text-gray-700 mb-1">Temuan Terkait Safe Work Permit (Jika Ada)</label>
                <textarea id="permit_findings" name="permit_findings" rows="4" maxlength="65535" class="{{ $inputClass }}"></textarea>
                <p class="text-xs text-gray-500 mt-1">Boleh dikosongkan jika tidak ada temuan.</p>
                <p data-error="permit_findings" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mt-7 pt-5 border-t">
            <button id="saveButton" type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
            <a href="/dashboard/permit-matrix" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">Batal</a>
        </div>
    </form>
</div>

<script>
const token = localStorage.getItem('token');
const mode = @json($mode);
const inspectionId = @json($inspectionId);
const authHeaders = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
let subAreas = [];

if (!token) window.location.href = '/';

const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

function showMessage(text) {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = 'mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800';
}

function fillSelect(id, rows, label = row => row.name) {
    const select = document.getElementById(id);
    rows.forEach(row => select.insertAdjacentHTML('beforeend', `<option value="${row.id}">${escapeHtml(label(row))}</option>`));
}

function renderSubAreas(selectedId = '') {
    const mainAreaId = Number(document.getElementById('main_area_id').value);
    const mappedRowsExist = subAreas.some(row => row.main_area_id !== null);
    const visibleRows = mappedRowsExist
        ? subAreas.filter(row => row.main_area_id === null || Number(row.main_area_id) === mainAreaId)
        : subAreas;
    const select = document.getElementById('sub_area_id');
    select.innerHTML = '<option value="">Pilih Sub Area</option>';
    fillSelect('sub_area_id', visibleRows);
    select.value = selectedId ? String(selectedId) : '';
}

function fillForm(data) {
    Object.entries(data).forEach(([key, value]) => {
        const field = document.getElementById(key);
        if (field && key !== 'sub_area_id') field.value = value ?? '';
    });
    renderSubAreas(data.sub_area_id);
}

function clearErrors() {
    document.querySelectorAll('[data-error]').forEach(element => {
        element.textContent = '';
        element.classList.add('hidden');
    });
}

function showErrors(errors) {
    Object.entries(errors || {}).forEach(([field, messages]) => {
        const element = document.querySelector(`[data-error="${field}"]`);
        if (element) {
            element.textContent = messages[0];
            element.classList.remove('hidden');
        }
    });
}

async function initForm() {
    if (mode === 'edit') {
        const profileResponse = await fetch('/api/auth/me', {headers: authHeaders});
        if (profileResponse.status === 401) return window.location.href = '/';
        if (!profileResponse.ok) return showMessage('Profil pengguna gagal dimuat.');
        const user = await profileResponse.json();
        if (user.role !== 'super_admin' && user.role !== 'admin') {
            document.getElementById('permitForm').classList.add('hidden');
            return showMessage('Anda tidak memiliki izin untuk mengedit data ini.');
        }
        localStorage.setItem('user', JSON.stringify(user));
    }

    const masterResponse = await fetch('/api/safe-work-permit-inspections/master-data', {headers: authHeaders});
    if (masterResponse.status === 401) return window.location.href = '/';
    if (!masterResponse.ok) return showMessage('Master data tidak dapat dimuat atau Anda tidak memiliki izin.');
    const master = await masterResponse.json();
    subAreas = master.sub_areas;
    fillSelect('inspector_id', master.inspectors);
    fillSelect('permit_type_id', master.permit_types);
    fillSelect('supervision_area_id', master.supervision_areas, row => row.code);
    fillSelect('main_area_id', master.main_areas);
    renderSubAreas();

    if (mode === 'edit') {
        const response = await fetch(`/api/safe-work-permit-inspections/${inspectionId}`, {headers: authHeaders});
        if (!response.ok) return showMessage('Data Permit Matrix tidak dapat dimuat.');
        fillForm((await response.json()).data);
    }
}

document.getElementById('main_area_id').addEventListener('change', () => renderSubAreas());
document.getElementById('permitForm').addEventListener('submit', async event => {
    event.preventDefault();
    clearErrors();
    const button = document.getElementById('saveButton');
    button.disabled = true;
    button.classList.add('opacity-60');

    const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
    payload.permit_number = payload.permit_number.trim();
    const url = mode === 'create' ? '/api/safe-work-permit-inspections' : `/api/safe-work-permit-inspections/${inspectionId}`;
    const response = await fetch(url, {
        method: mode === 'create' ? 'POST' : 'PUT',
        headers: {...authHeaders, 'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
    });
    const data = await response.json();

    button.disabled = false;
    button.classList.remove('opacity-60');
    if (response.status === 422) {
        showErrors(data.errors);
        showMessage('Periksa kembali field yang belum valid.');
        return;
    }
    if (!response.ok) {
        showMessage(data.message || 'Data gagal disimpan.');
        return;
    }

    sessionStorage.setItem('permitMatrixMessage', data.message);
    window.location.href = '/dashboard/permit-matrix';
});

initForm().catch(() => showMessage('Terjadi kesalahan saat memuat form.'));
</script>

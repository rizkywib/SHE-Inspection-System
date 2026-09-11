<div class="p-4 md:p-8">
    <div class="mb-6">
        <a href="/dashboard/safety-talk-trainings" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-3">{{ $mode === 'create' ? 'Tambah' : 'Edit' }} Safety Talk / Training On Site</h1>
        <p class="text-gray-600 mt-1">Field bertanda <span class="text-red-600">*</span> wajib diisi.</p>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3" role="alert"></div>
    <form id="trainingForm" class="bg-white rounded-xl shadow p-6" enctype="multipart/form-data">
        @php($inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            <div>
                <label for="speaker_id" class="block text-sm font-medium text-gray-700 mb-1">Pembicara Materi <span class="text-red-600">*</span></label>
                <select id="speaker_id" name="speaker_id" required class="{{ $inputClass }}"><option value="">Pilih Pembicara</option></select>
                <p data-error="speaker_id" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="implementation_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pelaksanaan <span class="text-red-600">*</span></label>
                <input id="implementation_date" name="implementation_date" type="date" required class="{{ $inputClass }}">
                <p data-error="implementation_date" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div class="md:col-span-2">
                <label for="topic" class="block text-sm font-medium text-gray-700 mb-1">Topik / Materi Safety Talk / Training On Site <span class="text-red-600">*</span></label>
                <textarea id="topic" name="topic" rows="5" maxlength="1000" required class="{{ $inputClass }}"></textarea>
                <div class="flex justify-between mt-1"><p data-error="topic" class="hidden text-red-600 text-sm"></p><span id="topicCount" class="text-xs text-gray-500 ml-auto">0 / 1000</span></div>
            </div>
            @foreach ([
                'ecogreen_participants' => 'Jumlah Peserta Ecogreen',
                'outsourcing_participants' => 'Jumlah Peserta Outsourcing',
                'contractor_participants' => 'Jumlah Peserta Contractor',
            ] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }} <span class="text-red-600">*</span></label>
                    <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" value="0" required class="{{ $inputClass }} participant-input">
                    <p data-error="{{ $field }}" class="hidden text-red-600 text-sm mt-1"></p>
                </div>
            @endforeach
            <div>
                <label for="total_participants" class="block text-sm font-medium text-gray-700 mb-1">Total Peserta</label>
                <input id="total_participants" value="0" readonly class="{{ $inputClass }} bg-gray-100 font-semibold">
                <p class="text-xs text-gray-500 mt-1">Dihitung otomatis.</p>
            </div>
            <div>
                <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">Durasi Penyampaian <span class="text-red-600">*</span></label>
                <div class="flex"><input id="duration_minutes" name="duration_minutes" type="number" min="1" required class="w-full border border-gray-300 rounded-l-lg px-3 py-2"><span class="inline-flex items-center bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg px-3 text-sm">menit</span></div>
                <p data-error="duration_minutes" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="implementation_area" class="block text-sm font-medium text-gray-700 mb-1">Area Pelaksanaan <span class="text-red-600">*</span></label>
                <select id="implementation_area" name="implementation_area" required class="{{ $inputClass }}">
                    <option value="">Pilih Area</option>
                    @foreach (range(1, 6) as $area)<option value="{{ $area }}">Area {{ $area }}</option>@endforeach
                </select>
                <p data-error="implementation_area" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div class="md:col-span-2">
                <label for="activity_photo" class="block text-sm font-medium text-gray-700 mb-1">Foto Penyampaian <span class="text-red-600">{{ $mode === 'create' ? '*' : '' }}</span></label>
                <input id="activity_photo" name="activity_photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" {{ $mode === 'create' ? 'required' : '' }} class="{{ $inputClass }} bg-white">
                <p class="text-xs text-gray-500 mt-1">JPG, JPEG, atau PNG. Maksimal 5 MB. Kosongkan saat edit jika foto tidak diganti.</p>
                <p data-error="activity_photo" class="hidden text-red-600 text-sm mt-1"></p>
                <div id="photoPreview" class="mt-4 hidden">
                    <img id="previewImage" alt="Preview foto kegiatan" class="max-h-72 rounded-lg border object-contain">
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-3 mt-7 pt-5 border-t">
            <button id="saveButton" type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg"><i class="fas fa-save mr-2"></i>Simpan</button>
            <a href="/dashboard/safety-talk-trainings" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">Batal</a>
        </div>
    </form>
</div>

<script>
const token = localStorage.getItem('token');
const mode = @json($mode);
const trainingId = @json($trainingId);
const authHeaders = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
if (!token) window.location.href = '/';
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

function showMessage(text) {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = 'mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800';
}
function calculateTotal() {
    const fields = ['ecogreen_participants', 'outsourcing_participants', 'contractor_participants'];
    document.getElementById('total_participants').value = fields.reduce((total, field) => total + Math.max(0, Number(document.getElementById(field).value) || 0), 0);
}
function showPreview(url) {
    document.getElementById('previewImage').src = url;
    document.getElementById('photoPreview').classList.remove('hidden');
}
function clearErrors() {
    document.querySelectorAll('[data-error]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
}
function showErrors(errors) {
    Object.entries(errors || {}).forEach(([field, messages]) => {
        const el = document.querySelector(`[data-error="${field}"]`);
        if (el) { el.textContent = messages[0]; el.classList.remove('hidden'); }
    });
}
function fillForm(data) {
    ['speaker_id','implementation_date','topic','ecogreen_participants','outsourcing_participants','contractor_participants','duration_minutes','implementation_area'].forEach(field => {
        document.getElementById(field).value = data[field] ?? '';
    });
    if (data.activity_photo_url) showPreview(data.activity_photo_url);
    calculateTotal();
    document.getElementById('topicCount').textContent = `${data.topic?.length || 0} / 1000`;
}

async function initForm() {
    const profileResponse = await fetch('/api/auth/me', {headers: authHeaders});
    if (profileResponse.status === 401) { localStorage.clear(); return window.location.href = '/'; }
    if (!profileResponse.ok) return showMessage('Profil pengguna gagal dimuat.');
    const user = await profileResponse.json();
    const permissions = Array.isArray(user.permissions) ? user.permissions : [];
    const needed = mode === 'create' ? 'safety-talk-training.create' : null;
    if (mode === 'create' && user.role !== 'super_admin' && !permissions.includes(needed)) {
        document.getElementById('trainingForm').classList.add('hidden');
        return showMessage('Anda tidak memiliki izin untuk tindakan ini.');
    }
    localStorage.setItem('user', JSON.stringify(user));

    const masterResponse = await fetch('/api/safety-talk-trainings/master-data', {headers: authHeaders});
    if (!masterResponse.ok) return showMessage('Master pembicara gagal dimuat.');
    const master = await masterResponse.json();
    const select = document.getElementById('speaker_id');
    master.speakers.forEach(row => select.insertAdjacentHTML('beforeend', `<option value="${row.id}">${escapeHtml(row.name)}</option>`));

    if (mode === 'edit') {
        const response = await fetch(`/api/safety-talk-trainings/${trainingId}`, {headers: authHeaders});
        if (!response.ok) return showMessage('Data Safety Talk tidak dapat dimuat.');
        const data = (await response.json()).data;
        const isAdmin = user.role === 'super_admin' || user.role === 'admin';
        if (!isAdmin && String(data.created_by) !== String(user.id)) {
            document.getElementById('trainingForm').classList.add('hidden');
            return showMessage('Anda tidak memiliki izin untuk mengedit data ini.');
        }
        fillForm(data);
    }
}

document.querySelectorAll('.participant-input').forEach(input => input.addEventListener('input', calculateTotal));
document.getElementById('topic').addEventListener('input', event => document.getElementById('topicCount').textContent = `${event.target.value.length} / 1000`);
document.getElementById('activity_photo').addEventListener('change', event => {
    const file = event.target.files[0];
    if (file) showPreview(URL.createObjectURL(file));
});
document.getElementById('trainingForm').addEventListener('submit', async event => {
    event.preventDefault();
    clearErrors();
    const button = document.getElementById('saveButton');
    button.disabled = true;
    button.classList.add('opacity-60');
    const formData = new FormData(event.currentTarget);
    if (mode === 'edit') formData.append('_method', 'PUT');
    const response = await fetch(mode === 'create' ? '/api/safety-talk-trainings' : `/api/safety-talk-trainings/${trainingId}`, {
        method: 'POST',
        headers: authHeaders,
        body: formData,
    });
    const data = await response.json();
    button.disabled = false;
    button.classList.remove('opacity-60');
    if (response.status === 422) {
        showErrors(data.errors);
        return showMessage('Periksa kembali field yang belum valid.');
    }
    if (!response.ok) return showMessage(data.message || 'Data gagal disimpan.');
    sessionStorage.setItem('safetyTalkMessage', data.message);
    window.location.href = '/dashboard/safety-talk-trainings';
});
initForm().catch(() => showMessage('Terjadi kesalahan saat memuat form.'));
</script>

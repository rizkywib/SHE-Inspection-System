@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
<div class="p-4 md:p-8">
    <div class="mb-6">
        <a href="/dashboard" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-3">Edit Profil</h1>
        <p class="text-gray-600 mt-1">Perbarui informasi akun dan tanda tangan Anda.</p>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3" role="alert"></div>

    <form id="profileForm" class="bg-white rounded-xl shadow p-6 max-w-4xl" enctype="multipart/form-data">
        @php($inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-600">*</span></label>
                <input id="name" name="name" required maxlength="255" class="{{ $inputClass }}">
                <p data-error="name" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-600">*</span></label>
                <input id="username" name="username" required maxlength="255" autocomplete="username" class="{{ $inputClass }}">
                <p data-error="username" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                <input id="phone" name="phone" type="tel" maxlength="30" placeholder="Contoh: 081234567890" class="{{ $inputClass }}">
                <p data-error="phone" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                <input id="position" name="position" maxlength="255" class="{{ $inputClass }}">
                <p data-error="position" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <input id="role" readonly class="{{ $inputClass }} bg-gray-100 text-gray-500">
                <p class="text-xs text-gray-500 mt-1">Role hanya dapat diubah melalui administrasi Users.</p>
            </div>
            <div class="md:col-span-2 border-t pt-5 mt-1">
                <h2 class="text-lg font-semibold text-gray-900">Ganti Password</h2>
                <p class="text-sm text-gray-500 mt-1">Kosongkan seluruh field password jika tidak ingin menggantinya.</p>
            </div>
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $inputClass }}">
                <p data-error="current_password" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div></div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" class="{{ $inputClass }}">
                <p data-error="password" class="hidden text-red-600 text-sm mt-1"></p>
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" class="{{ $inputClass }}">
            </div>
            <div class="md:col-span-2 border-t pt-5 mt-1">
                <label for="signature" class="block text-sm font-medium text-gray-700 mb-1">Tanda Tangan</label>
                <input id="signature" name="signature" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="{{ $inputClass }} bg-white">
                <p class="text-xs text-gray-500 mt-1">JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB. Kosongkan jika tidak diganti.</p>
                <p data-error="signature" class="hidden text-red-600 text-sm mt-1"></p>
                <div id="signaturePreview" class="hidden mt-4">
                    <img id="signatureImage" alt="Preview tanda tangan" class="max-h-40 max-w-md rounded-lg border bg-white p-3 object-contain">
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mt-7 pt-5 border-t">
            <button id="saveButton" type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>Simpan Profil
            </button>
            <a href="/dashboard" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">Batal</a>
        </div>
    </form>
</div>

<script>
const token = localStorage.getItem('token');
const authHeaders = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
if (!token) window.location.href = '/';

function showMessage(text, type = 'error') {
    const box = document.getElementById('messageBox');
    box.textContent = text;
    box.className = `mb-5 rounded-lg border px-4 py-3 ${type === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800'}`;
}
function imageUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (/^(https?:|data:|blob:)/.test(value)) return value;
    return `/${value.replace(/^\/+/, '').replace(/^public\//, '')}`;
}
function showSignature(url) {
    if (!url) return;
    document.getElementById('signatureImage').src = url;
    document.getElementById('signaturePreview').classList.remove('hidden');
}
function clearErrors() {
    document.querySelectorAll('[data-error]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
}
function showErrors(errors) {
    Object.entries(errors || {}).forEach(([field, messages]) => {
        const element = document.querySelector(`[data-error="${field}"]`);
        if (element) { element.textContent = messages[0]; element.classList.remove('hidden'); }
    });
}
function fillProfile(user) {
    ['name', 'username', 'phone', 'position', 'role'].forEach(field => {
        document.getElementById(field).value = user[field] || '';
    });
    showSignature(imageUrl(user.signature_path));
}
async function loadProfile() {
    const response = await fetch('/api/auth/me', {headers: authHeaders});
    if (response.status === 401) {
        localStorage.clear();
        return window.location.href = '/';
    }
    if (!response.ok) return showMessage('Profil gagal dimuat.');
    const user = await response.json();
    localStorage.setItem('user', JSON.stringify(user));
    fillProfile(user);
}
document.getElementById('signature').addEventListener('change', event => {
    const file = event.target.files[0];
    if (file) showSignature(URL.createObjectURL(file));
});
document.getElementById('profileForm').addEventListener('submit', async event => {
    event.preventDefault();
    clearErrors();
    const button = document.getElementById('saveButton');
    button.disabled = true;
    button.classList.add('opacity-60');
    const formData = new FormData(event.currentTarget);
    formData.append('_method', 'PUT');

    const response = await fetch('/api/auth/profile', {
        method: 'POST',
        headers: authHeaders,
        body: formData,
    });
    const data = await response.json();
    button.disabled = false;
    button.classList.remove('opacity-60');
    if (response.status === 422) {
        showErrors(data.errors);
        return showMessage('Periksa kembali data profil.');
    }
    if (!response.ok) return showMessage(data.message || 'Profil gagal diperbarui.');

    localStorage.setItem('user', JSON.stringify(data.user));
    document.getElementById('userName').textContent = data.user.name;
    document.getElementById('userRole').textContent = String(data.user.role || '').replaceAll('_', ' ');
    fillProfile(data.user);
    event.currentTarget.querySelectorAll('input[type="password"]').forEach(input => input.value = '');
    document.getElementById('signature').value = '';
    showMessage(data.message, 'success');
    window.scrollTo({top: 0, behavior: 'smooth'});
});
loadProfile().catch(() => showMessage('Terjadi kesalahan saat memuat profil.'));
</script>
@endsection

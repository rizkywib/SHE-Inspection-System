@extends('layouts.app')

@section('title', 'Fire Extinguisher Inspections')
@section('nav-fire-extinguishers', 'active')

@section('content')
<div class="p-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Fire Extinguisher Inspections</h1>
            <p class="text-gray-600 mt-1">List data inspection berdasarkan lokasi APAR.</p>
        </div>
        <a href="/dashboard/fire-extinguishers/create" class="btn-primary inline-flex items-center justify-center text-white px-6 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Inspection
        </a>
    </div>

    <div id="inspection-list" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900">List Data Fire Extinguisher Inspections</h2>
                <p class="text-sm text-gray-500 mt-1">Klik lokasi untuk melihat seluruh Fire Extinguisher Item.</p>
            </div>
            <div class="flex items-center gap-4">
                <span id="inspectionCount" class="text-sm font-semibold text-gray-600">Memuat data...</span>
                <button type="button" onclick="loadExtinguishers()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-sync-alt mr-1"></i>Refresh Data
                </button>
            </div>
        </div>

        <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Show</label>
                <select id="pageSize" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-500">entries</span>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Search:</label>
                <input id="searchFilter" type="text" placeholder="Search lokasi, inspector, tanggal..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspected By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="extinguisherTable" class="bg-white divide-y divide-gray-200">
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginationControls" class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div id="paginationInfo" class="text-sm text-gray-600"></div>
            <div class="flex items-center gap-2">
                <button id="prevPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <span id="pageNumbers" class="text-sm text-gray-700"></span>
                <button id="nextPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const API_URL = '/api';
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    let inspections = [];
    let currentPage = 1;
    let pageSize = 25;
    let searchFilter = '';

    if (!token) {
        window.location.href = '/';
        return;
    }
    document.getElementById('userName').textContent = user.name || 'User';

    function canModify(record) {
        if (user.role === 'admin' || user.role === 'super_admin') return true;
        return String(record.inspector_id) === String(user.id);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function formatDate(value) {
        return value ? String(value).slice(0, 10) : '-';
    }

    function boolValue(value) {
        return value === true || value === 1 || value === '1';
    }

    function handleUnauthorized(response) {
        if (response.status !== 401) return false;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/';
        return true;
    }

    async function loadExtinguishers() {
        const tbody = document.getElementById('extinguisherTable');
        const countElement = document.getElementById('inspectionCount');
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>';
        countElement.textContent = 'Memuat data...';

        try {
            const response = await fetch(`${API_URL}/fire-extinguishers?_=${Date.now()}`, {
                cache: 'no-store',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });
            if (handleUnauthorized(response)) return;
            const json = await response.json();
            if (!response.ok) throw new Error(json.message || 'Gagal memuat data inspection');

            inspections = Array.isArray(json.data) ? json.data : [];
            countElement.textContent = `${inspections.length} data inspection`;
            renderTable();
        } catch (error) {
            countElement.textContent = 'Gagal memuat data';
            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-600">${escapeHtml(error.message)}</td></tr>`;
        }
    }

    function matchesFilters(inspection) {
        const search = searchFilter.toLowerCase().trim();
        if (!search) return true;
        return String(inspection.id).includes(search) ||
            (inspection.location?.name || inspection.location_id || '').toLowerCase().includes(search) ||
            (inspection.inspector?.name || inspection.inspector_id || '').toLowerCase().includes(search) ||
            (formatDate(inspection.inspection_date) || '').includes(search) ||
            (inspection.reference_no || '').toLowerCase().includes(search);
    }

    function renderTable() {
        const tbody = document.getElementById('extinguisherTable');

        const filtered = inspections.filter(matchesFilters);
        const totalItems = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * pageSize;
        const end = Math.min(start + pageSize, totalItems);
        const pageItems = filtered.slice(start, end);

        if (totalItems === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada data inspection</td></tr>';
        } else {
            const grouped = Object.values(pageItems.reduce((result, inspection) => {
                const locationId = inspection.location_id || 0;
                const key = String(locationId);
                if (!result[key]) {
                    result[key] = {
                        locationId,
                        locationName: inspection.location?.name || `Location #${locationId}`,
                        inspections: []
                    };
                }
                result[key].inspections.push(inspection);
                return result;
            }, {})).sort((a, b) => a.locationName.localeCompare(b.locationName));

            let rowNumber = start;
            tbody.innerHTML = grouped.map(group => group.inspections.map((inspection, groupIndex) => {
                rowNumber += 1;
                const inspector = inspection.inspector?.name || '-';
                const itemUrl = `/dashboard/fire-extinguishers/location/${group.locationId}/items`;
                return `<tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-900 text-center">${rowNumber}</td>
                    ${groupIndex === 0 ? `<td rowspan="${group.inspections.length}" class="px-6 py-4 text-sm font-semibold align-top bg-blue-50 border-r border-blue-100">
                        <a href="${itemUrl}" class="text-blue-600 hover:text-blue-800 hover:underline">
                            <i class="fas fa-map-marker-alt mr-2"></i>${escapeHtml(group.locationName)}
                        </a>
                        <div class="text-xs font-normal text-blue-600 mt-1">${group.inspections.length} inspection(s)</div>
                    </td>` : ''}
                    <td class="px-6 py-4 text-sm text-gray-600">${escapeHtml(formatDate(inspection.inspection_date))}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(inspector)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                        <button onclick="exportItem(${inspection.id})" class="text-green-600 hover:text-green-800 mr-3 font-medium">
                            <i class="fas fa-file-excel mr-1"></i>Export
                        </button>
                        <button onclick="printItem(${inspection.id})" class="text-purple-600 hover:text-purple-800 mr-3 font-medium">
                            <i class="fas fa-print mr-1"></i>Print
                        </button>
                        <button onclick="signItem(${inspection.id})" ${inspection.signed_at ? 'disabled' : ''} class="mr-3 font-medium ${inspection.signed_at ? 'text-gray-400 cursor-not-allowed' : 'text-blue-600 hover:text-blue-800'}">
                            <i class="fas fa-signature mr-1"></i>${inspection.signed_at ? 'Signed' : 'Signature'}
                        </button>
                        ${canModify(inspection) ? `<button onclick="deleteItem(${inspection.id})" class="text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>` : ''}
                    </td>
                </tr>`;
            }).join('')).join('');
        }

        const info = document.getElementById('paginationInfo');
        if (totalItems === 0) {
            info.textContent = 'Showing 0 to 0 of 0 entries';
        } else {
            info.textContent = `Showing ${start + 1} to ${end} of ${totalItems} entries`;
        }

        document.getElementById('pageNumbers').textContent = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }

    async function inspectionDetail(id) {
        const response = await fetch(`${API_URL}/fire-extinguishers/${id}`, {
            cache: 'no-store',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (handleUnauthorized(response)) throw new Error('Session expired');
        const json = await response.json();
        if (!response.ok) throw new Error(json.message || 'Gagal memuat inspection');
        return json.data;
    }

    function conditionMark(value) {
        return boolValue(value) ? '<span class="check">&#10003;</span>' : '<span class="check">X</span>';
    }

    function signatureDate(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Asia/Bangkok'
        }).format(date);
    }

    function printLogoHtml() {
        return '<img src="/images/ecogreen-logo-print.png" class="print-company-logo" alt="Ecogreen Oleochemicals">';
    }

    function signatureImage(path) {
        if (!path) return '<div class="print-sign-space"></div>';
        const url = `/${String(path).replace(/^\/+/, '')}`;
        return `<img src="${escapeHtml(url)}" class="print-sign-image" alt="Signature">`;
    }

    function inspectionReport(detail) {
        const locationName = detail.location?.name || detail.location_id || '-';
        const inspectorName = detail.inspector?.name || detail.inspector_id || '-';
        const inspectorPosition = detail.inspector?.position || 'Safety Inspector';
        const signer = detail.signer || null;
        const signerName = signer?.name || 'Belum di ttd';
        const signerPosition = signer?.position || 'Safety Supervisor';
        const signedDate = signer ? signatureDate(detail.signed_at) : '';
        const itemRows = (detail.items || []).map((item, index) => `<tr>
            <td class="center">${index + 1}</td>
            <td>${escapeHtml(item.name || '-')}</td>
            <td>${escapeHtml(item.type || '-')}</td>
            <td>${escapeHtml(item.location_detail || '-')}</td>
            <td class="center">${conditionMark(item.pressure_condition)}</td>
            <td class="center">${conditionMark(item.seal_condition)}</td>
            <td class="center">${conditionMark(item.nozzle_condition)}</td>
            <td>${escapeHtml(item.remark || '-')}</td>
        </tr>`).join('');

        return `<div class="print-page">
            <div class="print-top">
                <div class="print-brand">${printLogoHtml()}<span>PT. Ecogreen Oleochemicals</span></div>
                <div class="print-doc-code">EOB-Saf-004 Rev. 4 31/12/2018</div>
            </div>
            <div class="print-meta">
                <div>Batam Plant</div>
                <div>Location : <strong>${escapeHtml(locationName)}</strong></div>
                <div>Date Inspected : <strong>${escapeHtml(formatDate(detail.inspection_date))}</strong></div>
            </div>
            <table class="print-table">
                <thead>
                    <tr class="print-title-row"><th colspan="8">FIRE EXTINGUISHER MONTHLY INSPECTION</th></tr>
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">Name</th>
                        <th rowspan="2">Type</th>
                        <th rowspan="2">Specified Location</th>
                        <th colspan="3">EXTINGUISHING UNITS*)</th>
                        <th rowspan="2">Remark</th>
                    </tr>
                    <tr>
                        <th>PRESSURE</th><th>SEAL</th><th>NOZZLE</th>
                    </tr>
                </thead>
                <tbody>${itemRows || '<tr><td colspan="8" class="center">No items</td></tr>'}</tbody>
            </table>
            <div class="print-notes">
                <div>Note :</div>
                <div><div>&#8730; = Function Well</div><div>X = Need Correction</div></div>
            </div>
            <div class="print-signatures">
                <div class="print-sign-block">
                    <div>Inspected by,</div>
                    ${signatureImage(detail.inspector?.signature_path)}
                    <div class="print-sign-name">${escapeHtml(inspectorName)}</div>
                    <div>${escapeHtml(inspectorPosition)}</div>
                </div>
                <div class="print-sign-block right">
                    <div>Noted by,</div>
                    <div class="print-signature-line">
                        ${signatureImage(signer?.signature_path)}
                        ${signedDate ? `<span class="print-sign-date">${escapeHtml(signedDate)}</span>` : ''}
                    </div>
                    <div class="print-sign-name">${escapeHtml(signerName)}</div>
                    <div>${escapeHtml(signerPosition)}</div>
                </div>
            </div>
        </div>`;
    }

    function reportStyles() {
        return `@page{size:A4 landscape;margin:8mm}*{box-sizing:border-box}body{margin:0;padding:8mm;background:#e5e7eb}.print-page{font-family:Arial,sans-serif;color:#000;width:100%;font-size:11px;line-height:1.25;background:#fff}.print-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px}.print-brand{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700}.print-company-logo{width:38px;height:50px;object-fit:contain;display:block}.print-doc-code{font-size:11px;text-align:right}.print-meta{margin-bottom:20px}.print-meta div{margin:4px 0}.print-table{border-collapse:collapse;width:100%;table-layout:fixed;font-size:10px}.print-table th,.print-table td{border:1px solid #000;padding:3px 4px;vertical-align:middle}.print-table th{font-weight:700;text-align:center}.print-title-row th{background:#000;color:#fff;font-size:14px;padding:4px 0}.center{text-align:center}.check{font-size:17px;font-weight:700;line-height:1}.print-notes{display:flex;justify-content:flex-end;gap:70px;margin-top:4px}.print-signatures{display:flex;justify-content:space-between;margin-top:54px}.print-sign-block{width:260px}.print-sign-block.right{margin-right:38px}.print-sign-image{height:42px;max-width:120px;object-fit:contain;display:block;margin:12px 0 4px 8px}.print-signature-line{display:flex;align-items:flex-end;gap:8px;min-height:58px}.print-sign-date{font-size:8px;margin-bottom:5px;white-space:nowrap}.print-sign-space{height:58px}.print-sign-name{font-weight:700;text-decoration:underline}`;
    }

    async function exportItem(id) {
        try {
            const detail = await inspectionDetail(id);
            const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"><style>${reportStyles()}</style></head><body>${inspectionReport(detail)}</body></html>`;
            const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `fire_extinguisher_inspection_${detail.reference_no || id}.xls`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } catch (error) {
            alert(error.message || 'Export failed');
        }
    }

    async function printItem(id) {
        const preview = window.open('', '_blank');
        if (!preview) {
            alert('Pop-up diblokir. Izinkan pop-up untuk membuka print preview.');
            return;
        }
        preview.document.write('<p style="font-family:Arial;padding:24px">Preparing print preview...</p>');
        try {
            const detail = await inspectionDetail(id);
            preview.document.open();
            preview.document.write(`<!DOCTYPE html><html><head><title>Fire Extinguisher Inspection</title><style>${reportStyles()}@media print{body{padding:0}}</style></head><body>${inspectionReport(detail)}</body></html>`);
            preview.document.close();
            preview.focus();
            preview.print();
        } catch (error) {
            preview.document.body.innerHTML = `<p style="color:#b91c1c;font-family:Arial;padding:24px">${escapeHtml(error.message)}</p>`;
        }
    }

    async function signItem(id) {
        const inspection = inspections.find(row => Number(row.id) === Number(id));
        if (!inspection || inspection.signed_at) return;
        if (!confirm('Add your signature to this inspection?')) return;
        const response = await fetch(`${API_URL}/fire-extinguishers/${id}/sign`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (!response.ok) {
            const json = await response.json().catch(() => ({}));
            alert(json.message || 'Failed to sign inspection');
            return;
        }
        loadExtinguishers();
    }

    async function deleteItem(id) {
        if (!confirm('Delete this inspection?')) return;
        const response = await fetch(`${API_URL}/fire-extinguishers/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (!response.ok) {
            const json = await response.json().catch(() => ({}));
            alert(json.message || 'Delete failed');
            return;
        }
        loadExtinguishers();
    }

    function logout() {
        fetch(`${API_URL}/auth/logout`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        }).finally(() => {
            localStorage.clear();
            window.location.href = '/';
        });
    }

    window.loadExtinguishers = loadExtinguishers;
    window.exportItem = exportItem;
    window.printItem = printItem;
    window.signItem = signItem;
    window.deleteItem = deleteItem;
    window.logout = logout;

    document.getElementById('pageSize').addEventListener('change', function() {
        pageSize = parseInt(this.value);
        currentPage = 1;
        renderTable();
    });

    document.getElementById('searchFilter').addEventListener('input', function() {
        searchFilter = this.value;
        currentPage = 1;
        renderTable();
    });

    document.getElementById('prevPage').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });

    document.getElementById('nextPage').addEventListener('click', function() {
        const totalItems = inspections.filter(matchesFilters).length;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadExtinguishers, { once: true });
    } else {
        loadExtinguishers();
    }
})();
</script>
@endsection

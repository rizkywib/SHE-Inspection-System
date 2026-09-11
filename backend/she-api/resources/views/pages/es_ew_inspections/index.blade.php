@extends('layouts.app')

@section('title', 'ES&EW Inspection')
@section('nav-es-ew-inspections', 'active')

@section('content')
<div class="p-4 md:p-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">ES&amp;EW Inspection</h1>
            <p class="text-gray-600 mt-1">Emergency Shower &amp; Eye Wash Inspection</p>
        </div>
        <a href="/dashboard/es-ew-inspections/create" class="btn-primary text-white px-5 py-3 rounded-lg shadow-md">
            <i class="fas fa-plus mr-2"></i>New Inspection
        </a>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3"></div>
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input id="search" placeholder="Area, inspector, atau name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Show</label>
                <select id="per_page" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    @foreach (['No.', 'Area', 'Inspection Date', 'Inspected By', 'Actions'] as $heading)
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody id="tableBody" class="divide-y divide-gray-200"><tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading...</td></tr></tbody>
            </table>
        </div>
        <div id="pagination" class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-t px-5 py-4"></div>
    </div>
</div>
<script>
const token = localStorage.getItem('token');
const user = JSON.parse(localStorage.getItem('user') || '{}');
const canModify = row => user.role === 'admin' || user.role === 'super_admin' || String(row.inspector_id) === String(user.id);
const headers = {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'};
const params = new URLSearchParams(window.location.search);
if (!token) window.location.href = '/';
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const formatDate = value => value ? new Date(`${value}T00:00:00`).toLocaleDateString('id-ID') : '-';
function message(text, type='success') {
    const box=document.getElementById('messageBox'); box.textContent=text;
    box.className=`mb-5 rounded-lg border px-4 py-3 ${type==='success'?'bg-green-50 border-green-300 text-green-800':'bg-red-50 border-red-300 text-red-800'}`;
}
async function init() {
    const flash=sessionStorage.getItem('esEwMessage'); if(flash){message(flash);sessionStorage.removeItem('esEwMessage');}
    const masterResponse=await fetch('/api/es-ew/master-data',{headers});
    if(masterResponse.status===401){localStorage.clear();return window.location.href='/';}
    if(!masterResponse.ok)return message('Master data gagal dimuat.','error');
    const master=await masterResponse.json();
    if(params.has('search'))document.getElementById('search').value=params.get('search');
    if(params.has('per_page'))document.getElementById('per_page').value=params.get('per_page');
    loadData(params.get('page')||1);
}
async function loadData(page=1) {
    const query=new URLSearchParams(params);query.set('page',page);
    const response=await fetch(`/api/es-ew?${query}`,{headers}); if(!response.ok)return message('Data gagal dimuat.','error');
    render(await response.json());
}
function render(result) {
    const body=document.getElementById('tableBody');
    if(!result.data.length) body.innerHTML='<tr><td colspan="5" class="px-4 py-10 text-center text-gray-500"><i class="fas fa-inbox text-3xl block mb-3"></i>No ES&EW inspections found.</td></tr>';
    else body.innerHTML=result.data.map((row,index)=>`<tr class="hover:bg-gray-50">
        <td class="px-4 py-3 text-sm">${result.from+index}</td>
        <td class="px-4 py-3 text-sm font-medium"><a href="/dashboard/es-ew-inspections/${encodeURIComponent(row.id)}" class="text-blue-600 hover:text-blue-800 hover:underline" title="Open item detail">${escapeHtml(row.area?.name||'-')}</a></td>
        <td class="px-4 py-3 text-sm whitespace-nowrap">${formatDate(row.inspection_date)}</td>
        <td class="px-4 py-3 text-sm">${escapeHtml(row.inspector?.name||'-')}</td>
        <td class="px-4 py-3 text-sm whitespace-nowrap">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <button onclick="exportInspection(${row.id})" class="text-green-600 hover:text-green-800 font-medium" title="Export this inspection">
                    <i class="fas fa-file-excel mr-1"></i>Export
                </button>
                <button onclick="printInspection(${row.id})" class="text-purple-600 hover:text-purple-800 font-medium" title="Print this inspection">
                    <i class="fas fa-print mr-1"></i>Print
                </button>
                <button onclick="signInspection(${row.id})" ${row.signed_by ? 'disabled' : ''} class="${row.signed_by ? 'text-gray-400 cursor-not-allowed' : 'text-blue-600 hover:text-blue-800'} font-medium" title="${row.signed_by ? `Signed by ${escapeHtml(row.signer?.name || '-')}` : 'Sign this inspection'}">
                    <i class="fas fa-signature mr-1"></i>${row.signed_by ? 'Signed' : 'Signature'}
                </button>
                ${canModify(row) ? `<button onclick="removeInspection(${row.id})" class="text-red-600 hover:text-red-800 font-medium" title="Delete">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>` : ''}
            </div>
        </td>
    </tr>`).join('');
    document.getElementById('pagination').innerHTML=`<p class="text-sm text-gray-600">Showing ${result.from||0}–${result.to||0} of ${result.total}</p><div class="flex gap-2 items-center"><button ${result.current_page<=1?'disabled':''} onclick="goPage(${result.current_page-1})" class="px-3 py-2 border rounded disabled:opacity-40">Previous</button><span class="text-sm">Page ${result.current_page} / ${result.last_page}</span><button ${result.current_page>=result.last_page?'disabled':''} onclick="goPage(${result.current_page+1})" class="px-3 py-2 border rounded disabled:opacity-40">Next</button></div>`;
}
function goPage(page){params.set('page',page);window.location.search=params.toString();}
async function removeInspection(id){if(!confirm('Delete this ES&EW inspection and all items?'))return;const response=await fetch(`/api/es-ew/${id}`,{method:'DELETE',headers});const data=await response.json();if(!response.ok)return message(data.message||'Delete failed.','error');message(data.message);loadData(params.get('page')||1);}
@include('pages.es_ew_inspections._header_actions_script')
let searchTimer;
document.getElementById('search').addEventListener('input',function(){
    clearTimeout(searchTimer);
    searchTimer=setTimeout(()=>{
        if(this.value.trim())params.set('search',this.value.trim());else params.delete('search');
        params.delete('page');window.location.search=params.toString();
    },500);
});
document.getElementById('per_page').addEventListener('change',function(){
    params.set('per_page',this.value);params.delete('page');window.location.search=params.toString();
});
init().catch(()=>message('Terjadi kesalahan saat memuat halaman.','error'));
</script>
@endsection

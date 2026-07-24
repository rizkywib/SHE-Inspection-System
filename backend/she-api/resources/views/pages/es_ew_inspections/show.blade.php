@extends('layouts.app')
@section('title', 'ES&EW Inspection Detail')
@section('nav-es-ew-inspections', 'active')
@section('content')
<div class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div><a href="/dashboard/es-ew-inspections" class="text-blue-600"><i class="fas fa-arrow-left mr-2"></i>Back</a><h1 class="text-3xl font-bold text-gray-900 mt-3">ES&amp;EW Inspection Detail</h1></div>
        <div class="flex gap-3"><a href="/dashboard/es-ew-inspections/{{ $inspectionId }}/edit" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-lg"><i class="fas fa-edit mr-2"></i>Edit</a><button id="deleteButton" class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-lg"><i class="fas fa-trash mr-2"></i>Delete</button></div>
    </div>
    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3 bg-red-50 border-red-300 text-red-800"></div>
    <div id="detailCard" class="space-y-6"><div class="bg-white rounded-xl shadow p-6 text-gray-500">Loading...</div></div>
</div>
<script>
const token=localStorage.getItem('token'),inspectionId=@json($inspectionId),headers={'Authorization':`Bearer ${token}`,'Accept':'application/json'};
if(!token)window.location.href='/';
const esc=v=>String(v??'-').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const date=v=>v?new Date(`${v}T00:00:00`).toLocaleDateString('id-ID'):'-';
const conditions=[['water_flow_es','Water Flow ES'],['water_flow_ew','Water Flow EW'],['water_condition','Water Condition'],['actual_valve_es','Actual Valve ES'],['actual_valve_ew','Actual Valve EW'],['physical_condition_es','Physical Condition ES'],['physical_condition_ew','Physical Condition EW'],['sign_board_condition','Sign Board'],['housekeeping_condition','Housekeeping'],['road_access_condition','Road Access'],['sewer_condition','Sewer Condition']];
function error(text){const box=document.getElementById('messageBox');box.textContent=text;box.classList.remove('hidden');}
function item(label,value){return `<div><dt class="text-sm font-medium text-gray-500">${label}</dt><dd class="mt-1 text-gray-900">${esc(value)}</dd></div>`;}
async function init(){const response=await fetch(`/api/es-ew/${inspectionId}`,{headers});if(response.status===401){localStorage.clear();return window.location.href='/';}if(!response.ok)return error('Inspection tidak ditemukan.');const data=(await response.json()).data;
const row=data.items[0]||{};
document.getElementById('detailCard').innerHTML=`<section class="bg-white rounded-xl shadow p-6"><dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">${item('Reference',data.reference_no)}${item('Inspection Date',date(data.inspection_date))}${item('Area',data.area?.name)}${item('Inspector',data.inspector?.name)}</dl></section><section class="bg-white rounded-xl shadow p-6"><h2 class="text-lg font-bold text-gray-900 border-b pb-3 mb-4">${esc(row.name)}</h2><dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">${item('Location',row.type)}${item('Section',row.location_detail)}${item('Remark',row.remark)}${conditions.map(c=>item(c[1],row[c[0]]?'YES':'NO')).join('')}</dl><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">${['photo_before','photo_after'].map(field=>`<div><p class="text-sm font-medium text-gray-500 mb-2">${field==='photo_before'?'Eye Wash':'Emergency Shower'}</p>${row[field]?`<a href="/${esc(row[field])}" target="_blank"><img src="/${esc(row[field])}" class="max-h-64 rounded-lg border object-contain" alt="Inspection photo"></a>`:'<p class="text-gray-400">No photo</p>'}</div>`).join('')}</div></section>`;}
document.getElementById('deleteButton').addEventListener('click',async()=>{if(!confirm('Delete this inspection and all items?'))return;const response=await fetch(`/api/es-ew/${inspectionId}`,{method:'DELETE',headers});const data=await response.json();if(!response.ok)return error(data.message||'Delete failed.');sessionStorage.setItem('esEwMessage',data.message);window.location.href='/dashboard/es-ew-inspections';});
init().catch(()=>error('Terjadi kesalahan saat memuat detail.'));
</script>
@endsection

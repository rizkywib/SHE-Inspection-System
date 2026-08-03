@php
    $conditions = [
        ['field' => 'water_flow_es', 'label' => 'Water Flow ES'],
        ['field' => 'water_flow_ew', 'label' => 'Water Flow EW'],
        ['field' => 'water_condition', 'label' => 'Water Condition'],
        ['field' => 'actual_valve_es', 'label' => 'Actual Valve ES'],
        ['field' => 'actual_valve_ew', 'label' => 'Actual Valve EW'],
        ['field' => 'physical_condition_es', 'label' => 'Physical Condition ES'],
        ['field' => 'physical_condition_ew', 'label' => 'Physical Condition EW'],
        ['field' => 'sign_board_condition', 'label' => 'Sign Board'],
        ['field' => 'housekeeping_condition', 'label' => 'Housekeeping'],
        ['field' => 'road_access_condition', 'label' => 'Road Access'],
        ['field' => 'sewer_condition', 'label' => 'Sewer Condition'],
    ];
@endphp
<div class="p-4 md:p-8">
    <div class="mb-6">
        <a href="/dashboard/es-ew-inspections" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-2"></i>Back</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-3">{{ $mode === 'create' ? 'New' : 'Edit' }} ES&amp;EW Inspection</h1>
        <p class="text-gray-600 mt-1">Emergency Shower &amp; Eye Wash Inspection</p>
    </div>

    <div id="messageBox" class="hidden mb-5 rounded-lg border px-4 py-3"></div>
    <form id="inspectionForm" class="space-y-6" enctype="multipart/form-data">
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide border-b pb-3 mb-4">Inspection Data</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Date</label>
                    <input id="inspection_date" type="date" required
                        @if ($mode === 'edit') readonly @endif
                        class="w-full border rounded-lg px-3 py-2 {{ $mode === 'edit' ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }}">
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Area <span class="text-red-600">*</span></label><select id="area_id" required class="w-full border rounded-lg px-3 py-2"><option value="">Select Area</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Inspector</label><select id="inspector_id" class="w-full border rounded-lg px-3 py-2"><option value="">Current User</option></select></div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow p-6">
            <div class="border-b pb-3 mb-4">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">ES&amp;EW Item</h2>
                <p class="text-sm text-gray-500 mt-1">Item awal untuk inspection header ini.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-600">*</span></label>
                    <select id="point_id" required class="w-full border rounded-lg px-3 py-2"><option value="">Select Name</option></select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Location</label><input id="item_location" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Section</label><input id="item_section" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100"></div>
            </div>

            <div id="conditionsContainer" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mt-4"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Eye Wash</label>
                    <input id="photo_before" type="file" accept=".jpg,.jpeg,.png,.webp" class="w-full border rounded-lg px-3 py-2 bg-white">
                    <div id="preview_before" class="mt-2 text-xs text-gray-500">No photo</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Shower</label>
                    <input id="photo_after" type="file" accept=".jpg,.jpeg,.png,.webp" class="w-full border rounded-lg px-3 py-2 bg-white">
                    <div id="preview_after" class="mt-2 text-xs text-gray-500">No photo</div>
                </div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Remark</label><textarea id="remark" rows="2" maxlength="5000" class="w-full border rounded-lg px-3 py-2"></textarea></div>
            </div>
        </section>

        <div class="flex gap-3">
            <button id="saveButton" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg"><i class="fas fa-save mr-2"></i>Save</button>
            <a href="/dashboard/es-ew-inspections" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
const token=localStorage.getItem('token');
const mode=@json($mode),inspectionId=@json($inspectionId),conditionFields=@json($conditions);
const headers={'Authorization':`Bearer ${token}`,'Accept':'application/json'};
if(!token)window.location.href='/';
let points=[];
const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const photoUrl=value=>value?`/${String(value).replace(/^\/+/,'')}`:'';
function message(text,type='error'){const box=document.getElementById('messageBox');box.textContent=text;box.className=`mb-5 rounded-lg border px-4 py-3 ${type==='success'?'bg-green-50 border-green-300 text-green-800':'bg-red-50 border-red-300 text-red-800'}`;window.scrollTo({top:0,behavior:'smooth'});}
function fillSelect(id,rows,label){document.getElementById(id).insertAdjacentHTML('beforeend',rows.map(row=>`<option value="${row.id}">${esc(label(row))}</option>`).join(''));}
function renderConditions(item={}){
    document.getElementById('conditionsContainer').innerHTML=conditionFields.map(condition=>{
        const yes=item[condition.field]===undefined||item[condition.field]===null?true:Boolean(item[condition.field]);
        return `<div class="border rounded-lg p-3"><span class="block text-sm font-medium text-gray-700 mb-2">${esc(condition.label)}</span><div class="flex gap-5"><label class="text-sm flex gap-2 items-center"><input required type="radio" name="${condition.field}" data-condition="${condition.field}" value="1" ${yes?'checked':''}> YES</label><label class="text-sm flex gap-2 items-center"><input required type="radio" name="${condition.field}" data-condition="${condition.field}" value="0" ${!yes?'checked':''}> NO</label></div></div>`;
    }).join('');
}
function updatePointDetails(){
    const point=points.find(row=>String(row.id)===document.getElementById('point_id').value);
    document.getElementById('item_location').value=point?.ket1||'';
    document.getElementById('item_section').value=point?.ket2||'';
}
function setPhoto(field,path){
    document.getElementById(`preview_${field.replace('photo_','')}`).innerHTML=path?`<a href="${esc(photoUrl(path))}" target="_blank" class="text-blue-600">View current photo</a>`:'No photo';
}
function bindPhotoPreview(field){
    document.getElementById(field).addEventListener('change',event=>{
        const file=event.target.files[0];if(!file)return;
        document.getElementById(`preview_${field.replace('photo_','')}`).innerHTML=`<img src="${URL.createObjectURL(file)}" class="h-24 rounded border object-cover" alt="Preview">`;
    });
}
async function init(){
    const masterResponse=await fetch('/api/es-ew/master-data',{headers});
    if(masterResponse.status===401){localStorage.clear();return window.location.href='/';}
    if(!masterResponse.ok)return message('Master data gagal dimuat.');
    const master=await masterResponse.json();points=master.points;
    fillSelect('area_id',master.areas,row=>row.name);fillSelect('inspector_id',master.inspectors,row=>row.name);fillSelect('point_id',points,row=>row.name_point);
    renderConditions();
    if(mode==='create'){
        document.getElementById('inspection_date').value=new Date().toISOString().slice(0,10);
        return;
    }
    const response=await fetch(`/api/es-ew/${inspectionId}`,{headers});if(!response.ok)return message('Inspection tidak ditemukan.');
    const data=(await response.json()).data,item=data.items[0]||{};
    ['inspection_date','area_id','inspector_id'].forEach(field=>document.getElementById(field).value=data[field]??'');
    const selectedPoint=points.find(point=>point.name_point===item.name&&String(point.ket1??'')===String(item.type??'')&&String(point.ket2??'')===String(item.location_detail??''))||points.find(point=>point.name_point===item.name);
    document.getElementById('point_id').value=selectedPoint?.id||'';
    document.getElementById('remark').value=item.remark||'';
    updatePointDetails();renderConditions(item);setPhoto('photo_before',item.photo_before);setPhoto('photo_after',item.photo_after);
}
document.getElementById('point_id').addEventListener('change',updatePointDetails);
bindPhotoPreview('photo_before');bindPhotoPreview('photo_after');
document.getElementById('inspectionForm').addEventListener('submit',async event=>{
    event.preventDefault();const button=document.getElementById('saveButton');button.disabled=true;button.classList.add('opacity-60');
    const form=new FormData();
    ['inspection_date','area_id','inspector_id','point_id'].forEach(field=>form.append(field,document.getElementById(field).value));
    form.append('items[0][remark]',document.getElementById('remark').value);
    conditionFields.forEach(condition=>form.append(`items[0][${condition.field}]`,document.querySelector(`[data-condition="${condition.field}"]:checked`).value));
    ['photo_before','photo_after'].forEach(field=>{const file=document.getElementById(field).files[0];if(file)form.append(`items[0][${field}]`,file);});
    if(mode==='edit')form.append('_method','PUT');
    const response=await fetch(mode==='create'?'/api/es-ew':`/api/es-ew/${inspectionId}`,{method:'POST',headers,body:form});
    const data=await response.json();button.disabled=false;button.classList.remove('opacity-60');
    if(!response.ok){const errors=Object.values(data.errors||{}).flat();return message(errors[0]||data.message||'Save failed.');}
    sessionStorage.setItem('esEwMessage',data.message);window.location.href='/dashboard/es-ew-inspections';
});
init().catch(()=>message('Terjadi kesalahan saat memuat form.'));
</script>

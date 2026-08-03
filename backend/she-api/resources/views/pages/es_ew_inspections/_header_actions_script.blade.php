const headerConditions = [
    'water_flow_es',
    'water_flow_ew',
    'water_condition',
    'actual_valve_es',
    'actual_valve_ew',
    'physical_condition_es',
    'physical_condition_ew',
    'sign_board_condition',
    'housekeeping_condition',
    'road_access_condition',
    'sewer_condition'
];
const headerConditionLabels = {
    water_flow_es: 'Water Flow ES',
    water_flow_ew: 'Water Flow EW',
    water_condition: 'Water Condition',
    actual_valve_es: 'Actual Valve ES',
    actual_valve_ew: 'Actual Valve EW',
    physical_condition_es: 'Physical Condition ES',
    physical_condition_ew: 'Physical Condition EW',
    sign_board_condition: 'Sign Board',
    housekeeping_condition: 'Housekeeping',
    road_access_condition: 'Road Access',
    sewer_condition: 'Sewer'
};

function headerBooleanValue(value) {
    return value === true || value === 1 || value === '1';
}

function headerConditionMark(value) {
    return headerBooleanValue(value) ? '&#10003;' : 'X';
}

function formatHeaderDateOnly(value) {
    return value ? String(value).slice(0, 10) : '-';
}

function formatHeaderDatePrint(value) {
    if (!value) return '-';
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    if (Number.isNaN(date.getTime())) return String(value);
    return [
        String(date.getDate()).padStart(2, '0'),
        String(date.getMonth() + 1).padStart(2, '0'),
        date.getFullYear()
    ].join('-');
}

function formatHeaderSignatureDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'Asia/Bangkok'
    }).format(date);
}

function headerPhotoUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (/^(https?:|data:|blob:)/i.test(value)) return value;
    return `/${value.replace(/^\/+/, '').replace(/^public\//, '')}`;
}

function canPreviewHeaderImage(path) {
    return !/\.(heic|heif)$/i.test(String(path || '').split('?')[0]);
}

function safeHeaderFilePart(value) {
    return String(value || 'inspection').replace(/[^a-z0-9_-]+/gi, '_');
}

async function fetchHeaderInspectionDetail(id) {
    const response = await fetch(`/api/es-ew/${id}`, { headers });
    if (response.status === 401) {
        localStorage.clear();
        window.location.href = '/';
        return null;
    }
    if (!response.ok) throw new Error('Inspection tidak ditemukan.');
    return (await response.json()).data;
}

async function signInspection(id) {
    if (!confirm('Add your signature to this inspection?')) return;

    const response = await fetch(`/api/es-ew/${id}/sign`, {
        method: 'POST',
        headers
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        message(data.message || 'Failed to sign inspection.', 'error');
        return;
    }

    message(data.message || 'Inspection signed successfully.');
    await loadData(params.get('page') || 1);
}

async function exportInspection(id) {
    try {
        const detail = await fetchHeaderInspectionDetail(id);
        if (!detail) return;

        const areaName = detail.area?.name || '-';
        const inspectorName = detail.inspector?.name || '-';
        const items = Array.isArray(detail.items) ? detail.items : [];
        const conditionHeaders = headerConditions.map(field =>
            `<th>${escapeHtml(headerConditionLabels[field])}</th>`
        ).join('');
        const itemRows = items.length > 0
            ? items.map((item, index) => `
                <tr>
                    <td class="center">${index + 1}</td>
                    <td>${escapeHtml(item.name)}</td>
                    <td>${escapeHtml(item.type)}</td>
                    <td>${escapeHtml(item.location_detail)}</td>
                    ${headerConditions.map(field => `<td class="center">${headerConditionMark(item[field])}</td>`).join('')}
                    <td>${escapeHtml(item.remark)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="16" class="center">No items</td></tr>';

        const htmlContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="utf-8">
                <title>ES&amp;EW Inspection</title>
                <style>
                    table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 10px; }
                    th, td { border: 1px solid #000; padding: 5px 6px; text-align: left; vertical-align: middle; }
                    th { background-color: #c0c0c0; font-weight: bold; text-align: center; }
                    .header { margin-bottom: 12px; font-family: Arial, sans-serif; }
                    .header h2 { margin: 0 0 8px; font-size: 16px; }
                    .header p { margin: 4px 0; font-size: 11px; }
                    .center { text-align: center; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>EMERGENCY SHOWER &amp; EYE WASH INSPECTION</h2>
                    <p><strong>Area:</strong> ${escapeHtml(areaName)}</p>
                    <p><strong>Date Inspected:</strong> ${formatHeaderDateOnly(detail.inspection_date)}</p>
                    <p><strong>Inspector:</strong> ${escapeHtml(inspectorName)}</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Section</th>
                            ${conditionHeaders}
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>${itemRows}</tbody>
                </table>
            </body>
            </html>
        `;

        const blob = new Blob([htmlContent], {
            type: 'application/vnd.ms-excel;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `es_ew_inspection_${safeHeaderFilePart(detail.inspection_date)}_${id}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } catch (error) {
        message(error.message || 'Failed to export inspection.', 'error');
    }
}

function headerPrintLogoHtml() {
    return '<img src="/images/ecogreen-logo-print.png" class="print-company-logo" alt="Ecogreen Oleochemicals">';
}

function headerPrintSignatureHtml(path) {
    const url = headerPhotoUrl(path);
    if (!url || !canPreviewHeaderImage(url)) {
        return '<div class="print-sign-space"></div>';
    }
    return `<img src="${escapeHtml(url)}" class="print-sign-image" alt="Signature">`;
}

function buildHeaderPrintHtml(detail) {
    const areaName = detail.area?.name || '-';
    const inspector = detail.inspector || {};
    const signer = detail.signer || null;
    const items = Array.isArray(detail.items) ? detail.items : [];
    const itemRows = items.length > 0
        ? items.map((item, index) => `
            <tr>
                <td class="center">${index + 1}</td>
                <td>${escapeHtml(item.type)}</td>
                <td>${escapeHtml(item.name)}</td>
                <td class="center check">${headerConditionMark(item.water_flow_es)}</td>
                <td class="center check">${headerConditionMark(item.water_flow_ew)}</td>
                <td class="center check">${headerConditionMark(item.water_condition)}</td>
                <td class="center check">${headerConditionMark(item.actual_valve_es)}</td>
                <td class="center check">${headerConditionMark(item.actual_valve_ew)}</td>
                <td class="center check">${headerConditionMark(item.physical_condition_es)}</td>
                <td class="center check">${headerConditionMark(item.physical_condition_ew)}</td>
                <td class="center check">${headerConditionMark(item.sign_board_condition)}</td>
                <td class="center check">${headerConditionMark(item.road_access_condition)}</td>
                <td class="center check">${headerConditionMark(item.housekeeping_condition)}</td>
                <td class="center check">${headerConditionMark(item.sewer_condition)}</td>
                <td>${escapeHtml(item.remark)}</td>
            </tr>
        `).join('')
        : '<tr><td colspan="15" class="center">No items</td></tr>';

    return `
        <div class="print-page">
            <div class="print-top">
                <div class="print-brand">
                    ${headerPrintLogoHtml()}
                    <span>PT. Ecogreen Oleochemicals</span>
                </div>
                <div class="print-doc-code">EOB-Saf-007 Rev. 4 31/12/2018</div>
            </div>
            <div class="print-meta">
                <div>Batam Plan</div>
                <div>Location : <strong><u>${escapeHtml(areaName)}</u></strong></div>
                <div>Date Inspected : <strong>${formatHeaderDatePrint(detail.inspection_date)}</strong></div>
            </div>
            <table class="print-table">
                <colgroup>
                    <col style="width:2%">
                    <col style="width:18%">
                    <col style="width:7%">
                    <col style="width:2%">
                    <col style="width:2%">
                    <col style="width:12%">
                    <col style="width:3%">
                    <col style="width:3%">
                    <col style="width:3%">
                    <col style="width:3%">
                    <col style="width:5%">
                    <col style="width:9%">
                    <col style="width:7%">
                    <col style="width:9%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr class="print-title-row">
                        <th colspan="15">EMERGENCY SHOWER &amp; EYE WASH STATION MONTHLY INSPECTION</th>
                    </tr>
                    <tr>
                        <th rowspan="2">No.</th>
                        <th colspan="2">Location</th>
                        <th colspan="2">Aliran air</th>
                        <th rowspan="2">Kondisi air ES &amp; EW Station</th>
                        <th colspan="2">Actuator/valve</th>
                        <th colspan="2">Kondisi fisik</th>
                        <th rowspan="2">Sign Board</th>
                        <th rowspan="2">Akses jalan &amp; lokasi</th>
                        <th rowspan="2">Housekeeping</th>
                        <th rowspan="2">Saluran pembuangan</th>
                        <th rowspan="2">Remark</th>
                    </tr>
                    <tr>
                        <th>Plant</th>
                        <th>Name</th>
                        <th>ES</th>
                        <th>EW</th>
                        <th>ES</th>
                        <th>EW</th>
                        <th>ES</th>
                        <th>EW</th>
                    </tr>
                </thead>
                <tbody>${itemRows}</tbody>
            </table>
            <div class="print-signatures">
                <div class="print-sign-block">
                    <div>Inspected by,</div>
                    ${headerPrintSignatureHtml(inspector.signature_path)}
                    <div class="print-sign-name">${escapeHtml(inspector.name || '-')}</div>
                    <div>${escapeHtml(inspector.position || 'Safety Inspector')}</div>
                </div>
                <div class="print-sign-block right">
                    <div>Noted by,</div>
                    <div class="print-signature-line">
                        ${headerPrintSignatureHtml(signer?.signature_path)}
                        ${signer ? `<span class="print-sign-date">${escapeHtml(formatHeaderSignatureDate(detail.signed_at))}</span>` : ''}
                    </div>
                    <div class="print-sign-name">${escapeHtml(signer?.name || 'Belum di ttd')}</div>
                    <div>${escapeHtml(signer?.position || 'Safety Supervisor')}</div>
                </div>
            </div>
        </div>
    `;
}

function buildHeaderPrintPreviewDocument(detail) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ES&amp;EW Inspection - ${escapeHtml(formatHeaderDateOnly(detail.inspection_date))}</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 5mm; background: #e5e7eb; }
        .preview-sheet { width: 287mm; min-height: 200mm; margin: 0 auto; padding: 3mm 2mm; background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,.18); }
        .print-page { font-family: Arial, sans-serif; color: #000; width: 100%; font-size: 12px; line-height: 1.35; }
        .print-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 11mm; }
        .print-brand { display: flex; align-items: center; gap: 5px; font-size: 14px; font-weight: 700; }
        .print-company-logo { width: 38px; height: 50px; object-fit: contain; display: block; }
        .print-doc-code { font-size: 13px; text-align: right; padding-top: 2px; }
        .print-meta { margin-bottom: 20px; font-size: 13px; line-height: 1.5; }
        .print-meta div { margin: 1px 0; }
        .print-table { border-collapse: collapse; width: 100%; table-layout: fixed; font-size: 8px; line-height: 1.1; }
        .print-table th, .print-table td { border: 1px solid #000; padding: 3px 2px; vertical-align: middle; overflow-wrap: anywhere; }
        .print-table th { font-weight: 700; text-align: center; }
        .print-table .print-title-row th { background: #000; color: #fff; font-size: 13px; padding: 4px; letter-spacing: .1px; }
        .center { text-align: center; }
        .check { font-family: "Segoe UI Symbol", "Arial Unicode MS", Arial, sans-serif; font-size: 15px; font-weight: 700; line-height: 1; }
        .print-signatures { display: flex; justify-content: space-between; margin-top: 34px; font-size: 10px; }
        .print-sign-block { width: 240px; }
        .print-sign-block.right { margin-right: 30px; }
        .print-sign-image { height: 42px; max-width: 120px; object-fit: contain; display: block; margin: 10px 0 3px 8px; }
        .print-signature-line { display: flex; align-items: flex-end; gap: 8px; min-height: 55px; }
        .print-sign-date { font-size: 8px; color: #444; margin-bottom: 5px; white-space: nowrap; }
        .print-sign-space { height: 55px; }
        .print-sign-name { font-weight: 700; text-decoration: underline; }
        @media print {
            body { padding: 0; background: #fff; }
            .preview-sheet { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <main id="printPreview" class="preview-sheet">${buildHeaderPrintHtml(detail)}</main>
</body>
</html>`;
}

async function waitForHeaderPrintImages(container) {
    const images = Array.from(container.querySelectorAll('img'));
    await Promise.all(images.map(image => {
        if (image.complete) return Promise.resolve();
        return new Promise(resolve => {
            const finish = () => resolve();
            image.addEventListener('load', finish, { once: true });
            image.addEventListener('error', finish, { once: true });
            setTimeout(finish, 3000);
        });
    }));
}

async function printInspection(id) {
    const previewWindow = window.open('', '_blank');
    if (!previewWindow) {
        message('Pop-up diblokir. Izinkan pop-up untuk membuka print preview.', 'error');
        return;
    }

    previewWindow.document.write('<!DOCTYPE html><title>Preparing Print...</title><p style="font-family:Arial;padding:24px">Preparing print preview...</p>');

    try {
        const detail = await fetchHeaderInspectionDetail(id);
        if (!detail) {
            previewWindow.close();
            return;
        }
        previewWindow.document.open();
        previewWindow.document.write(buildHeaderPrintPreviewDocument(detail));
        previewWindow.document.close();
        await waitForHeaderPrintImages(previewWindow.document);
        previewWindow.focus();
        previewWindow.print();
    } catch (error) {
        previewWindow.document.body.innerHTML =
            `<p style="font-family:Arial;padding:24px;color:#b91c1c">${escapeHtml(error.message || 'Failed to open print preview')}</p>`;
    }
}

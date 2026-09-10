<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PointController;
use App\Http\Controllers\Api\FireHydrantLocationController;
use App\Http\Controllers\Api\FireExtinguisherLocationController;
use App\Http\Controllers\Api\FireAlarmLocationController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\IncidentTypeController;
use App\Http\Controllers\Api\SupervisionAreaController;
use App\Http\Controllers\Api\PermitTypeController;
use App\Http\Controllers\Api\PermitJobPerformanceController;
use App\Http\Controllers\Api\PermitMainAreaController;
use App\Http\Controllers\Api\PermitSubAreaController;
use App\Http\Controllers\Api\FireHydrantController;
use App\Http\Controllers\Api\FireExtinguisherController;
use App\Http\Controllers\Api\FireAlarmController;
use App\Http\Controllers\Api\EsEwController;
use App\Http\Controllers\Api\EsEwAreaController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MedicalReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\SafeWorkPermitInspectionController;
use App\Http\Controllers\Api\SafetyTalkTrainingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (rate-limited to mitigate brute force)
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-inspections', [DashboardController::class, 'recentInspections']);
    Route::get('/dashboard/incident-summary', [DashboardController::class, 'incidentSummary']);

    // Master Data
    Route::apiResource('companies', CompanyController::class)->only(['index', 'show']);
    Route::apiResource('companies', CompanyController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('branches', BranchController::class)->only(['index', 'show']);
    Route::apiResource('branches', BranchController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('divisions', DivisionController::class)->only(['index', 'show']);
    Route::apiResource('divisions', DivisionController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('departments', DepartmentController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('sections', SectionController::class)->only(['index', 'show']);
    Route::apiResource('sections', SectionController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store'])->middleware('admin');
    Route::get('/locations/{id}', [LocationController::class, 'show']);
    Route::put('/locations/{id}', [LocationController::class, 'update'])->middleware('admin');
    Route::delete('/locations/{id}', [LocationController::class, 'destroy'])->middleware('admin');
    Route::apiResource('points', PointController::class)->only(['index', 'show']);
    Route::apiResource('points', PointController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('fire-hydrant-locations', FireHydrantLocationController::class)->only(['index', 'show']);
    Route::apiResource('fire-hydrant-locations', FireHydrantLocationController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('fire-extinguisher-locations', FireExtinguisherLocationController::class)->only(['index', 'show']);
    Route::apiResource('fire-extinguisher-locations', FireExtinguisherLocationController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('fire-alarm-locations', FireAlarmLocationController::class)->only(['index', 'show']);
    Route::apiResource('fire-alarm-locations', FireAlarmLocationController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('areas', AreaController::class)->only(['index', 'show']);
    Route::apiResource('areas', AreaController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('es-ew-areas', EsEwAreaController::class)->only(['index', 'show']);
    Route::apiResource('es-ew-areas', EsEwAreaController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('supervision-areas', SupervisionAreaController::class)->only(['index', 'show']);
    Route::apiResource('supervision-areas', SupervisionAreaController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('permit-types', PermitTypeController::class)->only(['index', 'show']);
    Route::apiResource('permit-types', PermitTypeController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('permit-job-performances', PermitJobPerformanceController::class)->only(['index', 'show']);
    Route::apiResource('permit-job-performances', PermitJobPerformanceController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('permit-main-areas', PermitMainAreaController::class)->only(['index', 'show']);
    Route::apiResource('permit-main-areas', PermitMainAreaController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('permit-sub-areas', PermitSubAreaController::class)->only(['index', 'show']);
    Route::apiResource('permit-sub-areas', PermitSubAreaController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::get('/incident-types', [IncidentTypeController::class, 'index']);
    Route::post('/incident-types', [IncidentTypeController::class, 'store'])->middleware('admin');
    Route::get('/incident-types/{id}', [IncidentTypeController::class, 'show']);
    Route::put('/incident-types/{id}', [IncidentTypeController::class, 'update'])->middleware('admin');
    Route::delete('/incident-types/{id}', [IncidentTypeController::class, 'destroy'])->middleware('admin');
    Route::apiResource('users', UserController::class)->only(['index', 'show']);
    Route::apiResource('users', UserController::class)->only(['store', 'update', 'destroy'])->middleware('admin');

    // Permit Matrix / Safe Work Permit Inspections
    Route::get('/safe-work-permit-inspections/master-data', [SafeWorkPermitInspectionController::class, 'masterData'])
        ->middleware('permission:safe-work-permit-inspection.view');
    Route::get('/safe-work-permit-inspections', [SafeWorkPermitInspectionController::class, 'index'])
        ->middleware('permission:safe-work-permit-inspection.view');
    Route::post('/safe-work-permit-inspections', [SafeWorkPermitInspectionController::class, 'store'])
        ->middleware('permission:safe-work-permit-inspection.create');
    Route::get('/safe-work-permit-inspections/{id}', [SafeWorkPermitInspectionController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permission:safe-work-permit-inspection.view');
    Route::put('/safe-work-permit-inspections/{id}', [SafeWorkPermitInspectionController::class, 'update'])
        ->whereNumber('id')
        ->middleware('admin');
    Route::delete('/safe-work-permit-inspections/{id}', [SafeWorkPermitInspectionController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('admin');

    // Safety Talk / Training On Site
    Route::get('/safety-talk-trainings/master-data', [SafetyTalkTrainingController::class, 'masterData'])
        ->middleware('permission:safety-talk-training.view');
    Route::get('/safety-talk-trainings', [SafetyTalkTrainingController::class, 'index'])
        ->middleware('permission:safety-talk-training.view');
    Route::post('/safety-talk-trainings', [SafetyTalkTrainingController::class, 'store'])
        ->middleware('permission:safety-talk-training.create');
    Route::get('/safety-talk-trainings/{id}', [SafetyTalkTrainingController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permission:safety-talk-training.view');
    Route::put('/safety-talk-trainings/{id}', [SafetyTalkTrainingController::class, 'update'])
        ->whereNumber('id')
        ->middleware('admin');
    Route::delete('/safety-talk-trainings/{id}', [SafetyTalkTrainingController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('admin');

    // QR Codes
    Route::get('/qr-codes/generate/{assetType}/{assetId}', [QrCodeController::class, 'generate']);
    Route::post('/qr-codes/scan', [QrCodeController::class, 'scan']);
    Route::get('/qr-codes', [QrCodeController::class, 'index']);

    // Fire Hydrant Inspections
    Route::get('/fire-hydrants', [FireHydrantController::class, 'index']);
    Route::get('/fire-hydrants/next-reference', [FireHydrantController::class, 'nextReference']);
    Route::post('/fire-hydrants', [FireHydrantController::class, 'store']);
    Route::get('/fire-hydrants/{id}', [FireHydrantController::class, 'show']);
    Route::put('/fire-hydrants/{id}', [FireHydrantController::class, 'update']);
    Route::delete('/fire-hydrants/{id}', [FireHydrantController::class, 'destroy']);
    Route::post('/fire-hydrants/{id}/checkin', [FireHydrantController::class, 'checkin']);
    Route::post('/fire-hydrants/{id}/sign', [FireHydrantController::class, 'sign']);
    Route::delete('/fire-hydrants/{inspectionId}/items/{itemIndex}', [FireHydrantController::class, 'deleteItem']);

    // Fire Extinguisher Inspections
    Route::get('/fire-extinguishers', [FireExtinguisherController::class, 'index']);
    Route::get('/fire-extinguishers/next-reference', [FireExtinguisherController::class, 'nextReference']);
    Route::post('/fire-extinguishers', [FireExtinguisherController::class, 'store']);
    Route::get('/fire-extinguishers/{id}', [FireExtinguisherController::class, 'show']);
    Route::put('/fire-extinguishers/{id}', [FireExtinguisherController::class, 'update']);
    Route::delete('/fire-extinguishers/{id}', [FireExtinguisherController::class, 'destroy']);
    Route::post('/fire-extinguishers/{id}/checkin', [FireExtinguisherController::class, 'checkin']);
    Route::post('/fire-extinguishers/{id}/sign', [FireExtinguisherController::class, 'sign']);

    // Fire Alarm Inspections
    Route::get('/fire-alarms', [FireAlarmController::class, 'index']);
    Route::post('/fire-alarms', [FireAlarmController::class, 'store']);
    Route::get('/fire-alarms/{id}', [FireAlarmController::class, 'show']);
    Route::put('/fire-alarms/{id}', [FireAlarmController::class, 'update']);
    Route::post('/fire-alarms/{id}/items', [FireAlarmController::class, 'storeItem']);
    Route::delete('/fire-alarms/{id}', [FireAlarmController::class, 'destroy']);
    Route::post('/fire-alarms/{id}/checkin', [FireAlarmController::class, 'checkin']);
    Route::post('/fire-alarms/{id}/sign', [FireAlarmController::class, 'sign']);

    // ES/EW Inspections
    Route::get('/es-ew/master-data', [EsEwController::class, 'masterData']);
    Route::get('/es-ew/next-reference', [EsEwController::class, 'nextReference']);
    Route::get('/es-ew', [EsEwController::class, 'index']);
    Route::post('/es-ew', [EsEwController::class, 'store']);
    Route::post('/es-ew/{inspectionId}/items', [EsEwController::class, 'storeItem'])->whereNumber('inspectionId');
    Route::put('/es-ew/{inspectionId}/items/{itemId}', [EsEwController::class, 'updateItem'])
        ->whereNumber('inspectionId')
        ->whereNumber('itemId');
    Route::delete('/es-ew/{inspectionId}/items/{itemId}', [EsEwController::class, 'destroyItem'])
        ->whereNumber('inspectionId')
        ->whereNumber('itemId');
    Route::get('/es-ew/{id}', [EsEwController::class, 'show']);
    Route::put('/es-ew/{id}', [EsEwController::class, 'update']);
    Route::delete('/es-ew/{id}', [EsEwController::class, 'destroy']);
    Route::post('/es-ew/{id}/checkin', [EsEwController::class, 'checkin']);
    Route::post('/es-ew/{id}/sign', [EsEwController::class, 'sign']);

    // Checklist Inspections
    Route::get('/checklists', [ChecklistController::class, 'index']);
    Route::post('/checklists', [ChecklistController::class, 'store']);
    Route::get('/checklists/{id}', [ChecklistController::class, 'show']);
    Route::put('/checklists/{id}', [ChecklistController::class, 'update']);
    Route::delete('/checklists/{id}', [ChecklistController::class, 'destroy']);
    Route::post('/checklists/{id}/checkin', [ChecklistController::class, 'checkin']);
    Route::get('/checklist-questions/{categoryId}', [ChecklistController::class, 'questions']);
    Route::post('/checklist-questions', [ChecklistController::class, 'storeQuestion']);

    // Incidents
    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{id}', [IncidentController::class, 'show']);
    Route::put('/incidents/{id}', [IncidentController::class, 'update']);
    Route::delete('/incidents/{id}', [IncidentController::class, 'destroy']);
    Route::post('/incidents/{id}/images', [IncidentController::class, 'uploadImage']);
    Route::post('/incidents/{id}/investigate', [IncidentController::class, 'investigate']);

    // Medical Reports
    Route::get('/medical-reports', [MedicalReportController::class, 'index']);
    Route::post('/medical-reports', [MedicalReportController::class, 'store']);
    Route::get('/medical-reports/{id}', [MedicalReportController::class, 'show']);
    Route::put('/medical-reports/{id}', [MedicalReportController::class, 'update']);
    Route::delete('/medical-reports/{id}', [MedicalReportController::class, 'destroy']);

    // File Upload
    Route::post('/upload', [\App\Http\Controllers\Api\FileController::class, 'upload']);
});

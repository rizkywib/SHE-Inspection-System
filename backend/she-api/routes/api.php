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
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\IncidentTypeController;
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

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

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
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('sections', SectionController::class);
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::get('/locations/{id}', [LocationController::class, 'show']);
    Route::put('/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
    Route::apiResource('points', PointController::class);
    Route::apiResource('fire-hydrant-locations', FireHydrantLocationController::class);
    Route::apiResource('fire-extinguisher-locations', FireExtinguisherLocationController::class);
    Route::apiResource('areas', AreaController::class);
    Route::apiResource('es-ew-areas', EsEwAreaController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('/incident-types', [IncidentTypeController::class, 'index']);
    Route::post('/incident-types', [IncidentTypeController::class, 'store']);
    Route::get('/incident-types/{id}', [IncidentTypeController::class, 'show']);
    Route::put('/incident-types/{id}', [IncidentTypeController::class, 'update']);
    Route::delete('/incident-types/{id}', [IncidentTypeController::class, 'destroy']);
    Route::apiResource('users', UserController::class);

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
        ->middleware('permission:safe-work-permit-inspection.update');
    Route::delete('/safe-work-permit-inspections/{id}', [SafeWorkPermitInspectionController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('permission:safe-work-permit-inspection.delete');

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
        ->middleware('permission:safety-talk-training.update');
    Route::delete('/safety-talk-trainings/{id}', [SafetyTalkTrainingController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('permission:safety-talk-training.delete');

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

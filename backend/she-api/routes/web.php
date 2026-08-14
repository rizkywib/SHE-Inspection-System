<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('web')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    });

    Route::get('/dashboard/fire-hydrants', function () {
        return view('pages.fire_hydrants');
    });

    Route::get('/dashboard/fire-hydrants/edit', function () {
        return view('pages.fire_hydrant_edit');
    });

    Route::get('/dashboard/fire-hydrant-checklist', function () {
        return view('pages.fire_hydrant_checklist');
    });

    Route::get('/dashboard/fire-extinguishers', function () {
        return response()
            ->view('pages.fire_extinguishers')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    });

    Route::get('/dashboard/fire-extinguishers/create', function () {
        return response()
            ->view('pages.fire_extinguisher_form', ['inspectionId' => null])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    });

    Route::get('/dashboard/fire-extinguishers/{id}/edit', function (int $id) {
        return response()
            ->view('pages.fire_extinguisher_form', ['inspectionId' => $id])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    })->whereNumber('id');

    Route::get('/dashboard/fire-extinguishers/location/{locationId}/items', function (int $locationId) {
        return response()
            ->view('pages.fire_extinguisher_items', ['locationId' => $locationId])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    })->whereNumber('locationId');

    Route::get('/dashboard/fire-alarms', function () {
        return view('pages.fire_alarms');
    });

    Route::prefix('/dashboard/es-ew-inspections')->name('es-ew-inspections.')->group(function () {
        Route::get('/', fn () => view('pages.es_ew_inspections.index'))->name('index');
        Route::get('/create', fn () => view('pages.es_ew_inspections.create'))->name('create');
        Route::get('/{id}/items/create', fn (int $id) => view('pages.es_ew_inspections.item_form', [
            'inspectionId' => $id,
            'itemId' => null,
            'mode' => 'create',
        ]))
            ->whereNumber('id')
            ->name('items.create');
        Route::get('/{id}/items/{itemId}/edit', fn (int $id, int $itemId) => view('pages.es_ew_inspections.item_form', [
            'inspectionId' => $id,
            'itemId' => $itemId,
            'mode' => 'edit',
        ]))
            ->whereNumber('id')
            ->whereNumber('itemId')
            ->name('items.edit');
        Route::get('/{id}', fn (int $id) => view('pages.es_ew_inspections.show', ['inspectionId' => $id]))
            ->whereNumber('id')
            ->name('show');
        Route::get('/{id}/edit', fn (int $id) => view('pages.es_ew_inspections.edit', ['inspectionId' => $id]))
            ->whereNumber('id')
            ->name('edit');
    });

    Route::get('/dashboard/inspections', function () {
        return view('pages.inspections');
    });

    Route::get('/dashboard/points', function () {
        return view('pages.points');
    });

    Route::get('/dashboard/fire-hydrant-locations', function () {
        return view('pages.fire_hydrant_locations');
    });

    Route::get('/dashboard/fire-extinguisher-locations', function () {
        return view('pages.fire_extinguisher_locations');
    });

    Route::get('/dashboard/incident-types', function () {
        return view('pages.incident_types');
    });

    Route::get('/dashboard/es-ew-areas', function () {
        return response()
            ->view('pages.es_ew_areas')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    });

    Route::get('/dashboard/supervision-areas', function () {
        return response()
            ->view('pages.supervision_areas')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    });

    Route::get('/dashboard/permit-types', function () {
        return response()
            ->view('pages.permit_types')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    });

    Route::get('/dashboard/users', function () {
        return view('pages.users');
    });

    Route::get('/dashboard/profile', function () {
        return view('pages.profile.edit');
    })->name('profile.edit');

    Route::prefix('/dashboard/permit-matrix')->name('permit-matrix.')->group(function () {
        Route::get('/', fn () => view('pages.permit_matrix.index'))->name('index');
        Route::get('/create', fn () => view('pages.permit_matrix.create'))->name('create');
        Route::get('/{id}', fn (int $id) => view('pages.permit_matrix.show', ['inspectionId' => $id]))
            ->whereNumber('id')
            ->name('show');
        Route::get('/{id}/edit', fn (int $id) => view('pages.permit_matrix.edit', ['inspectionId' => $id]))
            ->whereNumber('id')
            ->name('edit');
    });

    Route::prefix('/dashboard/safety-talk-trainings')->name('safety-talk-trainings.')->group(function () {
        Route::get('/', fn () => view('pages.safety_talk_trainings.index'))->name('index');
        Route::get('/create', fn () => view('pages.safety_talk_trainings.create'))->name('create');
        Route::get('/{id}', fn (int $id) => view('pages.safety_talk_trainings.show', ['trainingId' => $id]))
            ->whereNumber('id')
            ->name('show');
        Route::get('/{id}/edit', fn (int $id) => view('pages.safety_talk_trainings.edit', ['trainingId' => $id]))
            ->whereNumber('id')
            ->name('edit');
    });
});

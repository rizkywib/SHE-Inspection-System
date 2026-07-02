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

    Route::get('/dashboard/fire-extinguishers', function () {
        return view('pages.fire_extinguishers');
    });

    Route::get('/dashboard/fire-alarms', function () {
        return view('pages.fire_alarms');
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

    Route::get('/dashboard/users', function () {
        return view('pages.users');
    });
});

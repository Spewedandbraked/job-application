<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Middleware\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('organizations')->middleware(['api', JsonResponse::class])->group(function () {
    Route::get('/by-building/{buildingId}', [OrganizationController::class, 'byBuilding']);
    Route::get('/by-activity/{activityId}', [OrganizationController::class, 'byActivity']);
    Route::get('/nearby', [OrganizationController::class, 'nearby']);
    Route::get('/{id}', [OrganizationController::class, 'show']);
    Route::get('/search/activity', [OrganizationController::class, 'searchByActivity']);
    Route::get('/search/name', [OrganizationController::class, 'searchByName']);
});

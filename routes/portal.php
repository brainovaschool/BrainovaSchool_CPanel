<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\EmployeeController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::get('/portal', [PortalController::class, 'index'])->name('portal.index')->middleware('PermissionCheck:portal_access');

                Route::controller(EmployeeController::class)->prefix('portal/employees')->name('portal-employees.')->group(function () {
                    Route::get('/',          'index')->name('index')->middleware('PermissionCheck:portal_access');
                    Route::get('/show/{id}', 'show')->name('show')->middleware('PermissionCheck:portal_access');
                });

            });
        });
    });
});

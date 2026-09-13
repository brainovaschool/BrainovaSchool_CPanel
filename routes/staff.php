<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\DepartmentController;
use App\Http\Controllers\Staff\DesignationController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
    
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:staff_manage']], function () {
    
            // auth routes
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {
                
                Route::controller(DepartmentController::class)->prefix('department')->group(function () {
                    Route::get('/',                 'index')->name('department.index')->middleware('PermissionCheck:department_read');
                    Route::get('/create',           'create')->name('department.create')->middleware('PermissionCheck:department_create');
                    Route::post('/store',           'store')->name('department.store')->middleware('PermissionCheck:department_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('department.edit')->middleware('PermissionCheck:department_update');
                    Route::put('/update/{id}',      'update')->name('department.update')->middleware('PermissionCheck:department_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('department.delete')->middleware('PermissionCheck:department_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('department.bulk-delete')->middleware('PermissionCheck:department_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('department.bulk-status')->middleware('PermissionCheck:department_update', 'DemoCheck');
                });
                
                Route::controller(DesignationController::class)->prefix('designation')->group(function () {
                    Route::get('/',                 'index')->name('designation.index')->middleware('PermissionCheck:designation_read');
                    Route::get('/create',           'create')->name('designation.create')->middleware('PermissionCheck:designation_create');
                    Route::post('/store',           'store')->name('designation.store')->middleware('PermissionCheck:designation_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('designation.edit')->middleware('PermissionCheck:designation_update');
                    Route::put('/update/{id}',      'update')->name('designation.update')->middleware('PermissionCheck:designation_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('designation.delete')->middleware('PermissionCheck:designation_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('designation.bulk-delete')->middleware('PermissionCheck:designation_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('designation.bulk-status')->middleware('PermissionCheck:designation_update', 'DemoCheck');
                });
            });
        });
    });
});


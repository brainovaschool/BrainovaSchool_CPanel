<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\EmployeeController;
use App\Http\Controllers\Portal\TaskController;
use App\Http\Controllers\Portal\SettingsController;
use App\Http\Controllers\Portal\AttendanceController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::get('/portal', [PortalController::class, 'index'])->name('portal.index')->middleware('PermissionCheck:portal_access');

                Route::controller(EmployeeController::class)->prefix('portal/employees')->name('portal-employees.')->group(function () {
                    Route::get('/',          'index')->name('index')->middleware('PermissionCheck:portal_access');
                    Route::get('/show/{id}', 'show')->name('show')->middleware('PermissionCheck:portal_access');
                });

                Route::controller(TaskController::class)->prefix('portal/tasks')->name('portal-tasks.')->group(function () {
                    Route::get('/',                    'index')->name('index')->middleware('PermissionCheck:portal_manage');
                    Route::get('/create',               'create')->name('create')->middleware('PermissionCheck:portal_manage');
                    Route::post('/store',               'store')->name('store')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::get('/show/{id}',             'show')->name('show')->middleware('PermissionCheck:portal_access');
                    Route::post('/{id}/reassign',        'reassign')->name('reassign')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/accept',          'accept')->name('accept')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/submit',          'submit')->name('submit')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/start-review',    'startReview')->name('start-review')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/request-revision', 'requestRevision')->name('request-revision')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/approve',         'approve')->name('approve')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/comment',         'comment')->name('comment')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                });

                Route::get('/portal/my-tasks', [TaskController::class, 'myTasks'])->name('portal-my-tasks.index')->middleware('PermissionCheck:portal_access');

                Route::controller(SettingsController::class)->prefix('portal/settings')->name('portal-settings.')->group(function () {
                    Route::get('/',       'edit')->name('edit')->middleware('PermissionCheck:portal_manage');
                    Route::post('/',      'update')->name('update')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                });

                Route::get('/portal/attendance', [AttendanceController::class, 'index'])->name('portal-attendance.index')->middleware('PermissionCheck:portal_manage');

            });
        });
    });
});

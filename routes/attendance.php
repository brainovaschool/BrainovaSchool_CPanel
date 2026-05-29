<?php

use App\Http\Controllers\SubjectAttendanceController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Http\Controllers\Attendance\AttendanceController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Attendance\AttendaceNotificationController;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:attendance']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {
                Route::controller(AttendanceController::class)->prefix('attendance')->group(function () {
                    Route::get('/',                 'index')->name('attendance.index')->middleware('PermissionCheck:attendance_read');
                    Route::get('/subject-attendance', 'subjectAttendance')->name('attendance.subject-attendance')->middleware('PermissionCheck:attendance_read');
                    Route::post('/store',           'store')->name('attendance.store')->middleware('PermissionCheck:attendance_create');
                    Route::post('/subject-attendance', 'storeSubjectAttendance')->name('attendance.subject-attendance.store')->middleware('PermissionCheck:attendance_create');
                    Route::post('/search',          'searchStudents')->name('attendance.search');
                    Route::match(['get', 'post'], '/subject-attendance/search', 'searchAttendance')->name('attendance.student.search');
                    Route::get('/report',           'report')->name('attendance.report')->middleware('PermissionCheck:attendance_read');
                    Route::any('/report-search',    'reportSearch')->name('attendance.report-search')->middleware('PermissionCheck:attendance_read');

                });

                Route::controller(AttendaceNotificationController::class)->prefix('attendance-notification')->group(function () {
                    Route::get('/', 'notification')->name('attendance.notification')->middleware('PermissionCheck:attendance_read');
                    Route::post('/update', 'settinngUpdate')->name('attendance.settinngUpdate')->middleware('PermissionCheck:attendance_read');

                });

            });
        });
    });
});



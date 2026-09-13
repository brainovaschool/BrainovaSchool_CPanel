<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\ShiftController;
use App\Http\Controllers\Academic\ClassesController;
use App\Http\Controllers\Academic\SectionController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\ClassRoomController;
use App\Http\Controllers\Academic\ClassSetupController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Http\Controllers\Academic\ClassRoutineController;
use App\Http\Controllers\Academic\TimeScheduleController;
use App\Http\Controllers\Academic\SubjectAssignController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;





Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:academic']], function () {

            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {
                Route::controller(ClassesController::class)->prefix('classes')->group(function () {
                    Route::get('/',                 'index')->name('classes.index')->middleware('PermissionCheck:classes_read');
                    Route::get('/create',           'create')->name('classes.create')->middleware('PermissionCheck:classes_create');
                    Route::post('/store',           'store')->name('classes.store')->middleware('PermissionCheck:classes_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('classes.edit')->middleware('PermissionCheck:classes_update');
                    Route::get('/translate/{id}',        'translate')->name('classes.translate')->middleware('PermissionCheck:classes_update');
                    Route::post('/translateUpdate/{id}',        'translateUpdate')->name('classes.translateUpdate')->middleware('PermissionCheck:classes_update');
                    Route::put('/update/{id}',      'update')->name('classes.update')->middleware('PermissionCheck:classes_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('classes.delete')->middleware('PermissionCheck:classes_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('classes.bulk-delete')->middleware('PermissionCheck:classes_delete', 'DemoCheck');
                });

                Route::controller(SectionController::class)->prefix('section')->group(function () {
                    Route::get('/',                 'index')->name('section.index')->middleware('PermissionCheck:section_read');
                    Route::get('/create',           'create')->name('section.create')->middleware('PermissionCheck:section_create');
                    Route::post('/store',           'store')->name('section.store')->middleware('PermissionCheck:section_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('section.edit')->middleware('PermissionCheck:section_update');
                    Route::get('/translate/{id}',        'translate')->name('section.translate')->middleware('PermissionCheck:section_update');
                    Route::post('/translateUpdate/{id}',        'translateUpdate')->name('section.translateUpdate')->middleware('PermissionCheck:section_update');
                    Route::put('/update/{id}',      'update')->name('section.update')->middleware('PermissionCheck:section_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('section.delete')->middleware('PermissionCheck:section_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('section.bulk-delete')->middleware('PermissionCheck:section_delete', 'DemoCheck');
                });

                Route::controller(SubjectController::class)->prefix('subject')->group(function () {
                    Route::get('/',                 'index')->name('subject.index')->middleware('PermissionCheck:subject_read');
                    Route::get('/create',           'create')->name('subject.create')->middleware('PermissionCheck:subject_create');
                    Route::post('/store',           'store')->name('subject.store')->middleware('PermissionCheck:subject_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('subject.edit')->middleware('PermissionCheck:subject_update');
                    Route::put('/update/{id}',      'update')->name('subject.update')->middleware('PermissionCheck:subject_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('subject.delete')->middleware('PermissionCheck:subject_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('subject.bulk-delete')->middleware('PermissionCheck:subject_delete', 'DemoCheck');
                });

                Route::controller(ShiftController::class)->prefix('shift')->group(function () {
                    Route::get('/',                 'index')->name('shift.index')->middleware('PermissionCheck:shift_read');
                    Route::get('/create',           'create')->name('shift.create')->middleware('PermissionCheck:shift_create');
                    Route::post('/store',           'store')->name('shift.store')->middleware('PermissionCheck:shift_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('shift.edit')->middleware('PermissionCheck:shift_update');
                    Route::put('/update/{id}',      'update')->name('shift.update')->middleware('PermissionCheck:shift_update', 'DemoCheck');
                    Route::get('/translate/{id}',        'translate')->name('shift.translate')->middleware('PermissionCheck:shift_update');
                    Route::post('/translateUpdate/{id}',      'translateUpdate')->name('shift.translateUpdate')->middleware('PermissionCheck:shift_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('shift.delete')->middleware('PermissionCheck:shift_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('shift.bulk-delete')->middleware('PermissionCheck:shift_delete', 'DemoCheck');
                });

                Route::controller(ClassRoomController::class)->prefix('class-room')->group(function () {
                    Route::get('/',                 'index')->name('class-room.index')->middleware('PermissionCheck:class_room_read');
                    Route::get('/create',           'create')->name('class-room.create')->middleware('PermissionCheck:class_room_create');
                    Route::post('/store',           'store')->name('class-room.store')->middleware('PermissionCheck:class_room_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('class-room.edit')->middleware('PermissionCheck:class_room_update');
                    Route::put('/update/{id}',      'update')->name('class-room.update')->middleware('PermissionCheck:class_room_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('class-room.delete')->middleware('PermissionCheck:class_room_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('class-room.bulk-delete')->middleware('PermissionCheck:class_room_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('class-room.bulk-status')->middleware('PermissionCheck:class_room_update', 'DemoCheck');
                });

                Route::controller(ClassSetupController::class)->prefix('class-setup')->group(function () {
                    Route::get('/',                 'index')->name('class-setup.index')->middleware('PermissionCheck:class_setup_read');
                    Route::get('/create',           'create')->name('class-setup.create')->middleware('PermissionCheck:class_setup_create');
                    Route::post('/store',           'store')->name('class-setup.store')->middleware('PermissionCheck:class_setup_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('class-setup.edit')->middleware('PermissionCheck:class_setup_update');
                    Route::put('/update/{id}',      'update')->name('class-setup.update')->middleware('PermissionCheck:class_setup_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('class-setup.delete')->middleware('PermissionCheck:class_setup_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('class-setup.bulk-delete')->middleware('PermissionCheck:class_setup_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('class-setup.bulk-status')->middleware('PermissionCheck:class_setup_update', 'DemoCheck');
                    Route::get('/get-sections',     'getSections');
                });

                Route::controller(SubjectAssignController::class)->prefix('assign-subject')->group(function () {
                    Route::get('/',                 'index')->name('assign-subject.index')->middleware('PermissionCheck:subject_assign_read');
                    Route::get('/create',           'create')->name('assign-subject.create')->middleware('PermissionCheck:subject_assign_create');
                    Route::post('/store',           'store')->name('assign-subject.store')->middleware('PermissionCheck:subject_assign_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('assign-subject.edit')->middleware('PermissionCheck:subject_assign_update');
                    Route::put('/update/{id}',      'update')->name('assign-subject.update')->middleware('PermissionCheck:subject_assign_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('assign-subject.delete')->middleware('PermissionCheck:subject_assign_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('assign-subject.bulk-delete')->middleware('PermissionCheck:subject_assign_delete', 'DemoCheck');
                    Route::get('/add-subject-teacher','addSubjectTeacher');
                    Route::get('/show',              'show')->name('assign-subject.show')->middleware('PermissionCheck:subject_assign_read');
                    Route::get('/get-subjects',     'getSubjects');
                    Route::get('/check-section',     'checkSection');
                    Route::get('/check-exam-assign/{id}','checkExamAssign');
                });

                Route::controller(TimeScheduleController::class)->prefix('time/schedule')->group(function () {
                    Route::get('/',                 'index')->name('time_schedule.index')->middleware('PermissionCheck:time_schedule_read');
                    Route::get('/create',           'create')->name('time_schedule.create')->middleware('PermissionCheck:time_schedule_create');
                    Route::post('/store',           'store')->name('time_schedule.store')->middleware('PermissionCheck:time_schedule_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('time_schedule.edit')->middleware('PermissionCheck:time_schedule_update');
                    Route::put('/update/{id}',      'update')->name('time_schedule.update')->middleware('PermissionCheck:time_schedule_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('time_schedule.delete')->middleware('PermissionCheck:time_schedule_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('time_schedule.bulk-delete')->middleware('PermissionCheck:time_schedule_delete', 'DemoCheck');
                });
            });
        });

        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:routine']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(ClassRoutineController::class)->prefix('class-routine')->group(function () {
                    Route::get('/',                 'index')->name('class-routine.index')->middleware('PermissionCheck:class_routine_read');
                    Route::get('/create',           'create')->name('class-routine.create')->middleware('PermissionCheck:class_routine_create');
                    Route::post('/store',           'store')->name('class-routine.store')->middleware('PermissionCheck:class_routine_create');
                    Route::get('/edit/{id}',        'edit')->name('class-routine.edit')->middleware('PermissionCheck:class_routine_update');
                    Route::put('/update/{id}',      'update')->name('class-routine.update')->middleware('PermissionCheck:class_routine_update');
                    Route::delete('/delete/{id}',   'delete')->name('class-routine.delete')->middleware('PermissionCheck:class_routine_delete');
                    Route::get('/add-class-routine','addClassRoutine');

                    Route::get('/check-class-routine','checkClassRoutine');
                });

            });
        });
    });
});



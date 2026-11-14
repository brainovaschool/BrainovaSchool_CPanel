<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteSetup\ProgramClassAdminController;
use App\Http\Controllers\WebsiteSetup\ProgramClassController;
use App\Http\Controllers\WebsiteSetup\ProgramClassCoordinatorController;
use App\Http\Controllers\WebsiteSetup\ProgramClassGradeController;
use App\Http\Controllers\WebsiteSetup\ProgramClassMarkSubmissionController;
use App\Http\Controllers\WebsiteSetup\ProgramClassMaterialController;

/** The newer, simpler alternative to routes/class-content.php for
 *  program-based courses - a teacher adds/edits their own Program's
 *  classes directly (no Class/Section/Subject link needed), a Coordinator
 *  approves or sends them back. Authorization lives in the controllers
 *  (Program::teachers() / Staff::coordinatees()), not a PermissionCheck
 *  gate, since a Teacher reaching this has nothing to do with the
 *  class_content_* permission set. */
Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {

            // Same deliberately-outside-AdminPanel pattern as
            // class-content-material.download - the signed URL is the
            // access control.
            Route::get('/program-class/materials/{material}/download', [ProgramClassMaterialController::class, 'download'])
                ->name('program-class-material.download')
                ->middleware('signed');

            // A student needs this too (to open what they submitted), so
            // it sits outside AdminPanel the same way - the signed URL
            // itself is the access control.
            Route::get('/program-class/mark-submissions/{mark}/download', [ProgramClassMarkSubmissionController::class, 'download'])
                ->name('program-class-mark-submission.download')
                ->middleware('signed');

            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(ProgramClassController::class)->prefix('program-class')->name('program-class.')->group(function () {
                    Route::get('/{programId}/create',    'create')->name('create');
                    Route::post('/{programId}/store',     'store')->name('store')->middleware('DemoCheck');
                    Route::get('/edit/{id}',               'edit')->name('edit');
                    Route::put('/update/{id}',              'update')->name('update')->middleware('DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('delete')->middleware('DemoCheck');
                    Route::post('/submit/{id}',             'submit')->name('submit')->middleware('DemoCheck');
                });

                Route::controller(ProgramClassGradeController::class)->prefix('program-class')->name('program-class.')->group(function () {
                    Route::get('/{id}/grade',  'edit')->name('grade.edit');
                    Route::post('/{id}/grade', 'update')->name('grade.update')->middleware('DemoCheck');
                });

                Route::controller(ProgramClassCoordinatorController::class)->prefix('program-class-coordinator')->name('program-class-coordinator.')->group(function () {
                    Route::get('/',             'index')->name('index')->middleware('PermissionCheck:class_content_coordinator_review');
                    Route::get('/show/{id}',    'show')->name('show')->middleware('PermissionCheck:class_content_coordinator_review');
                    Route::post('/decide/{id}', 'decide')->name('decide')->middleware('PermissionCheck:class_content_coordinator_review', 'DemoCheck');
                });

                Route::post('/program-class-admin/decide/{id}', [ProgramClassAdminController::class, 'decide'])
                    ->name('program-class-admin.decide')->middleware('DemoCheck');

            });
        });
    });
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteSetup\ProgramClassAdminController;
use App\Http\Controllers\WebsiteSetup\ProgramClassController;
use App\Http\Controllers\WebsiteSetup\ProgramClassCoordinatorController;
use App\Http\Controllers\WebsiteSetup\ProgramClassGradeController;
use App\Http\Controllers\WebsiteSetup\ProgramClassGradingController;
use App\Http\Controllers\WebsiteSetup\ProgramClassMarkSubmissionController;
use App\Http\Controllers\WebsiteSetup\ProgramClassMaterialController;
use App\Http\Controllers\WebsiteSetup\ProgramClassActivityController;
use App\Http\Controllers\WebsiteSetup\ProgramClassFileController;
use App\Http\Controllers\Admin\HomeworkController;

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

            // Same pattern as program-class-material.download above.
            Route::get('/program-class/activities/{activity}/download', [ProgramClassActivityController::class, 'download'])
                ->name('program-class-activity.download')
                ->middleware('signed');

            // Project/Quiz/Worksheet/Assignment's own uploaded file - keyed
            // by {id, type} rather than a child row id, since these four are
            // single fields directly on the ProgramClass itself.
            Route::get('/program-class/{id}/file/{type}/download', [ProgramClassFileController::class, 'download'])
                ->name('program-class-file.download')
                ->middleware('signed');

            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(ProgramClassController::class)->prefix('program-class')->name('program-class.')->group(function () {
                    Route::get('/{programId}/create',    'create')->name('create');
                    Route::post('/{programId}/store',     'store')->name('store')->middleware('DemoCheck');
                    Route::get('/edit/{id}',               'edit')->name('edit');
                    Route::put('/update/{id}',              'update')->name('update')->middleware('DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('delete')->middleware('DemoCheck');
                    Route::post('/submit/{id}',             'submit')->name('submit')->middleware('DemoCheck');
                    Route::get('/{id}/quiz-analytics',      'quizAnalytics')->name('quiz-analytics');
                    // Same static Quiz_Template.csv as Homework's own sample
                    // download (homework.download-sample) - a separate route
                    // only because that one sits behind FeatureCheck:homework,
                    // which has nothing to do with whether this school uses
                    // Program Class quizzes at all.
                    Route::get('/quiz-sample-csv', [HomeworkController::class, 'downloadSample'])->name('quiz-sample-csv');
                });

                // Grade is scoped to a Program (every lesson in it,
                // filterable by student), not a single lesson - the
                // {programId} here is a program_id.
                Route::controller(ProgramClassGradeController::class)->prefix('program-class')->name('program-class.')->group(function () {
                    Route::get('/{programId}/grade',  'edit')->name('grade.edit');
                    Route::post('/{programId}/grade', 'update')->name('grade.update')->middleware('DemoCheck');
                });

                Route::get('/program-class-grading', [ProgramClassGradingController::class, 'index'])->name('program-class-grading.index');

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

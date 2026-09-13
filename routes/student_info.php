<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentInfo\StudentController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Http\Controllers\StudentInfo\ParentGuardianController;
use App\Http\Controllers\StudentInfo\PromoteStudentController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\StudentInfo\DisabledStudentController;
use App\Http\Controllers\StudentInfo\OnlineAdmissionController;
use App\Http\Controllers\StudentInfo\StudentCategoryController;
use App\Http\Controllers\StudentInfo\OnlineAdmissionSettingController;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {

        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:student_info']], function () {

            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(StudentController::class)->prefix('student')->group(function () {
                    Route::get('/',                 'index')->name('student.index')->middleware('PermissionCheck:student_read');
                    Route::any('/search',           'search')->name('student.search')->middleware('PermissionCheck:student_read');
                    Route::get('/create',           'create')->name('student.create')->middleware('PermissionCheck:student_create');
                    Route::post('/store',           'store')->name('student.store')->middleware('PermissionCheck:student_create', 'DemoCheck');
                    Route::get('edit/{id}',         'edit')->name('student.edit')->middleware('PermissionCheck:student_update');
                    Route::get('show/{id}',         'show')->name('student.show')->middleware('PermissionCheck:student_read');
                    Route::PUT('update',            'update')->name('student.update')->middleware('PermissionCheck:student_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('student.delete')->middleware('PermissionCheck:student_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('student.bulk-delete')->middleware('PermissionCheck:student_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('student.bulk-status')->middleware('PermissionCheck:student_update', 'DemoCheck');
                    Route::get('/get-children/{parentId}', 'getChildren')->name('student.getChildren')->middleware('PermissionCheck:student_read');


                    Route::get('/add-new-document',          'addNewDocument')->middleware('PermissionCheck:student_create');
                    Route::get('/get-students',              'getStudents')->middleware('PermissionCheck:student_read');
                    Route::get('/import',           'import')->name('student.import')->middleware('PermissionCheck:student_create');
                    Route::post('/import-submit', 'importSubmit')->name('student.importSubmit')->middleware('PermissionCheck:student_create');
                    Route::get('/sample-download',          'sampleDownload')->name('student.sampleDownload')->middleware('PermissionCheck:student_create');

                    // Route::get('/get-fees-collect-students', 'getFeesCollectStudents');
                    // Route::get('/get-fees-assign-students',  'getFeesAssignStudents');

                });

                Route::controller(StudentCategoryController::class)->prefix('student/category')->group(function () {
                    Route::get('/',                 'index')->name('student_category.index')->middleware('PermissionCheck:student_category_read');
                    Route::get('/create',           'create')->name('student_category.create')->middleware('PermissionCheck:student_category_create');
                    Route::post('/store',           'store')->name('student_category.store')->middleware('PermissionCheck:student_category_create', 'DemoCheck');
                    Route::get('edit/{id}',         'edit')->name('student_category.edit')->middleware('PermissionCheck:student_category_update');
                    Route::PUT('update/{id}',       'update')->name('student_category.update')->middleware('PermissionCheck:student_category_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('student_category.delete')->middleware('PermissionCheck:student_category_delete', 'DemoCheck');
                });

                Route::controller(PromoteStudentController::class)->prefix('promote/students')->group(function () {
                    Route::get('/',                 'index')->name('promote_students.index')->middleware('PermissionCheck:promote_students_read');
                    Route::post('/search',          'search')->name('promote_students.search')->middleware('PermissionCheck:promote_students_read');
                    Route::post('/store',           'store')->name('promote_students.store')->middleware('PermissionCheck:promote_students_create', 'DemoCheck');
                    Route::get('/get-class',        'getClass')->middleware('PermissionCheck:promote_students_read');
                    Route::get('/get-sections',     'getSections')->middleware('PermissionCheck:promote_students_read');
                });
                Route::controller(DisabledStudentController::class)->prefix('disabled/students')->group(function () {
                    Route::get('/',                 'index')->name('disabled_students.index')->middleware('PermissionCheck:disabled_students_read');
                    Route::post('/search',          'search')->name('disabled_students.search')->middleware('PermissionCheck:disabled_students_read');
                    Route::post('/store',           'store')->name('disabled_students.store')->middleware('PermissionCheck:disabled_students_create', 'DemoCheck');
                });

                Route::controller(ParentGuardianController::class)->prefix('parent')->group(function () {
                    Route::get('/',                 'index')->name('parent.index')->middleware('PermissionCheck:parent_read');
                    Route::any('/search',           'search')->name('parent.search')->middleware('PermissionCheck:parent_read');
                    Route::get('/create',           'create')->name('parent.create')->middleware('PermissionCheck:parent_create');
                    Route::post('/store',           'store')->name('parent.store')->middleware('PermissionCheck:parent_create', 'DemoCheck');
                    Route::get('edit/{id}',         'edit')->name('parent.edit')->middleware('PermissionCheck:parent_update');
                    Route::PUT('update/{id}',       'update')->name('parent.update')->middleware('PermissionCheck:parent_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('parent.delete')->middleware('PermissionCheck:parent_delete', 'DemoCheck');
                    Route::get('/get-parent',       'getParent')->middleware('PermissionCheck:parent_read');
                });
            });
        });

        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:online_admission']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {
                Route::controller(OnlineAdmissionController::class)->prefix('online-admissions')->group(function () {
                    Route::get('/',                 'index')->name('online-admissions.index')->middleware('PermissionCheck:admission_read');
                    Route::any('/search',           'search')->name('online-admissions.search')->middleware('PermissionCheck:admission_read');
                    Route::get('edit/{id}',         'edit')->name('online-admissions.edit')->middleware('PermissionCheck:admission_update');
                    Route::post('/store',           'store')->name('online-admissions.store')->middleware('PermissionCheck:admission_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('online-admissions.delete')->middleware('PermissionCheck:admission_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('online-admissions.bulk-delete')->middleware('PermissionCheck:admission_delete', 'DemoCheck');
                });

                Route::controller(OnlineAdmissionSettingController::class)->prefix('online-admissions-setting')->group(function () {
                    Route::get('/',                 'index')->name('online-admissions.setting.index')->middleware('PermissionCheck:admission_setting_update');
                    Route::post('/update',           'update')->name('online-admissions.setting.update');

                    Route::get('/fees',                 'fees')->name('online-admissions.setting.fees')->middleware('PermissionCheck:admission_setting_update');
                    Route::post('/fees/store',                 'feesStore')->name('online-admissions.setting.feesStore')->middleware('PermissionCheck:admission_setting_update');
                    Route::get('/fees/edit/{id}',                 'feesEdit')->name('online-admissions.setting.feesEdit')->middleware('PermissionCheck:admission_setting_update');
                    Route::post('/fees/update',                 'feesUpdate')->name('online-admissions.setting.feesUpdate')->middleware('PermissionCheck:admission_setting_update');
                    Route::delete('/fees-delete/{id}',   'delete')->name('online-admissions.setting.feesdelete');
                });
            });
        });

    });
});



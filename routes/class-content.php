<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassContent\ClassContentAdminController;
use App\Http\Controllers\ClassContent\ClassContentLessonController;
use App\Http\Controllers\ClassContent\ClassContentModuleController;
use App\Http\Controllers\ClassContent\ClassContentCoordinatorController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(ClassContentModuleController::class)->prefix('class-content')->name('class-content-module.')->group(function () {
                    Route::get('/',               'index')->name('index')->middleware('PermissionCheck:class_content_read');
                    Route::get('/create',         'create')->name('create')->middleware('PermissionCheck:class_content_create');
                    Route::post('/store',         'store')->name('store')->middleware('PermissionCheck:class_content_create', 'DemoCheck');
                    Route::get('/edit/{id}',      'edit')->name('edit')->middleware('PermissionCheck:class_content_update');
                    Route::put('/update/{id}',    'update')->name('update')->middleware('PermissionCheck:class_content_update', 'DemoCheck');
                    Route::delete('/delete/{id}', 'delete')->name('delete')->middleware('PermissionCheck:class_content_delete', 'DemoCheck');
                    Route::post('/submit/{id}',   'submit')->name('submit')->middleware('PermissionCheck:class_content_submit', 'DemoCheck');
                });

                Route::controller(ClassContentLessonController::class)->prefix('class-content/{moduleId}/lessons')->name('class-content-module.')->group(function () {
                    Route::get('/',               'index')->name('lessons')->middleware('PermissionCheck:class_content_read');
                    Route::get('/create',         'create')->name('lessons.create')->middleware('PermissionCheck:class_content_create');
                    Route::post('/store',         'store')->name('lessons.store')->middleware('PermissionCheck:class_content_create', 'DemoCheck');
                    Route::get('/edit/{id}',      'edit')->name('lessons.edit')->middleware('PermissionCheck:class_content_update');
                    Route::put('/update/{id}',    'update')->name('lessons.update')->middleware('PermissionCheck:class_content_update', 'DemoCheck');
                    Route::delete('/delete/{id}', 'delete')->name('lessons.delete')->middleware('PermissionCheck:class_content_delete', 'DemoCheck');
                });

                Route::controller(ClassContentCoordinatorController::class)->prefix('class-content-coordinator')->name('class-content-coordinator.')->group(function () {
                    Route::get('/',              'index')->name('index')->middleware('PermissionCheck:class_content_coordinator_review');
                    Route::get('/show/{id}',     'show')->name('show')->middleware('PermissionCheck:class_content_coordinator_review');
                    Route::post('/decide/{id}',  'decide')->name('decide')->middleware('PermissionCheck:class_content_coordinator_review', 'DemoCheck');
                });

                Route::controller(ClassContentAdminController::class)->prefix('class-content-admin')->name('class-content-admin.')->group(function () {
                    Route::get('/',              'index')->name('index')->middleware('PermissionCheck:class_content_approve');
                    Route::get('/show/{id}',     'show')->name('show')->middleware('PermissionCheck:class_content_approve');
                    Route::post('/decide/{id}',  'decide')->name('decide')->middleware('PermissionCheck:class_content_approve', 'DemoCheck');
                });

            });
        });
    });
});

<?php

use App\Http\Controllers\ParentLeaveController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Library\BookController;
use App\Http\Controllers\ParentPanel\FeesController;
use App\Http\Controllers\Library\IssueBookController;
use App\Http\Controllers\ParentPanel\ProfileController;
use App\Http\Controllers\ParentPanel\HomeworkController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Http\Controllers\ParentPanel\DashboardController;
use App\Http\Controllers\ParentPanel\MarksheetController;
use App\Http\Controllers\ParentPanel\AttendanceController;
use App\Http\Controllers\ParentPanel\ExamRoutineController;
use App\Http\Controllers\ParentPanel\SubjectListController;
use App\Http\Controllers\ParentPanel\ClassRoutineController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\VehicleTracker\Http\Controllers\ParentPanelTransportController;
use Modules\VehicleTracker\Http\Controllers\StudentPanelTransportController;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => 'ParentPanel'], function () {
                Route::group(['middleware' => ['auth.routes']], function () {

                    Route::controller(DashboardController::class)->prefix('parent-panel-dashboard')->group(function () {
                        Route::get('/', 'index')->name('parent-panel-dashboard.index');
                        Route::post('/search', 'search')->name('parent-panel-student.search');
                        Route::post('search-parent-menu-data', 'searchParentMenuData')->name('search-parent-menu-data');
                        Route::get('/learning-guide', 'learningGuide')->name('parent-panel-dashboard.learning-guide');
                    });

                    Route::controller(ProfileController::class)->prefix('parent-panel')->group(function () {
                        Route::get('/profile',              'profile')->name('parent-panel.profile');
                        Route::get('/profile/edit',         'edit')->name('parent-panel.profile.edit');
                        Route::put('/profile/update',       'update')->name('parent-panel.profile.update')->middleware('DemoCheck');

                        Route::get('/password/update',      'passwordUpdate')->name('parent-panel.password-update');
                        Route::put('/password/update/store', 'passwordUpdateStore')->name('parent-panel.password-update-store')->middleware('DemoCheck');
                    });

                    Route::group(['middleware' => ['FeatureCheck:academic']], function () {
                        Route::controller(SubjectListController::class)->prefix('parent-panel-subject-list')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-subject-list.index');
                            Route::post('/search', 'search')->name('parent-panel-subject-list.search');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:routine']], function () {
                        Route::controller(ClassRoutineController::class)->prefix('parent-panel-class-routine')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-class-routine.index');
                            Route::post('/search', 'search')->name('parent-panel-class-routine.search');
                            Route::get('/pdf-generate/{student}', 'generatePDF')->name('parent-panel-class-routine.pdf-generate');
                        });
                        Route::controller(ExamRoutineController::class)->prefix('parent-panel-exam-routine')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-exam-routine.index');
                            Route::post('/search', 'search')->name('parent-panel-exam-routine.search');
                            Route::get('/exam-types', 'getExamTypes');
                            Route::get('/pdf-generate/{student}/{type}', 'generatePDF')->name('parent-panel-exam-routine.pdf-generate');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:report']], function () {
                        Route::controller(MarksheetController::class)->prefix('parent-panel-marksheet')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-marksheet.index');
                            Route::post('/search', 'search')->name('parent-panel-marksheet.search');
                            Route::get('/exam-types', 'getExamTypes');
                            Route::get('/pdf-generate/{student}/{type}', 'generatePDF')->name('parent-panel-marksheet.pdf-generate');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:fees']], function () {
                        Route::controller(FeesController::class)->prefix('parent-panel-fees')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-fees.index');
                            Route::post('/search', 'search')->name('parent-panel-fees.search');
                            Route::get('pay-modal', 'payModal');
                            Route::post('pay-with-stripe', 'payWithStripe')->name('parent-panel-fees.pay-with-stripe');
                            Route::get('pay-with-paypal', 'payWithPaypal')->name('parent-panel-fees.pay-with-paypal');
                            Route::get('payment-success', 'paymentSuccess')->name('parent-panel-fees.payment.success');
                            Route::get('payment-cancel', 'paymentCancel')->name('parent-panel-fees.payment.cancel');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:attendance']], function () {
                        Route::controller(AttendanceController::class)->prefix('parent-panel-attendance')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-attendance.index');
                            Route::any('/search', 'search')->name('parent-panel-attendance.search');
                        });

                        Route::controller(HomeworkController::class)->prefix('parent-panel-homeworks')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-homeworks.index');
                            Route::any('/search', 'search')->name('parent-panel-homeworks.search');
                        });

                    });

                    Route::controller(DashboardController::class)->group(function () {
                        Route::get('parent-panel-notices/', 'notices')->name('parent-panel-notices.index');
                    });

                    Route::controller(BookController::class)->group(function () {
                        Route::get('parent/panel/books', 'indexParent')->name('parent-panel-book.index');
                    });

                    Route::controller(IssueBookController::class)->group(function () {
                        Route::get('parent/panel/issue-books', 'indexParent')->name('parent-panel-issue-books.index');
                    });

                    Route::controller(ParentPanelTransportController::class)->prefix('parent-panel-transport')->group(function () {
                        Route::get('/schdule', 'schdule')->name('parent-panel-transport.schdule');
                        Route::get('/report', 'report')->name('parent-panel-transport.report');
                        Route::get('/report-details/{id}', 'reportDetails')->name('parent-panel-transport.report-details');
                        Route::get('/livetrack', 'livetrack')->name('parent-panel-transport.livetrack');
                    });



                    Route::prefix('parent')->name('parent.')->group(function () {
                        Route::prefix('leave')->controller(ParentLeaveController::class)->name('leave.')->group(function () {
                            Route::get('/', 'index')->name('index');
                            Route::post('/store', 'store')->name('store');
                            Route::get('/edit/{id}', 'edit')->name('edit');
                            Route::delete('/delete/{id}', 'destroy')->name('destroy');
                        });
                    });



                });
            });
        });
    });
});



<?php

use App\Http\Controllers\StudentLeaveController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Library\BookController;
use App\Http\Controllers\Library\IssueBookController;
use App\Http\Controllers\StudentPanel\FeesController;
use App\Repositories\StudentPanel\AttendanceRepository;
use App\Http\Controllers\StudentPanel\ProfileController;
use App\Http\Controllers\StudentPanel\AiHelpController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Http\Controllers\StudentPanel\HomeworkController;
use App\Http\Controllers\StudentPanel\DashboardController;
use App\Http\Controllers\StudentPanel\MarksheetController;
use App\Http\Controllers\StudentPanel\AttendanceController;
use App\Http\Controllers\StudentPanel\OnlineExamController;
use App\Http\Controllers\StudentPanel\ExamRoutineController;
use App\Http\Controllers\StudentPanel\SubjectListController;
use App\Http\Controllers\StudentPanel\ClassRoutineController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\VehicleTracker\Http\Controllers\StudentPanelTransportController;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => 'StudentPanel'], function () {
                Route::group(['middleware' => ['auth.routes']], function () {

                    Route::controller(DashboardController::class)->prefix('student-panel-dashboard')->group(function () {
                        Route::get('/', 'index')->name('student-panel-dashboard.index');
                        Route::post('search-student-menu-data', 'searchStudentMenuData')->name('search-student-menu-data');
                        Route::post('daily-goals', 'saveDailyGoals')->name('student-panel-dashboard.save-daily-goals');
                    });

                    Route::controller(AiHelpController::class)->prefix('student-panel-ai-help')->group(function () {
                        Route::get('/',       'index')->name('student-panel-ai-help.index');
                        Route::post('/ask',   'ask')->name('student-panel-ai-help.ask');
                    });

                    Route::controller(ProfileController::class)->prefix('student-panel')->group(function () {
                        Route::get('/profile',              'profile')->name('student-panel.profile');
                        Route::get('/profile/edit',         'edit')->name('student-panel.profile.edit');
                        Route::put('/profile/update',       'update')->name('student-panel.profile.update')->middleware('DemoCheck');

                        Route::get('/password/update',      'passwordUpdate')->name('student-panel.password-update');
                        Route::put('/password/update/store', 'passwordUpdateStore')->name('student-panel.password-update-store')->middleware('DemoCheck');
                    });

                    Route::group(['middleware' => ['FeatureCheck:academic']], function () {
                        Route::controller(SubjectListController::class)->prefix('student-panel-subject-list')->group(function () {
                            Route::get('/', 'index')->name('student-panel-subject-list.index');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:routine']], function () {
                        Route::controller(ClassRoutineController::class)->prefix('student-panel-class-routine')->group(function () {
                            Route::get('/', 'index')->name('student-panel-class-routine.index');
                            Route::get('/pdf-generate', 'generatePDF')->name('student-panel-class-routine.pdf-generate');
                        });

                        Route::controller(ExamRoutineController::class)->prefix('student-panel-exam-routine')->group(function () {
                            Route::get('/', 'index')->name('student-panel-exam-routine.index');
                            Route::post('/search', 'search')->name('student-panel-exam-routine.search');
                            Route::get('/pdf-generate/{type}', 'generatePDF')->name('student-panel-exam-routine.pdf-generate');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:online_examination']], function () {
                        Route::controller(OnlineExamController::class)->prefix('student-panel-online-examination')->group(function () {
                            Route::get('/', 'index')->name('student-panel-online-examination.index');
                            Route::get('/view/{id}', 'view')->name('student-panel-online-examination.view');
                            Route::get('/result-view/{id}', 'resultView')->name('student-panel-online-examination.result-view');
                            Route::post('/answer-submit', 'answerSubmit')->name('student-panel-online-examination.answer-submit');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:report']], function () {
                        Route::controller(MarksheetController::class)->prefix('student-panel-marksheet')->group(function () {
                            Route::get('/', 'index')->name('student-panel-marksheet.index');
                            Route::post('/search', 'search')->name('student-panel-marksheet.search');
                            Route::get('/pdf-generate/{type}', 'generatePDF')->name('student-panel-marksheet.pdf-generate');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:attendance']], function () {
                        Route::controller(AttendanceController::class)->prefix('student-panel-attendance')->group(function () {
                            Route::get('/', 'index')->name('student-panel-attendance.index');
                            Route::any('/search', 'search')->name('student-panel-attendance.search');
                            Route::post('/attendance', 'attendance')->name('student-panel-attendance.attendance');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:fees']], function () {
                        Route::controller(FeesController::class)->prefix('student-panel-fees')->group(function () {
                            Route::get('/', 'index')->name('student-panel-fees.index');
                            Route::get('pay-modal', 'payModal');
                            Route::post('pay-with-stripe', 'payWithStripe')->name('student-panel-fees.pay-with-stripe');
                            Route::get('pay-with-paypal', 'payWithPaypal')->name('student-panel-fees.pay-with-paypal');
                            Route::get('payment-success', 'paymentSuccess')->name('student-panel-fees.payment.success');
                            Route::get('payment-cancel', 'paymentCancel')->name('student-panel-fees.payment.cancel');
                        });
                    });

                    Route::controller(HomeworkController::class)->group(function () {
                        Route::get('stundet/panel/homeworks', 'index')->name('student-panel-homeworks.index');
                        Route::post('stundet/panel/homework/submit/', 'submit')->name('student-panel.homework.submit');

                        Route::get('stundet/panel/homework-answer/{id}', 'homeworkAnswer')->name('student-panel-homeworks.answer');
                        Route::post('stundet/panel/homework-answer-submit', 'homeworkAnswerSubmit')->name('student-panel-homeworks.answer-submit');

                    });

                    Route::controller(BookController::class)->group(function () {
                        Route::get('stundet/panel/books', 'indexStudent')->name('student-panel-book.index');
                    });

                    Route::controller(IssueBookController::class)->group(function () {
                        Route::get('stundet/panel/issue-books', 'indexStudent')->name('student-panel-issue-books.index');
                    });

                    Route::controller(DashboardController::class)->group(function () {
                        Route::get('student-panel-gmeet/', 'gmeet')->name('student-panel-gmeet.index');
                    });

                    Route::controller(DashboardController::class)->group(function () {
                        Route::get('student-panel-notices/', 'notices')->name('student-panel-notices.index');
                    });

                    Route::controller(StudentLeaveController::class)->group(function () {
                        Route::get('student-panel-leave/', 'index')->name('student-panel-leave.index');
                    });

                    Route::prefix('student')->name('student.')->group(function () {
                        Route::prefix('leave')->controller(StudentLeaveController::class)->name('leave.')->group(function () {
                            Route::get('/', 'index')->name('index');
                            Route::post('/store', 'store')->name('store');
                            Route::get('/edit/{id}', 'edit')->name('edit');
                            Route::delete('/delete/{id}', 'destroy')->name('destroy');
                        });
                    });



                    Route::controller(StudentPanelTransportController::class)->prefix('student-panel-transport')->group(function () {
                        Route::get('/schdule', 'schdule')->name('student-panel-transport.schdule');
                        Route::get('/report', 'report')->name('student-panel-transport.report');
                        Route::get('/report-details/{id}', 'reportDetails')->name('student-panel-transport.report-details');
                        Route::get('/livetrack', 'livetrack')->name('student-panel-transport.livetrack');
                    });

                });
            });
        });
    });
});


Route::get('student-class-routine-pdf',                     [ClassRoutineController::class, 'generatePDF']);
Route::get('student-exam-routine-pdf/{exam_type_id}',       [ExamRoutineController::class, 'examRoutinePDF']);
Route::get('student-marksheet-pdf/{exam_type_id}',          [MarksheetController::class, 'generatePDF'])->name('student.marksheet-pdf');

Route::get('student-fees/pay-with-stripe/{fee_assign_children_id}', [FeesController::class, 'studentFeesPayWithStripe'])->name('student-fees.pay-with-stripe');
Route::post('student-fees/pay-with-stripe/store', [FeesController::class, 'studentFeesPayWithStripeStore'])->name('student-fees.pay-with-stripe.store');

Route::get('student-fees/pay-with-paypal/{fee_assign_children_id}', [FeesController::class, 'studentFeesPayWithPayPal'])->name('student-fees.pay-with-paypal');
Route::get('student-fees/paypal-payment-success',   [FeesController::class, 'studentFeesPayPalPaymentSuccess'])->name('student-fees.paypal-payment-success');
Route::get('student-fees/payment-success',   [FeesController::class, 'studentFeesPaymentSuccess'])->name('student-fees.payment-success');
Route::get('student-fees/payment-cancel',    [FeesController::class, 'studentFeesPaymentCancel'])->name('student-fees.payment-cancel');
Route::get('student-fees/payment-error',     [FeesController::class, 'studentFeesPaymentError'])->name('student-fees.payment-error');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GmeetController;
use App\Http\Controllers\Admin\IdCardController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\SmsMailLogController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\NoticeBoardController;
use App\Http\Controllers\Admin\ModuleInstallController;
use App\Http\Controllers\Admin\SmsMailTemplateController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:account']], function () {
            // auth routes
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {
                Route::controller(HomeworkController::class)->group(function () {
                    Route::get('homework/',                 'index')->name('homework.index')->middleware('PermissionCheck:homework_read');
                    Route::get('homework-question-group/',  'questionGroup')->name('homework.question-group')->middleware('PermissionCheck:question_group_read');
                    Route::get('homework-question-list/',  'questionList')->name('homework.question-list')->middleware(['PermissionCheck:homework_read', 'DenyTeacherRole']);
                    Route::get('homework-question-create/',  'questionCreate')->name('homework.question-create')->middleware('PermissionCheck:question_group_read');
                    Route::get('homework/quiz-analytics/{id}', 'quizAnalytics')->name('homework.quiz-analytics')->middleware('PermissionCheck:homework_read');
                    Route::get('homework/export-results/{id}', 'exportResults')->name('homework.export-results')->middleware('PermissionCheck:homework_read');
                    Route::get('homework/get-all-questions',  'getAllQuestions')->name('homework.get-all-questions')->middleware('PermissionCheck:homework_read');
                    Route::get('homework-download-sample', [HomeworkController::class, 'downloadSample'])->name('homework.download-sample');
                    
                    // AJAX endpoints for filtered reporting dashboard
                    Route::get('homework/ajax/global-stats',      'getGlobalStats')->name('homework.ajax.global-stats');
                    Route::post('homework/ajax/filtered-report',   'getFilteredReport')->name('homework.ajax.filtered-report');
                    Route::get('homework/ajax/sections-by-class',  'getSectionsByClass')->name('homework.ajax.sections-by-class');
                    Route::get('homework/ajax/subjects-by-section', 'getSubjectsBySection')->name('homework.ajax.subjects-by-section');
                    
                    // Remove the middleware temporarily for a quick test
                    //Route::get('homework/get-all-questions', 'getAllQuestions')->name('homework.get-all-questions');
                    
                    
                    Route::get('homework-answer-sheet/{id}/{student_id}',  'viewAnswerSheet')->name('homework.answer.sheet')->middleware('PermissionCheck:homework_read');
                    Route::get('homework/view-questions',  'viewQuestions')->name('homework.view-questions')->middleware('PermissionCheck:homework_read');
                    Route::post('homework/quiz-question/skill', 'updateQuizQuestionSkill')->name('homework.quiz-question.skill')->middleware('PermissionCheck:homework_update', 'DemoCheck');
                    Route::any('homework/search',           'search')->name('homework.search')->middleware('PermissionCheck:homework_read');
                    Route::get('homework/create',           'create')->name('homework.create')->middleware('PermissionCheck:homework_create');
                    Route::post('homework/store',           'store')->name('homework.store')->middleware('PermissionCheck:homework_create', 'DemoCheck');
                    Route::get('homework/edit/{id}',        'edit')->name('homework.edit')->middleware('PermissionCheck:homework_update');
                    Route::put('homework/update/{id}',      'update')->name('homework.update')->middleware('PermissionCheck:homework_update', 'DemoCheck');
                    Route::delete('homework/delete/{id}',   'delete')->name('homework.delete')->middleware('PermissionCheck:homework_delete', 'DemoCheck');
                  
                    Route::POST('homework/students',   'students');
                    Route::POST('homework/evaluation/submit',   'evaluationSubmit')->name('homework.evaluation.submit');
                     Route::POST('homework/mark/submit',   'markSubmit')->name('homework.mark.submit');
                });

                Route::controller(IdCardController::class)->group(function () {
                    Route::get('idcard/',                 'index')->name('idcard.index')->middleware('PermissionCheck:id_card_read');
                    Route::get('idcard/create',           'create')->name('idcard.create')->middleware('PermissionCheck:id_card_create');
                    Route::post('idcard/store',           'store')->name('idcard.store')->middleware('PermissionCheck:id_card_create');
                    Route::get('idcard/edit/{id}',           'edit')->name('idcard.edit')->middleware('PermissionCheck:id_card_update');
                    Route::put('idcard/update/{id}',           'update')->name('idcard.update')->middleware('PermissionCheck:id_card_update');
                    Route::delete('idcard/delete/{id}',   'delete')->name('idcard.delete')->middleware('PermissionCheck:id_card_delete', 'DemoCheck');

                    Route::POST('idcard/preview',   'preview');

                    Route::get('idcard/generate',                 'generate')->name('idcard.generate')->middleware('PermissionCheck:id_card_generate_read');
                    Route::post('idcard/generate',                 'generateSearch')->name('idcard.generate.search')->middleware('PermissionCheck:id_card_generate_read');
                });

                Route::controller(CertificateController::class)->group(function () {
                    Route::get('certificate/',                 'index')->name('certificate.index')->middleware('PermissionCheck:certificate_read');
                    Route::get('certificate/create',           'create')->name('certificate.create')->middleware('PermissionCheck:certificate_create');
                    Route::post('certificate/store',           'store')->name('certificate.store')->middleware('PermissionCheck:certificate_create');
                    Route::get('certificate/edit/{id}',           'edit')->name('certificate.edit')->middleware('PermissionCheck:certificate_update');
                    Route::put('certificate/update/{id}',           'update')->name('certificate.update')->middleware('PermissionCheck:certificate_update');
                    Route::delete('certificate/delete/{id}',   'delete')->name('certificate.delete')->middleware('PermissionCheck:certificate_delete', 'DemoCheck');

                    Route::POST('certificate/preview',   'preview');

                    Route::get('certificate/generate',                 'generate')->name('certificate.generate')->middleware('PermissionCheck:certificate_read');
                    Route::post('certificate/generate',                 'generateSearch')->name('certificate.generate.search')->middleware('PermissionCheck:certificate_read');
                });

                Route::controller(GmeetController::class)->group(function () {
                    Route::get('liveclass/gmeet/',                 'index')->name('gmeet.index')->middleware('PermissionCheck:gmeet_read');
                    Route::get('liveclass/gmeet/create',           'create')->name('gmeet.create')->middleware('PermissionCheck:gmeet_create');
                    Route::post('liveclass/gmeet/store',           'store')->name('gmeet.store')->middleware('PermissionCheck:gmeet_create');
                    Route::get('liveclass/gmeet/edit/{id}',        'edit')->name('gmeet.edit')->middleware('PermissionCheck:gmeet_update');
                    Route::put('liveclass/gmeet/update/{id}',      'update')->name('gmeet.update')->middleware('PermissionCheck:gmeet_update');
                    Route::delete('liveclass/gmeet/delete/{id}',   'delete')->name('gmeet.delete')->middleware('PermissionCheck:gmeet_delete', 'DemoCheck');

                    Route::any('liveclass/gmeet/search',           'search')->name('gmeet.search')->middleware('PermissionCheck:gmeet_read');
                });

                Route::controller(NoticeBoardController::class)->group(function () {
                    Route::get('communication/notice-board/',                 'index')->name('notice-board.index')->middleware('PermissionCheck:notice_board_read');
                    Route::get('communication/notice-board/create',           'create')->name('notice-board.create')->middleware('PermissionCheck:notice_board_create');
                    Route::post('communication/notice-board/store',           'store')->name('notice-board.store')->middleware('PermissionCheck:notice_board_create');
                    Route::get('communication/notice-board/edit/{id}',        'edit')->name('notice-board.edit')->middleware('PermissionCheck:notice_board_update');
                    Route::put('communication/notice-board/update/{id}',      'update')->name('notice-board.update')->middleware('PermissionCheck:notice_board_update');
                    Route::delete('communication/notice-board/delete/{id}',   'delete')->name('notice-board.delete')->middleware('PermissionCheck:notice_board_delete', 'DemoCheck');
                    Route::get('/translate/{id}',                'translate')->name('notice-board.translate')->middleware('PermissionCheck:notice_board_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('notice-board.translate.update')->middleware('PermissionCheck:notice_board_update');
                });

                Route::controller(SmsMailTemplateController::class)->group(function () {
                    Route::get('communication/template/',                 'index')->name('template.index')->middleware('PermissionCheck:sms_mail_template_read');
                    Route::get('communication/template/create',           'create')->name('template.create')->middleware('PermissionCheck:sms_mail_template_create');
                    Route::post('communication/template/store',           'store')->name('template.store')->middleware('PermissionCheck:sms_mail_template_create');
                    Route::get('communication/template/edit/{id}',        'edit')->name('template.edit')->middleware('PermissionCheck:sms_mail_template_update');
                    Route::put('communication/template/update/{id}',      'update')->name('template.update')->middleware('PermissionCheck:sms_mail_template_update');
                    Route::delete('communication/template/delete/{id}',   'delete')->name('template.delete')->middleware('PermissionCheck:sms_mail_template_delete', 'DemoCheck');
                });

                Route::controller(SmsMailLogController::class)->group(function () {
                    Route::get('communication/smsmail/',                 'index')->name('smsmail.index')->middleware('PermissionCheck:sms_mail_read_read');
                    Route::get('communication/smsmail/create',           'create')->name('smsmail.create')->middleware('PermissionCheck:sms_mail_read_send');
                    Route::post('communication/smsmail/store',           'store')->name('smsmail.store')->middleware('PermissionCheck:sms_mail_read_send');
                    Route::get('communication/smsmail/edit/{id}',        'edit')->name('smsmail.edit')->middleware('PermissionCheck:sms_mail_read_update');
                    Route::put('communication/smsmail/update/{id}',      'update')->name('smsmail.update')->middleware('PermissionCheck:sms_mail_read_update');
                    Route::delete('communication/smsmail/delete/{id}',   'delete')->name('smsmail.delete')->middleware('PermissionCheck:sms_mail_read_delete', 'DemoCheck');

                    Route::get('communication/smsmail/users',        'users')->name('smsmail.users')->middleware('PermissionCheck:sms_mail_read_read');
                    Route::get('communication/smsmail/template',        'template')->name('smsmail.template')->middleware('PermissionCheck:sms_mail_read_read');
                });

                Route::controller(ModuleInstallController::class)->group(function () {
                    Route::get('admin/module/install/{moduleName}', 'moduleInstall');
                });
            });
        });
    });
});

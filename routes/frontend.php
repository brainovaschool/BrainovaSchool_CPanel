<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\FrontendController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {

        Route::group(['middleware' => ['lang']], function () {

            Route::group(['controller' => FrontendController::class], function () {

                Route::get('/',            'index')->name('frontend.home');
                Route::get('/get-classes',      'getClasses');
                Route::get('/get-sections',     'getSections');
                Route::get('/get-exam-type',    'getExamType');
                Route::get('/result',           'result')->name('frontend.result');
                Route::post('/result',          'searchResult')->name('frontend.result.search');
                Route::get('/pdf-download/{id}/{type}/{class}/{section}', 'downloadPDF')->name('frontend.result.pdf-download');

                Route::get('/about',            'about')->name('frontend.about');
                Route::get('/news',             'news')->name('frontend.news');
                Route::get('/news-detail/{id}', 'newsDetail')->name('frontend.news-detail');
                Route::get('/events',           'events')->name('frontend.events');
                Route::get('/courses',          'courses')->name('frontend.courses');
                Route::get('/courses/{slug}',   'courseDetail')->name('frontend.course-detail');
                Route::get('/event-detail/{id}','eventDetail')->name('frontend.events-detail');

                Route::get('/notices',           'notices')->name('frontend.notices');
                Route::get('/notice-detail/{id}','noticeDetail')->name('frontend.notice-detail');

                Route::get('/contact',          'contact')->name('frontend.contact');
                Route::get('/online-admission', 'onlineAdmission')->name('frontend.online-admission');
                Route::get('/online-admission-fees/{id}/{ref}', 'onlineAdmissionFees')->name('frontend.online-admission-fees');

                Route::post('/contact',         'storeContact')->name('frontend.contact.store');
                Route::post('/subscribe',       'storeSubscribe')->name('frontend.subscribe');
                Route::post('/online-admission','storeOnlineAdmission')->name('frontend.online-admission.store');
                Route::post('/online-admission-fees','storeOnlineAdmissionFees')->name('frontend.online-admission-fees-store');

                Route::get('/page/{slug}',          'page')->name('frontend.page');

            });
        });

    });
});

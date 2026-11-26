<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\AssessmentBookingController;
use App\Http\Controllers\Frontend\MeetingBookingController;
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

                Route::get('/test-video-page',  'testVideoPage')->name('frontend.test-video-page');
                Route::get('/watch-and-learn',  'watchAndLearn')->name('frontend.watch-and-learn');
                Route::get('/knowledge-hub',    'knowledgeHub')->name('frontend.knowledge-hub');
                Route::get('/knowledge-hub/{slug}', 'knowledgeHubPage')->name('frontend.knowledge-hub-page');
                Route::get('/about',            'about')->name('frontend.about');
                Route::get('/our-approach',     'ourApproach')->name('frontend.our-approach');
                Route::get('/news',             'news')->name('frontend.news');
                Route::get('/news-detail/{id}', 'newsDetail')->name('frontend.news-detail');
                Route::get('/events',           'events')->name('frontend.events');
                Route::get('/courses',          'courses')->name('frontend.courses');
                Route::get('/programs/{category}', 'programCategory')->name('frontend.program-category');
                Route::get('/courses/{slug}',   'courseDetail')->name('frontend.course-detail');
                Route::get('/event-detail/{id}','eventDetail')->name('frontend.events-detail');

                Route::get('/notices',           'notices')->name('frontend.notices');
                Route::get('/notice-detail/{id}','noticeDetail')->name('frontend.notice-detail');

                Route::get('/testimonials',       'testimonialsPage')->name('frontend.testimonials');
                Route::get('/reviews',            'testimonialsPage')->name('frontend.reviews');
                Route::get('/demo-class',  'bookFreeTrial')->name('frontend.book-free-trial');
                Route::post('/demo-class', 'storeFreeTrial')->name('frontend.book-free-trial.store');

                Route::get('/book-assessment',  [AssessmentBookingController::class, 'show'])->name('frontend.book-assessment');
                Route::post('/book-assessment', [AssessmentBookingController::class, 'store'])->name('frontend.book-assessment.store');

                // Simple registration, no slot/calendar - the assessment is
                // taken whenever the parent opens the emailed link. The
                // slot-booking page above is reserved for a later step
                // (booking a meeting with the teacher once a result exists).
                Route::get('/register-assessment',  [AssessmentBookingController::class, 'registerShow'])->name('frontend.register-assessment');
                Route::post('/register-assessment', [AssessmentBookingController::class, 'registerStore'])->name('frontend.register-assessment.store');

                // Same-origin ping from the Assessment System's own JS
                // right after a test is submitted - see
                // AssessmentBookingController::completed() and
                // views_run.js's submitRun(). GET, not POST: no CSRF token
                // exists on that static page, and this is read-plus-notify,
                // not a form submission.
                Route::get('/assessment-completed', [AssessmentBookingController::class, 'completed'])->name('frontend.assessment-completed');

                // The streamlined "book a meeting" step a lead's parent
                // reaches from their result email - identity carried by
                // the link's own signature, so 'signed' (not a login)
                // guards it. Never re-asks the details already on file;
                // see MeetingBookingController.
                Route::get('/book-meeting/{lead}', [MeetingBookingController::class, 'show'])->name('frontend.book-meeting')->middleware('signed');
                Route::post('/book-meeting/{lead}', [MeetingBookingController::class, 'store'])->name('frontend.book-meeting.store')->middleware('signed');

                // The one-tap "I'll be there" link in the 1-hour-before
                // reminder email (AssessmentMeetingReminderCron) - no
                // login, same signed-link identity as the two routes
                // above.
                Route::get('/confirm-meeting/{lead}', [MeetingBookingController::class, 'confirm'])->name('frontend.confirm-meeting')->middleware('signed');

                Route::get('/contact',          'contact')->name('frontend.contact');
                Route::get('/online-admission', 'onlineAdmission')->name('frontend.online-admission');
                Route::get('/online-admission-fees/{id}/{ref}', 'onlineAdmissionFees')->name('frontend.online-admission-fees');

                Route::post('/contact',         'storeContact')->name('frontend.contact.store');
                Route::post('/subscribe',       'storeSubscribe')->name('frontend.subscribe');
                Route::post('/waitlist',        'storeWaitlist')->name('frontend.waitlist.store');
                Route::post('/online-admission','storeOnlineAdmission')->name('frontend.online-admission.store');
                Route::post('/online-admission-fees','storeOnlineAdmissionFees')->name('frontend.online-admission-fees-store');

                Route::get('/page/{slug}',          'page')->name('frontend.page');

                Route::get('/homeschool-ai-preview',                  'aiHelperShow')->name('frontend.ai-helper.show');
                Route::post('/homeschool-ai-preview/generate',        'aiHelperGenerate')->name('frontend.ai-helper.generate');
                Route::post('/homeschool-ai-preview/generate-visual', 'aiHelperGenerateVisual')->name('frontend.ai-helper.generate-visual');
                Route::post('/homeschool-ai-preview/generate-slides', 'aiHelperGenerateSlides')->name('frontend.ai-helper.generate-slides');

            });
        });

    });
});

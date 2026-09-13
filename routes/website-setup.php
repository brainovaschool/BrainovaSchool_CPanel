<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteSetup\NewsController;
use App\Http\Controllers\WebsiteSetup\AboutController;
use App\Http\Controllers\WebsiteSetup\EventController;
use App\Http\Controllers\WebsiteSetup\SliderController;
use App\Http\Controllers\WebsiteSetup\CounterController;
use App\Http\Controllers\WebsiteSetup\GalleryController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Http\Controllers\WebsiteSetup\SectionsController;
use App\Http\Controllers\WebsiteSetup\SubscribeController;
use App\Http\Controllers\WebsiteSetup\ContactInfoController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\WebsiteSetup\ContactMessageController;
use App\Http\Controllers\WebsiteSetup\GalleryCategoryController;
use App\Http\Controllers\WebsiteSetup\DepartmentContactController;
use App\Http\Controllers\WebsiteSetup\PagesController;
use App\Http\Controllers\WebsiteSetup\ProgramController;
use App\Http\Controllers\WebsiteSetup\ProgramFocusController;
use App\Http\Controllers\WebsiteSetup\ProgramCategoryController;
use App\Http\Controllers\WebsiteSetup\TestimonialController;
use App\Http\Controllers\WebsiteSetup\TrialSlotController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:website_setup']], function () {
            Route::group(['middleware' => ['auth.routes', 'DenyTeacherAndStudent']], function () {

                Route::controller(SectionsController::class)->prefix('page-sections')->group(function () {
                    Route::get('/',                         'index')->name('sections.index')->middleware('PermissionCheck:page_sections_read');
                    Route::get('/edit/{id}',                'edit')->name('sections.edit')->middleware('PermissionCheck:page_sections_update');
                    Route::put('/update/{id}',              'update')->name('sections.update')->middleware('PermissionCheck:page_sections_update', 'DemoCheck');
                    Route::get('/add-social-link',          'addSocialLink');
                    Route::get('/add-choose-us',            'addChooseUs');
                    Route::get('/add-academic-curriculum',  'addAcademicCurriculum');

                    Route::get('/translate/{id}',                'translate')->name('sections.translate')->middleware('PermissionCheck:page_sections_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('sections.translate.update')->middleware('PermissionCheck:page_sections_update');
                });


                Route::controller(AboutController::class)->prefix('abouts')->group(function () {
                    Route::get('/',                         'index')->name('about.index')->middleware('PermissionCheck:about_read');
                    Route::get('/create',                   'create')->name('about.create')->middleware('PermissionCheck:about_create');
                    Route::post('/store',                   'store')->name('about.store')->middleware('PermissionCheck:about_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('about.edit')->middleware('PermissionCheck:about_update');
                    Route::put('/update/{id}',              'update')->name('about.update')->middleware('PermissionCheck:about_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('about.delete')->middleware('PermissionCheck:about_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('about.bulk-delete')->middleware('PermissionCheck:about_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('about.bulk-status')->middleware('PermissionCheck:about_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('about.translate')->middleware('PermissionCheck:about_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('about.translate.update')->middleware('PermissionCheck:about_update');
                });


                Route::controller(SliderController::class)->prefix('slider')->group(function () {
                    Route::get('/',                         'index')->name('slider.index')->middleware('PermissionCheck:slider_read');
                    Route::get('/create',                   'create')->name('slider.create')->middleware('PermissionCheck:slider_create');
                    Route::post('/store',                   'store')->name('slider.store')->middleware('PermissionCheck:slider_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('slider.edit')->middleware('PermissionCheck:slider_update');
                    Route::put('/update/{id}',              'update')->name('slider.update')->middleware('PermissionCheck:slider_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('slider.delete')->middleware('PermissionCheck:slider_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('slider.bulk-delete')->middleware('PermissionCheck:slider_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('slider.bulk-status')->middleware('PermissionCheck:slider_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('slider.translate')->middleware('PermissionCheck:slider_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('slider.translate.update')->middleware('PermissionCheck:slider_update');
                });

                Route::controller(NewsController::class)->prefix('admin-news')->group(function () {
                    Route::get('/',                         'index')->name('news.index')->middleware('PermissionCheck:news_read');
                    Route::get('/create',                   'create')->name('news.create')->middleware('PermissionCheck:news_create');
                    Route::post('/store',                   'store')->name('news.store')->middleware('PermissionCheck:news_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('news.edit')->middleware('PermissionCheck:news_update');
                    Route::put('/update/{id}',              'update')->name('news.update')->middleware('PermissionCheck:news_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('news.delete')->middleware('PermissionCheck:news_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('news.bulk-delete')->middleware('PermissionCheck:news_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('news.bulk-status')->middleware('PermissionCheck:news_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('news.translate')->middleware('PermissionCheck:news_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('news.translate.update')->middleware('PermissionCheck:news_update');
                });

                Route::controller(EventController::class)->prefix('event')->group(function () {
                    Route::get('/',                         'index')->name('event.index')->middleware('PermissionCheck:event_read');
                    Route::get('/create',                   'create')->name('event.create')->middleware('PermissionCheck:event_create');
                    Route::post('/store',                   'store')->name('event.store')->middleware('PermissionCheck:event_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('event.edit')->middleware('PermissionCheck:event_update');
                    Route::put('/update/{id}',              'update')->name('event.update')->middleware('PermissionCheck:event_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('event.delete')->middleware('PermissionCheck:event_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('event.bulk-delete')->middleware('PermissionCheck:event_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('event.bulk-status')->middleware('PermissionCheck:event_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('event.translate')->middleware('PermissionCheck:event_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('event.translate.update')->middleware('PermissionCheck:event_update');

                });

                Route::controller(ProgramCategoryController::class)->prefix('program-category')->name('program-category.')->group(function () {
                    Route::get('/',                'index')->name('index')->middleware('PermissionCheck:program_category_read');
                    Route::get('/create',          'create')->name('create')->middleware('PermissionCheck:program_category_create');
                    Route::post('/store',          'store')->name('store')->middleware('PermissionCheck:program_category_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('edit')->middleware('PermissionCheck:program_category_update');
                    Route::put('/update/{id}',      'update')->name('update')->middleware('PermissionCheck:program_category_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('delete')->middleware('PermissionCheck:program_category_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('bulk-delete')->middleware('PermissionCheck:program_category_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('bulk-status')->middleware('PermissionCheck:program_category_update', 'DemoCheck');
                });

                Route::controller(ProgramFocusController::class)->prefix('program-focus')->name('program-focus.')->group(function () {
                    Route::get('/',                'index')->name('index')->middleware('PermissionCheck:program_focus_read');
                    Route::get('/create',          'create')->name('create')->middleware('PermissionCheck:program_focus_create');
                    Route::post('/store',          'store')->name('store')->middleware('PermissionCheck:program_focus_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('edit')->middleware('PermissionCheck:program_focus_update');
                    Route::put('/update/{id}',      'update')->name('update')->middleware('PermissionCheck:program_focus_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('delete')->middleware('PermissionCheck:program_focus_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('bulk-delete')->middleware('PermissionCheck:program_focus_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('bulk-status')->middleware('PermissionCheck:program_focus_update', 'DemoCheck');
                });

                Route::controller(ProgramController::class)->prefix('program')->name('program.')->group(function () {
                    Route::get('/',                'index')->name('index')->middleware('PermissionCheck:program_read');
                    Route::get('/create',          'create')->name('create')->middleware('PermissionCheck:program_create');
                    Route::post('/store',          'store')->name('store')->middleware('PermissionCheck:program_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('edit')->middleware('PermissionCheck:program_update');
                    Route::put('/update/{id}',      'update')->name('update')->middleware('PermissionCheck:program_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('delete')->middleware('PermissionCheck:program_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('bulk-delete')->middleware('PermissionCheck:program_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('bulk-status')->middleware('PermissionCheck:program_update', 'DemoCheck');
                });

                Route::controller(TestimonialController::class)->prefix('testimonial')->name('testimonial.')->group(function () {
                    Route::get('/',                'index')->name('index')->middleware('PermissionCheck:testimonial_read');
                    Route::get('/create',          'create')->name('create')->middleware('PermissionCheck:testimonial_create');
                    Route::post('/store',          'store')->name('store')->middleware('PermissionCheck:testimonial_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('edit')->middleware('PermissionCheck:testimonial_update');
                    Route::put('/update/{id}',      'update')->name('update')->middleware('PermissionCheck:testimonial_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('delete')->middleware('PermissionCheck:testimonial_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('bulk-delete')->middleware('PermissionCheck:testimonial_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('bulk-status')->middleware('PermissionCheck:testimonial_update', 'DemoCheck');
                });

                Route::controller(TrialSlotController::class)->prefix('trial-slot')->name('trial-slot.')->group(function () {
                    Route::get('/',                'index')->name('index')->middleware('PermissionCheck:trial_slot_read');
                    Route::get('/create',          'create')->name('create')->middleware('PermissionCheck:trial_slot_create');
                    Route::post('/store',          'store')->name('store')->middleware('PermissionCheck:trial_slot_create', 'DemoCheck');
                    Route::get('/edit/{id}',        'edit')->name('edit')->middleware('PermissionCheck:trial_slot_update');
                    Route::put('/update/{id}',      'update')->name('update')->middleware('PermissionCheck:trial_slot_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('delete')->middleware('PermissionCheck:trial_slot_delete', 'DemoCheck');
                    Route::post('/bulk-delete',     'bulkDelete')->name('bulk-delete')->middleware('PermissionCheck:trial_slot_delete', 'DemoCheck');
                    Route::post('/bulk-status',     'bulkStatus')->name('bulk-status')->middleware('PermissionCheck:trial_slot_update', 'DemoCheck');
                });

                Route::controller(CounterController::class)->prefix('counter')->group(function () {
                    Route::get('/',                         'index')->name('counter.index')->middleware('PermissionCheck:counter_read');
                    Route::get('/create',                   'create')->name('counter.create')->middleware('PermissionCheck:counter_create');
                    Route::post('/store',                   'store')->name('counter.store')->middleware('PermissionCheck:counter_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('counter.edit')->middleware('PermissionCheck:counter_update');
                    Route::put('/update/{id}',              'update')->name('counter.update')->middleware('PermissionCheck:counter_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('counter.delete')->middleware('PermissionCheck:counter_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('counter.bulk-delete')->middleware('PermissionCheck:counter_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('counter.bulk-status')->middleware('PermissionCheck:counter_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('counter.translate')->middleware('PermissionCheck:counter_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('counter.translate.update')->middleware('PermissionCheck:counter_update');


                });

                Route::controller(ContactInfoController::class)->prefix('contact-info')->group(function () {
                    Route::get('/',                         'index')->name('contact-info.index')->middleware('PermissionCheck:contact_info_read');
                    Route::get('/create',                   'create')->name('contact-info.create')->middleware('PermissionCheck:contact_info_create');
                    Route::post('/store',                   'store')->name('contact-info.store')->middleware('PermissionCheck:contact_info_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('contact-info.edit')->middleware('PermissionCheck:contact_info_update');
                    Route::put('/update/{id}',              'update')->name('contact-info.update')->middleware('PermissionCheck:contact_info_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('contact-info.delete')->middleware('PermissionCheck:contact_info_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('contact-info.bulk-delete')->middleware('PermissionCheck:contact_info_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('contact-info.bulk-status')->middleware('PermissionCheck:contact_info_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('contact-info.translate')->middleware('PermissionCheck:contact_info_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('contact-info.translate.update')->middleware('PermissionCheck:contact_info_update');

                });

                Route::controller(DepartmentContactController::class)->prefix('department-contact')->group(function () {
                    Route::get('/',                         'index')->name('department-contact.index')->middleware('PermissionCheck:dep_contact_read');
                    Route::get('/create',                   'create')->name('department-contact.create')->middleware('PermissionCheck:dep_contact_create');
                    Route::post('/store',                   'store')->name('department-contact.store')->middleware('PermissionCheck:dep_contact_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('department-contact.edit')->middleware('PermissionCheck:dep_contact_update');
                    Route::put('/update/{id}',              'update')->name('department-contact.update')->middleware('PermissionCheck:dep_contact_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('department-contact.delete')->middleware('PermissionCheck:dep_contact_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('department-contact.bulk-delete')->middleware('PermissionCheck:dep_contact_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('department-contact.bulk-status')->middleware('PermissionCheck:dep_contact_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('department-contact.translate')->middleware('PermissionCheck:dep_contact_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('department-contact.translate.update')->middleware('PermissionCheck:dep_contact_update');
                });

                Route::controller(SubscribeController::class)->prefix('subscribe')->group(function () {
                    Route::get('/',                         'index')->name('subscribe.index')->middleware('PermissionCheck:subscribe_read');
                });
                Route::controller(ContactMessageController::class)->prefix('contact-message')->group(function () {
                    Route::get('/',                         'index')->name('contact-message.index')->middleware('PermissionCheck:contact_message_read');
                });

            });
        });


        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:gallery']], function () {
            Route::group(['middleware' => ['auth.routes']], function () {

                Route::controller(GalleryCategoryController::class)->prefix('gallery-category')->group(function () {
                    Route::get('/',                         'index')->name('gallery-category.index')->middleware('PermissionCheck:gallery_category_read');
                    Route::get('/create',                   'create')->name('gallery-category.create')->middleware('PermissionCheck:gallery_category_create');
                    Route::post('/store',                   'store')->name('gallery-category.store')->middleware('PermissionCheck:gallery_category_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('gallery-category.edit')->middleware('PermissionCheck:gallery_category_update');
                    Route::get('/translate/{id}',                'translate')->name('gallery-category.translate')->middleware('PermissionCheck:gallery_category_update');
                    Route::post('/translateUpdate/{id}',                'translateUpdate')->name('gallery-category.translateUpdate')->middleware('PermissionCheck:gallery_category_update');
                    Route::put('/update/{id}',              'update')->name('gallery-category.update')->middleware('PermissionCheck:gallery_category_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('gallery-category.delete')->middleware('PermissionCheck:gallery_category_delete', 'DemoCheck');
                });

                Route::controller(GalleryController::class)->prefix('gallery')->group(function () {
                    Route::get('/',                         'index')->name('gallery.index')->middleware('PermissionCheck:gallery_read');
                    Route::get('/create',                   'create')->name('gallery.create')->middleware('PermissionCheck:gallery_create');
                    Route::post('/store',                   'store')->name('gallery.store')->middleware('PermissionCheck:gallery_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('gallery.edit')->middleware('PermissionCheck:gallery_update');
                    Route::put('/update/{id}',              'update')->name('gallery.update')->middleware('PermissionCheck:gallery_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('gallery.delete')->middleware('PermissionCheck:gallery_delete', 'DemoCheck');
                });

                Route::controller(PagesController::class)->prefix('page')->name('page.')->group(function () {
                    Route::get('/',                         'index')->name('index')->middleware('PermissionCheck:page_read');
                    Route::get('/create',                   'create')->name('create')->middleware('PermissionCheck:page_create');
                    Route::post('/store',                   'store')->name('store')->middleware('PermissionCheck:page_create', 'DemoCheck');
                    Route::get('/edit/{id}',                'edit')->name('edit')->middleware('PermissionCheck:page_update');
                    Route::post('/update/{id}',              'update')->name('update')->middleware('PermissionCheck:page_update', 'DemoCheck');
                    Route::delete('/delete/{id}',           'delete')->name('delete')->middleware('PermissionCheck:page_delete', 'DemoCheck');
                    Route::post('/bulk-delete',             'bulkDelete')->name('bulk-delete')->middleware('PermissionCheck:page_delete', 'DemoCheck');
                    Route::post('/bulk-status',             'bulkStatus')->name('bulk-status')->middleware('PermissionCheck:page_update', 'DemoCheck');

                    Route::get('/translate/{id}',                'translate')->name('translate')->middleware('PermissionCheck:page_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('translate.update')->middleware('PermissionCheck:page_update');

                });

            });
        });
    });
});




<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\EmployeeController;
use App\Http\Controllers\Portal\TaskController;
use App\Http\Controllers\Portal\SettingsController;
use App\Http\Controllers\Portal\AttendanceController;
use App\Http\Controllers\Portal\WorkLogController;
use App\Http\Controllers\Portal\ResponsibilityController;
use App\Http\Controllers\Portal\AnalyticsController;
use App\Http\Controllers\Portal\SocialController;
use App\Http\Controllers\Portal\GrowthPayController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::get('/portal', [PortalController::class, 'index'])->name('portal.index')->middleware('PermissionCheck:portal_access');

                Route::controller(EmployeeController::class)->prefix('portal/employees')->name('portal-employees.')->group(function () {
                    Route::get('/',          'index')->name('index')->middleware('PermissionCheck:portal_access');
                    Route::get('/show/{id}', 'show')->name('show')->middleware('PermissionCheck:portal_access');
                });

                Route::controller(TaskController::class)->prefix('portal/tasks')->name('portal-tasks.')->group(function () {
                    Route::get('/',                    'index')->name('index')->middleware('PermissionCheck:portal_manage');
                    Route::get('/create',               'create')->name('create')->middleware('PermissionCheck:portal_manage');
                    Route::post('/store',               'store')->name('store')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::get('/show/{id}',             'show')->name('show')->middleware('PermissionCheck:portal_access');
                    Route::post('/{id}/reassign',        'reassign')->name('reassign')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/accept',          'accept')->name('accept')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/submit',          'submit')->name('submit')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/start-review',    'startReview')->name('start-review')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/request-revision', 'requestRevision')->name('request-revision')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/approve',         'approve')->name('approve')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/comment',         'comment')->name('comment')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/claim',            'claim')->name('claim')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/mark-paid',        'markPaid')->name('mark-paid')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                });

                Route::get('/portal/my-tasks', [TaskController::class, 'myTasks'])->name('portal-my-tasks.index')->middleware('PermissionCheck:portal_access');
                Route::get('/portal/paid-tasks', [TaskController::class, 'paidTasks'])->name('portal-paid-tasks.index')->middleware('PermissionCheck:portal_access');

                Route::controller(SettingsController::class)->prefix('portal/settings')->name('portal-settings.')->group(function () {
                    Route::get('/',       'edit')->name('edit')->middleware('PermissionCheck:portal_manage');
                    Route::post('/',      'update')->name('update')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                });

                Route::get('/portal/attendance', [AttendanceController::class, 'index'])->name('portal-attendance.index')->middleware('PermissionCheck:portal_manage');

                Route::controller(WorkLogController::class)->prefix('portal/work-log')->name('portal-work-log.')->group(function () {
                    Route::get('/',             'myLog')->name('index')->middleware('PermissionCheck:portal_access');
                    Route::post('/entry',       'storeEntry')->name('entry.store')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::delete('/entry/{id}', 'deleteEntry')->name('entry.delete')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/submit',      'submitDay')->name('submit')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                });

                Route::controller(WorkLogController::class)->prefix('portal/work-logs')->name('portal-work-logs.')->group(function () {
                    Route::get('/',                'managerIndex')->name('index')->middleware('PermissionCheck:portal_manage');
                    Route::get('/show/{staffId}',  'managerShow')->name('show')->middleware('PermissionCheck:portal_manage');
                });

                Route::controller(ResponsibilityController::class)->prefix('portal/responsibilities')->name('portal-responsibilities.')->group(function () {
                    Route::get('/',           'index')->name('index')->middleware('PermissionCheck:portal_manage');
                    Route::post('/store',     'store')->name('store')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/update', 'update')->name('update')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::delete('/{id}',    'destroy')->name('destroy')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                });

                Route::get('/portal/my-responsibilities', [ResponsibilityController::class, 'myDuties'])->name('portal-my-responsibilities.index')->middleware('PermissionCheck:portal_access');
                Route::post('/portal/responsibilities/{id}/tick', [ResponsibilityController::class, 'tick'])->name('portal-responsibilities.tick')->middleware('PermissionCheck:portal_access', 'DemoCheck');

                Route::get('/portal/analytics', [AnalyticsController::class, 'index'])->name('portal-analytics.index')->middleware('PermissionCheck:portal_manage');
                Route::get('/portal/my-performance', [AnalyticsController::class, 'myPerformance'])->name('portal-my-performance.index')->middleware('PermissionCheck:portal_access');

                Route::controller(SocialController::class)->prefix('portal/social/reels')->name('portal-social-reels.')->group(function () {
                    Route::get('/',              'reels')->name('index')->middleware('PermissionCheck:portal_access');
                    Route::get('/create',        'createReel')->name('create')->middleware('PermissionCheck:portal_access');
                    Route::post('/store',        'storeReel')->name('store')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::get('/show/{id}',     'showReel')->name('show')->middleware('PermissionCheck:portal_access');
                    Route::get('/edit/{id}',     'editReel')->name('edit')->middleware('PermissionCheck:portal_access');
                    Route::put('/update/{id}',   'updateReel')->name('update')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::post('/{id}/review',  'reviewReel')->name('review')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                    Route::delete('/{id}',       'destroyReel')->name('destroy')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                });

                Route::get('/portal/social/month-plan', [SocialController::class, 'monthPlan'])->name('portal-social-month-plan.index')->middleware('PermissionCheck:portal_access');
                Route::post('/portal/social/month-plan/targets', [SocialController::class, 'updateCategoryTargets'])->name('portal-social-month-plan.targets')->middleware('PermissionCheck:portal_manage', 'DemoCheck');

                Route::get('/portal/social/audience', [SocialController::class, 'audience'])->name('portal-social-audience.index')->middleware('PermissionCheck:portal_access');
                Route::post('/portal/social/audience/store', [SocialController::class, 'storeMetric'])->name('portal-social-audience.store')->middleware('PermissionCheck:portal_access', 'DemoCheck');

                Route::get('/portal/social/page-fixes', [SocialController::class, 'pageFixes'])->name('portal-social-page-fixes.index')->middleware('PermissionCheck:portal_access');
                Route::post('/portal/social/page-fixes/store', [SocialController::class, 'storePageFix'])->name('portal-social-page-fixes.store')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                Route::post('/portal/social/page-fixes/{id}/status', [SocialController::class, 'updatePageFixStatus'])->name('portal-social-page-fixes.status')->middleware('PermissionCheck:portal_access', 'DemoCheck');

                Route::get('/portal/social/settings', [SocialController::class, 'settings'])->name('portal-social-settings.edit')->middleware('PermissionCheck:portal_manage');
                Route::post('/portal/social/settings', [SocialController::class, 'updateSettings'])->name('portal-social-settings.update')->middleware('PermissionCheck:portal_manage', 'DemoCheck');

                Route::get('/portal/social/daily-plan', [SocialController::class, 'dailyPlan'])->name('portal-social-daily-plan.index')->middleware('PermissionCheck:portal_access');
                Route::post('/portal/social/daily-plan', [SocialController::class, 'storeDailyPlan'])->name('portal-social-daily-plan.store')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                Route::post('/portal/social/weekly-goal', [SocialController::class, 'storeWeeklyGoal'])->name('portal-social-weekly-goal.store')->middleware('PermissionCheck:portal_access', 'DemoCheck');
                Route::post('/portal/social/weekly-goal/{id}/toggle', [SocialController::class, 'toggleGoal'])->name('portal-social-weekly-goal.toggle')->middleware('PermissionCheck:portal_access', 'DemoCheck');

                Route::controller(GrowthPayController::class)->prefix('portal/growth-pay')->name('portal-growth-pay.')->group(function () {
                    Route::get('/',             'index')->name('index')->middleware('PermissionCheck:portal_manage');
                    Route::post('/save',        'save')->name('save')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                    Route::post('/{id}/mark-paid', 'markPaid')->name('mark-paid')->middleware('PermissionCheck:portal_manage', 'DemoCheck');
                });

            });
        });
    });
});

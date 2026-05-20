<?php

namespace App\Providers;

use Illuminate\Http\Request;
use App\Models\WebsiteSetup\Page;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Models\WebsiteSetup\Subscribe;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Modules\MultiBranch\Entities\Branch;
use App\Models\WebsiteSetup\PageSections;
use Illuminate\Support\Facades\RateLimiter;
use Stancl\Tenancy\Events\TenancyBootstrapped;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        // $this->app['events']->listen(TenancyBootstrapped::class, function ($event) {
        // view()->composer('*', function ($view) {

        //     try {

        //         $subscriber = Subscribe::count();
        //         $sections   = PageSections::with('upload')->get();

        //         $sectionArr = [];
        //         foreach($sections as $section){
        //             $sectionArr[$section->key]   = $section;
        //         }

        //         $view->with([
        //             'sections'   => $sectionArr,
        //             'subscriber' => $subscriber,
        //         ]);
        //     } catch (\Exception $e) {
        //         $view->with([
        //             'sections'   => [],
        //             'subscriber' => 0,
        //         ]);
        //     }
        // });
        // });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerFrontendCoursesConfig();
        $this->registerFrontendHolidayCampsConfig();

        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(2)->by(optional($request->user())->id ?: $request->ip());
        });


        if (env('APP_SAAS')):

            $this->app['events']->listen(TenancyBootstrapped::class, function ($event) {
                view()->composer('*', function ($view) {
                    $view->with($this->pageSectionsAndSubscriber());
                });
            });
        else:
            view()->composer('*', function ($view) {
                $view->with($this->pageSectionsAndSubscriber());
            });

        endif;


        view()->composer(['backend.partials.header', 'parent-panel.partials.header'], function ($view) {

            try {
                $notifications = SystemNotification::myNotification();
                $view->with([
                    'notifications' => $notifications
                ]);
            } catch (\Exception $e) {
                $view->with([
                    'notifications' => []
                ]);
            }
        });

        view()->composer(['frontend.partials.footer-content'], function ($view) {
            try {
                $footer_pages = Page::where('menu_show', 'footer')->get(['id', 'name', 'slug']);

                $view->with([
                    'footer_pages' => $footer_pages
                ]);
            } catch (\Exception $e) {
                $view->with([
                    'footer_pages' => []
                ]);
            }
        });

        view()->composer(['frontend.partials.menu'], function ($view) {
            try {
                $footer_pages = Page::where('menu_show', 'header')->get(['id', 'name', 'slug']);
                $view->with([
                    'header_pages' => $footer_pages
                ]);
            } catch (\Exception $e) {
                $view->with([
                    'header_pages' => []
                ]);
            }
        });


        if (hasModule('MultiBranch') && Schema::hasTable('branches')) {
            view()->composer(['backend.partials.header'], function ($view) {
                $branches = Branch::pluck('name', 'id');
                $view->with(['all_branches' => $branches]);
            });
        }


        if (env('APP_HTTPS') == true) {
            URL::forceScheme('https');
        }
        Paginator::useBootstrap();
    }

    /**
     * Subscribe + page sections for global view composer. Cached once per HTTP request
     * so wildcard composers do not re-query on every nested @include.
     */
    /**
     * Always load the full public courses catalog from disk (avoids stale config:cache lists).
     */
    protected function registerFrontendCoursesConfig(): void
    {
        $path = base_path('config/frontend_courses.php');

        if (!is_readable($path)) {
            return;
        }

        $catalog = require $path;

        if (is_array($catalog)) {
            Config::set('frontend_courses', $catalog);
        }
    }

    protected function registerFrontendHolidayCampsConfig(): void
    {
        $path = base_path('config/frontend_holiday_camps.php');

        if (!is_readable($path)) {
            return;
        }

        $catalog = require $path;

        if (is_array($catalog)) {
            Config::set('frontend_holiday_camps', $catalog);
        }
    }

    protected function pageSectionsAndSubscriber(): array
    {
        static $payload = null;
        static $requestId = null;

        $currentId = request() ? spl_object_id(request()) : null;
        if ($requestId !== $currentId) {
            $requestId = $currentId;
            $payload = null;
        }

        if ($payload !== null) {
            return $payload;
        }

        try {
            $subscriber = Subscribe::count();
            $sections = PageSections::with('upload')->get();

            $sectionArr = [];
            foreach ($sections as $section) {
                $sectionArr[$section->key] = $section;
            }

            return $payload = [
                'sections' => $sectionArr,
                'subscriber' => $subscriber,
            ];
        } catch (\Throwable $e) {
            return $payload = [
                'sections' => [],
                'subscriber' => 0,
            ];
        }
    }

}

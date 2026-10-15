<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeatureCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $feature)
    {
        if (!env('APP_SAAS')) {
            return $next($request);
        }

        // if (Auth::check() && in_array($feature, Setting('features'))) :
        if (Auth::check() && in_array($feature, activeSubscriptionFeatures() ?? [])) :
            return $next($request);
        endif;
        return abort(403, 'Access Denied');
    }
}

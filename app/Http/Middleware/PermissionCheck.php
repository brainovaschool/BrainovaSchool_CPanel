<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        // A single permission still works exactly as before (splitting a
        // string with no comma just yields one element). Comma-separated
        // is new, opt-in OR logic — e.g. 'PermissionCheck:portal_manage,
        // portal_analytics_read' passes with either one, for a view-only
        // route that a broader "manage" permission should also unlock.
        foreach (explode(',', $permission) as $single) {
            if (authHasPermission(trim($single))) {
                return $next($request);
            }
        }

        return abort(403, 'Access Denied');
    }

}

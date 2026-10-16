<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * Public (no login required): powers the mobile app's "Select a School"
 * screen. The vendor's own template pointed at an endpoint
 * (saas/get-all-school) that was never actually implemented anywhere in
 * this codebase -- not even in the disabled MainApp module, whose own
 * SaaS school-list route is a differently-shaped get-school/{city_id} --
 * so this is a clean implementation against the real, working Tenant
 * system instead of that disconnected country/city/School flow.
 */
class SchoolDirectoryController extends Controller
{
    use ReturnFormatTrait;

    /** Every registered school, for the picker. Each school's own display
     *  name comes from ITS OWN General Settings (application_name), not
     *  the central tenants table, so briefly switches into each tenant to
     *  read it -- there are only ever a handful of schools, so this is
     *  cheap; nothing here touches or exposes access codes. */
    public function index()
    {
        try {
            $schools = Tenant::with('domains')->get()->map(function (Tenant $tenant) {
                $domain = $tenant->domains->first()?->domain;
                if (!$domain) {
                    return null;
                }

                $name = $tenant->run(fn () => setting('application_name')) ?: $tenant->id;
                $logo = $tenant->run(fn () => globalAsset(setting('app_logo'), 'favicon.png'));

                return [
                    'id'      => $tenant->id,
                    'name'    => $name,
                    'logo'    => $logo,
                    'api_url' => "https://{$domain}/api",
                ];
            })->filter()->values();

            return $this->responseWithSuccess(___('alert.success'), $schools);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    /** Gates actually proceeding into a school's login behind a code only
     *  that school's staff were given -- the list above is visible to
     *  anyone, but selecting a school in the app still requires this. */
    public function verifyCode(Request $request)
    {
        try {
            $schoolId = $request->input('school_id');
            $code     = $request->input('code');

            $tenant = Tenant::find($schoolId);
            if (!$tenant || empty($tenant->access_code)) {
                return $this->responseWithError(___('alert.invalid_code') ?: 'Invalid code.');
            }

            if (!hash_equals((string) $tenant->access_code, (string) $code)) {
                return $this->responseWithError(___('alert.invalid_code') ?: 'Invalid code.');
            }

            return $this->responseWithSuccess(___('alert.success'));
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}

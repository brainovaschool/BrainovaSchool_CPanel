<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ReturnFormatTrait;

/**
 * Public (no login required): the mobile app's splash/login screens show
 * this before anyone signs in, so it can't sit behind auth:sanctum like
 * the rest of this file. Reads the dedicated "app_logo" General Setting
 * (General Settings -> Mobile App Logo, 250x250) -- deliberately separate
 * from light_logo/dark_logo, which are the website's own header logo and a
 * different shape -- so updating it updates the app too, with no new build.
 */
class AppBrandingController extends Controller
{
    use ReturnFormatTrait;

    public function index()
    {
        try {
            $branding = [
                'school_name' => setting('application_name'),
                'logo'        => globalAsset(setting('app_logo'), 'favicon.png'),
            ];

            return $this->responseWithSuccess(___('alert.success'), $branding);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/**
 * Runs pending tenant migrations from the browser, for hosts without shell /
 * artisan access. Guarded by a fixed key + main-administrator login.
 */
class MigrationRunnerController extends Controller
{
    private const KEY = 'brainova-db-2026';

    public function run(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        Artisan::call('migrate', [
            '--path'  => 'database/migrations/tenant',
            '--force' => true,
        ]);

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . e(trim(Artisan::output()) ?: 'Nothing to migrate.')
            . "</pre>"
        );
    }
}

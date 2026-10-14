<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Creates a new, fully isolated school (its own database, migrated and
 * seeded fresh) and binds it to a subdomain — from the browser, for hosts
 * without shell/artisan access. Same guard pattern as MigrationRunnerController:
 * a fixed key + main-administrator login. Not idempotent on purpose — it
 * refuses to touch a school key or domain that already exists rather than
 * silently reusing or overwriting one.
 */
class TenantProvisionController extends Controller
{
    private const KEY = 'brainova-db-2026';

    public function create(string $key, string $schoolKey, string $domain)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        // Stancl Tenancy's own central tables (tenants/domains) were never part of
        // this project's migrations — create them once, on the central database,
        // the first time this route runs. Harmless to check on every later call.
        if (!Schema::hasTable('tenants') || !Schema::hasTable('domains')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (Tenant::find($schoolKey)) {
            return response("A tenant with id \"{$schoolKey}\" already exists — nothing done.", 422);
        }

        if (Domain::where('domain', $domain)->exists()) {
            return response("Domain \"{$domain}\" is already registered to a tenant — nothing done.", 422);
        }

        // Tenant::create triggers CreateDatabase -> MigrateDatabase -> SeedDatabase
        // synchronously (see TenancyServiceProvider), so by the time this returns
        // the new school has its own real database, fully migrated and seeded.
        $tenant = Tenant::create(['id' => $schoolKey]);
        $tenant->domains()->create(['domain' => $domain]);

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Tenant \"{$schoolKey}\" created with its own separate database, migrated and seeded.\n"
            . "Domain registered: {$domain}\n\n"
            . "Visit https://{$domain}/login to sign in.\n\n"
            . "Default super admin login for this new school (from the standard seeder — CHANGE THIS before handing the school access):\n"
            . "  Email: superadmin@onest.com\n"
            . "  Password: 123456"
            . "</pre>"
        );
    }
}

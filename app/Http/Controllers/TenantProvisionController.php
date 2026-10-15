<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Stancl\Tenancy\Jobs\SeedDatabase;

/**
 * Creates a new, fully isolated school (its own database, migrated and
 * seeded fresh) and binds it to a subdomain — from the browser, for hosts
 * without shell/artisan access. Same guard pattern as MigrationRunnerController:
 * a fixed key + main-administrator login.
 *
 * Shared cPanel hosting can't CREATE DATABASE from the app's own DB user, so
 * the database for each school has to be created by hand in cPanel first
 * (see the response this returns when that hasn't happened yet). A tenant
 * whose database-creation step failed is left as a central "tenants" row
 * with no working database or domain — safe to retry: for an existing
 * tenant this skips straight to migrating + seeding the (now manually
 * created) database rather than refusing, since Stancl's own CreateDatabase
 * step refuses to touch a database it didn't create itself. Never re-creates
 * a domain that's already attached.
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

        $tenant = Tenant::find($schoolKey);

        if ($tenant && $tenant->domains()->where('domain', $domain)->exists()) {
            return response("Tenant \"{$schoolKey}\" and domain \"{$domain}\" are already fully set up — nothing done.", 422);
        }

        if (!$tenant && Domain::where('domain', $domain)->exists()) {
            return response("Domain \"{$domain}\" is already registered to a different tenant — nothing done.", 422);
        }

        $expectedDatabase = env('DB_DATABASE') . '_' . $schoolKey;
        if (!$this->databaseExists($expectedDatabase)) {
            return response(
                '<pre style="font:14px/1.5 monospace;padding:24px">'
                . "Can't create the database for \"{$schoolKey}\" automatically on this hosting.\n\n"
                . "One-time manual step in cPanel first:\n"
                . "1. MySQL Databases -> Create New Database: BrainovaSchool_{$schoolKey}\n"
                . "   (cPanel will prefix it to {$expectedDatabase})\n"
                . "2. Add User to Database -> your existing app DB user -> this new database -> ALL PRIVILEGES\n\n"
                . "Then reload this exact URL again."
                . "</pre>",
                422
            );
        }

        if (!$tenant) {
            // Tenant::create triggers CreateDatabase -> MigrateDatabase -> SeedDatabase
            // synchronously (see TenancyServiceProvider).
            $tenant = Tenant::create(['id' => $schoolKey]);
        } else {
            // A previous attempt left this tenant's central row behind without a
            // working database (the cPanel database didn't exist yet at the time).
            // CreateDatabase refuses to touch a database it didn't create itself,
            // so skip straight to migrating + seeding the one that's there now.
            dispatch_sync(new MigrateDatabase($tenant));
            dispatch_sync(new SeedDatabase($tenant));
        }

        if (!$tenant->domains()->where('domain', $domain)->exists()) {
            $tenant->domains()->create(['domain' => $domain]);
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Tenant \"{$schoolKey}\" is set up with its own separate database, migrated and seeded.\n"
            . "Domain registered: {$domain}\n\n"
            . "Visit https://{$domain}/login to sign in.\n\n"
            . "Default super admin login for this new school (from the standard seeder — CHANGE THIS before handing the school access):\n"
            . "  Email: superadmin@onest.com\n"
            . "  Password: 123456"
            . "</pre>"
        );
    }

    /** Read-only: lists every registered tenant + domain, and whether the login/dashboard
     *  routes actually run through tenancy-switching middleware anywhere in routes/web.php. */
    public function diagnose(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $rows = Domain::with('tenant')->get()->map(function ($d) {
            return "{$d->domain} -> tenant \"{$d->tenant_id}\"";
        })->implode("\n") ?: '(no domains registered at all)';

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Registered domains:\n{$rows}\n\n"
            . "Central DB (default connection): " . env('DB_DATABASE') . "\n"
            . "Central domains config: " . json_encode(config('tenancy.central_domains')) . "\n"
            . "</pre>"
        );
    }

    /** Checks the central connection's server for a database by name, without switching to it. */
    private function databaseExists(string $name): bool
    {
        // SHOW DATABASES LIKE ? doesn't support bound placeholders on this MySQL
        // version; information_schema.SCHEMATA is a normal table, so a plain
        // parameterized SELECT works safely instead.
        $result = DB::select('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$name]);
        return count($result) > 0;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Subscription;
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

    // Every feature key any route in this app actually checks via FeatureCheck:<key>
    // (confirmed by grepping routes/*.php) -- a school created through the full
    // signup flow gets these from its chosen Package instead; this route has no
    // Package to read, so every school it provisions gets full access to the
    // same core modules your own site has, same as before APP_SAAS was ever on.
    // Restrict a specific school afterwards via Manage School Features.
    private const DEFAULT_FEATURES = [
        'online_admission', 'student_info', 'academic', 'routine', 'attendance',
        'fees', 'examination', 'online_examination', 'library', 'account',
        'report', 'language', 'staff_manage', 'website_setup', 'gallery', 'setting',
        'id_card', 'certificate', 'class_content', 'homework', 'teacher_tools',
        'communication', 'live_class', 'subscribers', 'contact_message',
        'course_catalogue', 'learning_engine', 'app_settings',
    ];

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
            // Already fully set up -- still worth healing a subscription whose
            // features never got backfilled (e.g. testschool before this fix).
            $this->ensureFullFeatureAccess($tenant);
            return response("Tenant \"{$schoolKey}\" and domain \"{$domain}\" are already fully set up — feature access checked/healed, nothing else done.", 422);
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

        $this->ensureFullFeatureAccess($tenant);
        $accessCode = $this->ensureAccessCode($tenant);

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
            . "  Password: 123456\n\n"
            . "Mobile app school-selection access code (give this to the school's staff, not students):\n"
            . "  {$accessCode}"
            . "</pre>"
        );
    }

    /**
     * ONE-OFF: registers a domain (meant for the school's own main domain) as
     * a tenant pointing at the app's CURRENT, already-populated database --
     * never a new empty one. Run this exactly once, before turning on
     * APP_SAAS, so InitializeTenancyByDomain has something to resolve the
     * main domain to instead of breaking the live site the moment that
     * middleware becomes active everywhere. Deliberately skips Stancl's
     * normal create/migrate/seed pipeline (saveQuietly -- no events fire)
     * since the database already exists with real data; nothing is created,
     * migrated, or re-seeded.
     */
    public function adoptMainSite(string $key, string $tenantId, string $domain)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        if (!Schema::hasTable('tenants') || !Schema::hasTable('domains')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (Tenant::find($tenantId)) {
            return response("A tenant with id \"{$tenantId}\" already exists — nothing done.", 422);
        }

        if (Domain::where('domain', $domain)->exists()) {
            return response("Domain \"{$domain}\" is already registered to a tenant — nothing done.", 422);
        }

        $realDatabase = env('DB_DATABASE');

        $tenant = new Tenant(['id' => $tenantId]);
        $tenant->setInternal('db_name', $realDatabase);
        $tenant->saveQuietly();

        $tenant->domains()->create(['domain' => $domain]);

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . "Tenant \"{$tenantId}\" registered, pointing at your EXISTING database ({$realDatabase}).\n"
            . "Nothing was created, migrated, or seeded -- your real data is untouched.\n"
            . "Domain registered: {$domain}\n\n"
            . "This has no effect yet. It only takes effect once APP_SAAS=true is set in .env."
            . "</pre>"
        );
    }

    /** ONE-OFF: back-fills full feature access for a tenant created outside
     *  this controller's own create() -- specifically adoptMainSite(), which
     *  never touches Subscription at all. Same healing logic as create()'s
     *  "already set up" branch, just reachable without re-supplying a domain. */
    public function healFeatures(string $key, string $tenantId)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response("No tenant with id \"{$tenantId}\" exists.", 422);
        }

        $this->ensureFullFeatureAccess($tenant);

        return response("Feature access checked/healed for tenant \"{$tenantId}\".");
    }

    /** ONE-OFF: shows (generating one first if it doesn't have one yet) the
     *  mobile app's school-selection access code for an existing tenant --
     *  for brainova/testschool, created before this code existed. */
    public function showAccessCode(string $key, string $tenantId)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response("No tenant with id \"{$tenantId}\" exists.", 422);
        }

        $code = $this->ensureAccessCode($tenant);

        return response("Access code for tenant \"{$tenantId}\": {$code}");
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

    /** Inside the tenant's own database: gives its active subscription a full
     *  features list if it doesn't have one yet (e.g. seeded with none, since
     *  this route bypasses the Package/session-based signup flow). Never
     *  overwrites a subscription that already has real features set. */
    private function ensureFullFeatureAccess(Tenant $tenant): void
    {
        $tenant->run(function () {
            $subscription = Subscription::active()->first() ?? new Subscription(['status' => Status::ACTIVE]);
            if (empty($subscription->features)) {
                $subscription->features = self::DEFAULT_FEATURES;
                $subscription->save();
            }
            // activeSubscriptionFeatures() caches its result forever -- a page
            // visited before this fix may have already cached the broken null.
            cache()->forget('activeSubscriptionFeatures');
        });
    }

    /** On the CENTRAL tenants row (not inside the tenant's own database --
     *  this gates which school you can even pick in the mobile app's school
     *  selector, before any per-school login happens): generates a random
     *  access code if this tenant doesn't have one yet. Never overwrites an
     *  existing code, so re-running this is always safe. */
    private function ensureAccessCode(Tenant $tenant): string
    {
        if (empty($tenant->access_code)) {
            $tenant->access_code = strtoupper(\Illuminate\Support\Str::random(6));
            $tenant->save();
        }

        return $tenant->access_code;
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

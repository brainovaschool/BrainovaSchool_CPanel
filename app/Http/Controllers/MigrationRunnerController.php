<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/**
 * Runs pending tenant migrations from the browser, for hosts without shell /
 * artisan access, and syncs the permissions that ship alongside them.
 * Guarded by a fixed key + main-administrator login. Idempotent.
 */
class MigrationRunnerController extends Controller
{
    private const KEY = 'brainova-db-2026';

    /** Permission groups that must exist for the newer Website Setup modules. */
    private const PERMISSION_GROUPS = [
        'program_category' => ['read' => 'program_category_read', 'create' => 'program_category_create', 'update' => 'program_category_update', 'delete' => 'program_category_delete'],
        'program_focus'    => ['read' => 'program_focus_read', 'create' => 'program_focus_create', 'update' => 'program_focus_update', 'delete' => 'program_focus_delete'],
        'program'          => ['read' => 'program_read', 'create' => 'program_create', 'update' => 'program_update', 'delete' => 'program_delete'],
        'testimonial'      => ['read' => 'testimonial_read', 'create' => 'testimonial_create', 'update' => 'testimonial_update', 'delete' => 'testimonial_delete'],
        'trial_slot'       => ['read' => 'trial_slot_read', 'create' => 'trial_slot_create', 'update' => 'trial_slot_update', 'delete' => 'trial_slot_delete'],
    ];

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
        $migrate = trim(Artisan::output()) ?: 'Nothing to migrate.';

        $perms = $this->syncPermissions();

        // Program catalogue seeder is idempotent (updateOrCreate on slug) — safe to re-run.
        $seed = 'skipped';
        try {
            (new \Database\Seeders\WebsiteSetup\ProgramCatalogSeeder())->run();
            $seed = 'ok — categories/focuses/programs synced';
        } catch (\Throwable $e) {
            $seed = 'error: ' . $e->getMessage();
        }

        // Starter testimonials + reviews (idempotent — matched on name + type).
        $tm = 'skipped';
        try {
            (new \Database\Seeders\WebsiteSetup\TestimonialSeeder())->run();
            $tm = 'ok — testimonials/reviews synced';
        } catch (\Throwable $e) {
            $tm = 'error: ' . $e->getMessage();
        }

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . e($migrate) . "\n\nPermissions: " . e($perms)
            . "\nCatalogue seed: " . e($seed)
            . "\nTestimonials seed: " . e($tm)
            . "</pre>"
        );
    }

    private function syncPermissions(): string
    {
        $added      = [];
        $allKeywords = [];

        foreach (self::PERMISSION_GROUPS as $attribute => $keywords) {
            if (!Permission::where('attribute', $attribute)->exists()) {
                Permission::create(['attribute' => $attribute, 'keywords' => $keywords]);
                $added[] = $attribute;
            }
            $allKeywords = array_merge($allKeywords, array_values($keywords));
        }

        foreach (Role::all() as $role) {
            $rolePerms = is_array($role->permissions) ? $role->permissions : [];
            if ((int) $role->id === 1 || in_array('news_read', $rolePerms, true)) {
                $role->permissions = array_values(array_unique(array_merge($rolePerms, $allKeywords)));
                $role->save();
            }
        }

        return $added ? ('added ' . implode(', ', $added)) : 'all present';
    }
}

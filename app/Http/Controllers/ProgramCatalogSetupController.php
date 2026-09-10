<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\WebsiteSetup\ProgramCatalogSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * One-time installer for the Program Catalogue module.
 * Runs the three migrations, registers permissions, and imports the
 * existing courses. Guarded by a fixed key + main-administrator login.
 * This controller + its route are removed once setup is confirmed.
 */
class ProgramCatalogSetupController extends Controller
{
    private const KEY = 'brainova-programs-2026';

    public function run(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $log = [];

        Artisan::call('migrate', [
            '--path'  => 'database/migrations/tenant',
            '--force' => true,
        ]);
        $log['1_migrate'] = trim(Artisan::output()) ?: 'Nothing to migrate.';

        $log['2_permissions'] = $this->ensurePermissions();

        (new ProgramCatalogSeeder())->run();
        $log['3_import'] = 'Categories, focus areas and courses imported.';

        $log['4_counts'] = [
            'program_categories' => DB::table('program_categories')->count(),
            'program_focuses'    => DB::table('program_focuses')->count(),
            'programs'            => DB::table('programs')->count(),
        ];

        return response(
            '<pre style="font:14px/1.5 monospace;padding:24px">'
            . e(print_r($log, true))
            . "\nDone. You can now remove this route.</pre>"
        );
    }

    private function ensurePermissions(): string
    {
        $groups = [
            'program_category' => [
                'read'   => 'program_category_read',
                'create' => 'program_category_create',
                'update' => 'program_category_update',
                'delete' => 'program_category_delete',
            ],
            'program_focus' => [
                'read'   => 'program_focus_read',
                'create' => 'program_focus_create',
                'update' => 'program_focus_update',
                'delete' => 'program_focus_delete',
            ],
            'program' => [
                'read'   => 'program_read',
                'create' => 'program_create',
                'update' => 'program_update',
                'delete' => 'program_delete',
            ],
        ];

        $added = [];
        foreach ($groups as $attribute => $keywords) {
            if (!Permission::where('attribute', $attribute)->exists()) {
                Permission::create(['attribute' => $attribute, 'keywords' => $keywords]);
                $added[] = $attribute;
            }
        }

        $allKeywords = [];
        foreach ($groups as $keywords) {
            $allKeywords = array_merge($allKeywords, array_values($keywords));
        }

        // Grant to the main admin and to any role that already manages CMS News.
        foreach (Role::all() as $role) {
            $perms = is_array($role->permissions) ? $role->permissions : [];
            if ((int) $role->id === 1 || in_array('news_read', $perms, true)) {
                $role->permissions = array_values(array_unique(array_merge($perms, $allKeywords)));
                $role->save();
            }
        }

        return $added ? ('added: ' . implode(', ', $added)) : 'already present';
    }
}

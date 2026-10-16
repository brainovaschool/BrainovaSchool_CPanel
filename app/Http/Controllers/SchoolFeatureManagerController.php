<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the platform owner control which core modules each school can see
 * and use -- the lever that makes "sell a limited package to Al-Nisa, keep
 * full access on Brainova's own site" possible. A real page in the admin
 * panel, reached from the sidebar (Subscriptions -> Manage School Features)
 * rather than a one-off URL, but only shown to/usable by the platform
 * owner: every tenant has its own role_id=1 "Super Admin" (every school's
 * own admin), so role alone isn't enough -- this only works when the
 * CURRENT tenant is Brainova's own ("brainova"), never from inside another
 * school's login.
 */
class SchoolFeatureManagerController extends Controller
{
    // name => human label, shown as a checkbox each. Matches exactly the
    // feature keys every FeatureCheck:<key> / hasFeature('<key>') call in
    // the app actually checks.
    private const FEATURES = [
        'online_admission'   => 'Online Admission',
        'student_info'       => 'Student Info',
        'academic'           => 'Academic (classes, subjects, sessions)',
        'routine'            => 'Class & Exam Routines',
        'attendance'         => 'Attendance & Leave',
        'fees'               => 'Fees',
        'examination'        => 'Examination',
        'online_examination' => 'Online Examination',
        'library'            => 'Library',
        'account'            => 'Accounts (income/expense)',
        'report'             => 'Reports',
        'language'           => 'Language Management',
        'staff_manage'       => 'Staff / Users / Roles',
        'website_setup'      => 'Website Setup',
        'gallery'            => 'Gallery',
        'setting'            => 'General Settings',
    ];

    private function authorizeOwner(): void
    {
        $currentTenant = tenant() ? tenant()->id : null;
        if (!Auth::check() || (int) Auth::user()->role_id !== 1 || $currentTenant !== 'brainova') {
            abort(403, 'Only available to Brainova\'s own Super Admin.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeOwner();

        $tenants = Tenant::with('domains')->get()->map(function (Tenant $tenant) {
            $name = $tenant->run(fn () => setting('application_name')) ?: $tenant->id;
            return [
                'id'     => $tenant->id,
                'name'   => $name,
                'domain' => $tenant->domains->first()?->domain,
            ];
        });

        $selectedId = $request->query('school', $tenants->first()['id'] ?? null);
        $selectedTenant = $selectedId ? Tenant::find($selectedId) : null;
        $selectedFeatures = $selectedTenant
            ? $selectedTenant->run(fn () => Subscription::active()->first()?->features ?? [])
            : [];

        return view('backend.settings.school-features', [
            'title'            => 'Manage School Features',
            'tenants'          => $tenants,
            'selectedId'       => $selectedId,
            'allFeatures'      => self::FEATURES,
            'selectedFeatures' => $selectedFeatures,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeOwner();

        $tenant = Tenant::find($request->input('tenant_id'));
        if (!$tenant) {
            abort(404);
        }

        $features = array_values(array_intersect($request->input('features', []), array_keys(self::FEATURES)));

        $tenant->run(function () use ($features) {
            $subscription = Subscription::active()->first() ?? new Subscription(['status' => Status::ACTIVE]);
            $subscription->features = $features;
            $subscription->save();
            cache()->forget('activeSubscriptionFeatures');
        });

        return redirect()
            ->route('school-features.index', ['school' => $tenant->id])
            ->with('success', "Saved — \"{$tenant->id}\" now has " . count($features) . ' module(s) enabled.');
    }
}

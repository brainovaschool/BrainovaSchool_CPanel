<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the platform owner (you) control which core modules each school can
 * see and use -- the thing that makes "sell a limited package to Al-Nisa,
 * keep full access on your own site" actually possible. Same admin-only +
 * fixed-key guard as the rest of the /db/ tools.
 */
class SchoolFeatureManagerController extends Controller
{
    private const KEY = 'brainova-db-2026';

    // name => human label, shown as a checkbox each. Matches exactly the
    // feature keys every FeatureCheck:<key> / hasFeature('<key>') call in
    // the app actually checks (see TenantProvisionController).
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

    public function index(string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

        $tenants = Tenant::with('domains')->get()->map(function (Tenant $tenant) {
            $name = $tenant->run(fn () => setting('application_name')) ?: $tenant->id;
            $currentFeatures = $tenant->run(fn () => Subscription::active()->first()?->features ?? []);

            return [
                'id'       => $tenant->id,
                'name'     => $name,
                'domain'   => $tenant->domains->first()?->domain,
                'features' => $currentFeatures,
            ];
        });

        $rows = '';
        foreach ($tenants as $t) {
            $checkboxes = '';
            foreach (self::FEATURES as $featureKey => $label) {
                $checked = in_array($featureKey, $t['features']) ? 'checked' : '';
                $checkboxes .= "<label style='display:block;padding:4px 0'>"
                    . "<input type='checkbox' name='features[]' value='{$featureKey}' {$checked}> {$label}"
                    . "</label>";
            }

            $rows .= "<form method='POST' action='/db/manage-school-features/" . self::KEY . "/update' "
                . "style='border:1px solid #ddd;border-radius:8px;padding:16px;margin-bottom:20px;max-width:480px'>"
                . "<input type='hidden' name='_token' value='" . csrf_token() . "'>"
                . "<input type='hidden' name='tenant_id' value='{$t['id']}'>"
                . "<h3 style='margin:0 0 4px'>{$t['name']}</h3>"
                . "<p style='margin:0 0 12px;color:#666;font-size:13px'>{$t['id']} &middot; {$t['domain']}</p>"
                . $checkboxes
                . "<button type='submit' style='margin-top:12px;padding:8px 20px;background:#1a56db;color:#fff;border:none;border-radius:6px;cursor:pointer'>Save</button>"
                . "</form>";
        }

        $flash = session('feature_update_message');
        $flashHtml = $flash ? "<p style='padding:10px 16px;background:#e6f4ea;border-radius:6px;max-width:480px'>{$flash}</p>" : '';

        return response(
            "<div style='font-family:system-ui,sans-serif;padding:24px'>"
            . "<h2>Manage School Feature Access</h2>"
            . "<p style='color:#666;max-width:480px'>Uncheck a module to hide it from that school's whole staff -- sidebar links and the pages themselves both respect this. Your own school is listed the same way; nothing stops you from limiting it too, so be careful which boxes you uncheck there.</p>"
            . $flashHtml
            . $rows
            . "</div>"
        );
    }

    public function update(Request $request, string $key)
    {
        if (!hash_equals(self::KEY, $key)) {
            abort(404);
        }

        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, 'Log in as the main administrator first, then reload this page.');
        }

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
            ->route('school-features.index', ['key' => self::KEY])
            ->with('feature_update_message', "Saved -- \"{$tenant->id}\" now has " . count($features) . ' module(s) enabled.');
    }
}

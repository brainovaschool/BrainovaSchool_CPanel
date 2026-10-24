<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\RoleEnum;
use App\Models\DecisionRule;
use App\Traits\ReturnFormatTrait;

class DecisionRuleRepository
{
    use ReturnFormatTrait;

    /** The categories this page can configure, and which columns each one
     *  controls. Only 'leave' has real fields wired up today; adding a
     *  future category means adding a key here plus its show_* column(s)
     *  on the decision_rules table. */
    public const CATEGORIES = [
        'leave' => 'Leave',
    ];

    public function all()
    {
        return DecisionRule::with(['subjectRole', 'viewerRole'])->latest()->get();
    }

    public function store($request)
    {
        try {
            DecisionRule::create([
                'category'               => $request->category,
                'subject_role_id'        => $request->subject_role_id ?: null,
                'viewer_role_id'         => $request->viewer_role_id ?: null,
                'show_replacement_day'   => $request->boolean('show_replacement_day'),
                'show_half_day_full_day' => $request->boolean('show_half_day_full_day'),
            ]);

            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function delete($id)
    {
        try {
            DecisionRule::find($id)?->delete();

            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Whether $field (e.g. 'show_replacement_day') should be visible to
     *  the current viewer for a record whose subject is $subjectRoleId.
     *  Super Admin always sees everything; everyone else needs a matching
     *  rule (subject/viewer role null on a rule means "any role"). */
    public static function allows(string $category, ?int $subjectRoleId, string $field): bool
    {
        if ((int) (auth()->user()->role_id ?? 0) === RoleEnum::SUPERADMIN) {
            return true;
        }

        $viewerRoleId = auth()->user()->role_id ?? null;

        return DecisionRule::where('category', $category)
            ->where($field, true)
            ->where(function ($q) use ($subjectRoleId) {
                $q->whereNull('subject_role_id')->orWhere('subject_role_id', $subjectRoleId);
            })
            ->where(function ($q) use ($viewerRoleId) {
                $q->whereNull('viewer_role_id')->orWhere('viewer_role_id', $viewerRoleId);
            })
            ->exists();
    }
}

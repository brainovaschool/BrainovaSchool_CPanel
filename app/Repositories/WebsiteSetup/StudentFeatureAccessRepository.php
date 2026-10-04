<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\LearningEngine\FeatureAccess;
use App\Models\LearningEngine\FeatureAccessStudent;
use App\Models\StudentInfo\Student;
use App\Traits\ReturnFormatTrait;

class StudentFeatureAccessRepository
{
    use ReturnFormatTrait;

    public function students()
    {
        return Student::active()
            ->with(['session_class_student.class', 'session_class_student.section'])
            ->orderBy('first_name')
            ->get();
    }

    /** One row per feature in FeatureAccess::FEATURES, each with its
     *  current visible_to_all flag and tester id list — creates a
     *  default (hidden, no testers) row on the fly for a feature that's
     *  never been saved, so the screen always has something to show. */
    public function getAll(): array
    {
        $rows = [];
        foreach (FeatureAccess::FEATURES as $key => $label) {
            $rows[$key] = [
                'label'          => $label,
                'visible_to_all' => FeatureAccess::visibleToAll($key),
                'tester_ids'     => FeatureAccess::testerIds($key),
            ];
        }
        return $rows;
    }

    public function update($request, string $featureKey): array
    {
        if (!array_key_exists($featureKey, FeatureAccess::FEATURES)) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        try {
            $row = FeatureAccess::firstOrNew(['feature_key' => $featureKey]);
            $row->visible_to_all = (bool) $request->input('visible_to_all');
            $row->save();

            $testerIds = array_map('intval', (array) $request->input('tester_ids', []));

            FeatureAccessStudent::where('feature_key', $featureKey)
                ->whereNotIn('student_id', $testerIds)
                ->delete();

            foreach ($testerIds as $studentId) {
                FeatureAccessStudent::firstOrCreate([
                    'feature_key' => $featureKey,
                    'student_id'  => $studentId,
                ]);
            }

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}

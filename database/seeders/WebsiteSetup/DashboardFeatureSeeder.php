<?php

namespace Database\Seeders\WebsiteSetup;

use App\Models\WebsiteSetup\DashboardFeature;
use Illuminate\Database\Seeder;

/**
 * The catalogue of dashboard sections an admin can show/hide per portal from
 * Website Setup > Dashboard Features. Each row's `feature_key` must match a
 * dashboard_feature_enabled('<portal>', '<key>') check somewhere in the
 * student/parent/teacher blades — this seeder only keeps labels/descriptions
 * current. It deliberately never touches `status` on an existing row, so an
 * admin's enable/disable choice survives every re-run of this seeder.
 */
class DashboardFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ---- Student portal ----
            ['portal' => 'student', 'feature_key' => 'kea_voice',          'label' => 'Kea Voice Avatar',        'description' => 'Tap-to-hear spoken summary (greeting, Brain Level, next action) in the dashboard hero.'],
            ['portal' => 'student', 'feature_key' => 'brain_level',        'label' => 'Brain Level',             'description' => 'Level number and XP progress bar toward the next level.'],
            ['portal' => 'student', 'feature_key' => 'knowledge_tree',     'label' => 'Knowledge Tree',          'description' => 'Growth stage and active-days tile that replaces a streak.'],
            ['portal' => 'student', 'feature_key' => 'next_best_action',   'label' => 'Next Best Action',        'description' => 'The one-suggestion "what to do now" card with a plain-language reason.'],
            ['portal' => 'student', 'feature_key' => 'whats_next',         'label' => "What's Next list",        'description' => 'A short list of upcoming skills not yet started.'],
            ['portal' => 'student', 'feature_key' => 'skill_mastery_overview', 'label' => 'Skill Mastery Overview', 'description' => 'The Not Started / Developing / Proficient / Advanced progress bar and counts.'],
            ['portal' => 'student', 'feature_key' => 'mistake_bank',       'label' => 'Mistake Bank',            'description' => 'Skills needing another look, with recovery stage (Needs practice / First / Second recovery).'],
            ['portal' => 'student', 'feature_key' => 'refresh_time',       'label' => 'Refresh Time',            'description' => 'Spaced-review reminders for mastered skills that have gone stale.'],
            ['portal' => 'student', 'feature_key' => 'badges',             'label' => 'Badges',                  'description' => 'Per-subject mastery badges (bronze/silver/gold).'],
            ['portal' => 'student', 'feature_key' => 'personal_best',      'label' => 'Personal Best',           'description' => "This week's accuracy vs. last week's, never vs. classmates."],
            ['portal' => 'student', 'feature_key' => 'verified_skills',    'label' => 'Verified Skills',         'description' => 'A grid of mastered skills with accuracy % and the date earned.'],
            ['portal' => 'student', 'feature_key' => 'daily_goals',        'label' => "Today's Goals",           'description' => 'A short checklist the student picks for themselves each visit.'],
            ['portal' => 'student', 'feature_key' => 'reflection_journal', 'label' => 'Reflection Journal',      'description' => 'Two short daily questions: what was hard, what strategy worked.'],
            ['portal' => 'student', 'feature_key' => 'weekly_wins',        'label' => 'Mastered This Week tile', 'description' => 'Stat tile showing skills mastered in the last 7 days — hidden when there are none.'],
            ['portal' => 'student', 'feature_key' => 'milestone_banner',   'label' => 'Milestone Celebration',   'description' => 'One-time banner the moment a skill first reaches Advanced.'],
            ['portal' => 'student', 'feature_key' => 'ai_ask_helper',      'label' => 'AI Study Helper (Ask)',   'description' => 'General homework question helper on the AI Study Helper page.'],
            ['portal' => 'student', 'feature_key' => 'teach_kea',          'label' => 'Teach Kea',               'description' => 'Explain a skill in your own words; Kea checks your understanding.'],
            ['portal' => 'student', 'feature_key' => 'ai_fact_checker',    'label' => 'AI Fact-Checker',         'description' => 'Paste a claim; Brainbot verifies true / false / genuinely unclear.'],

            // ---- Teacher portal ----
            ['portal' => 'teacher', 'feature_key' => 'skill_mastery_report', 'label' => 'Skill Mastery Report', 'description' => 'Class-wide mastery breakdown, one row per student, never ranked.'],
            ['portal' => 'teacher', 'feature_key' => 'struggle_flag',        'label' => 'Struggle Flag Column',  'description' => 'The "possible struggle" column inside the Skill Mastery Report.'],
            ['portal' => 'teacher', 'feature_key' => 'quiz_skill_tagging',   'label' => 'Quiz Skill Tagging',    'description' => 'Skill dropdown on each homework quiz question, linking it to the mastery system.'],

            // ---- Parent portal ----
            ['portal' => 'parent', 'feature_key' => 'learning_snapshot', 'label' => 'Learning Snapshot',      'description' => 'Currently-working-on skills, needs-review list, and the mastery overview bar.'],
            ['portal' => 'parent', 'feature_key' => 'brain_level',       'label' => 'Brain Level',             'description' => "Their child's level number and progress bar."],
            ['portal' => 'parent', 'feature_key' => 'knowledge_tree',    'label' => 'Knowledge Tree',          'description' => "Their child's growth stage and active-days tile."],
            ['portal' => 'parent', 'feature_key' => 'badges',            'label' => 'Badges',                  'description' => "Their child's per-subject mastery badges."],
            ['portal' => 'parent', 'feature_key' => 'personal_best',     'label' => 'Personal Best',           'description' => "This week's accuracy vs. last week's."],
            ['portal' => 'parent', 'feature_key' => 'verified_skills',   'label' => 'Verified Skills',         'description' => "Grid of the child's mastered skills with evidence."],
            ['portal' => 'parent', 'feature_key' => 'weekly_wins',       'label' => 'Good News This Week',     'description' => 'Proactive weekly banner of real wins — never fabricated.'],
            ['portal' => 'parent', 'feature_key' => 'learning_guide',    'label' => 'Dashboard Guide Link',    'description' => '"What does this mean?" button linking to the plain-language explainer page.'],
        ];

        foreach (array_values($rows) as $i => $r) {
            $existing = DashboardFeature::where('portal', $r['portal'])->where('feature_key', $r['feature_key'])->first();

            if ($existing) {
                $existing->label       = $r['label'];
                $existing->description = $r['description'];
                $existing->sort_order  = $i + 1;
                $existing->save(); // status is deliberately left untouched
            } else {
                DashboardFeature::create([
                    'portal'      => $r['portal'],
                    'feature_key' => $r['feature_key'],
                    'label'       => $r['label'],
                    'description' => $r['description'],
                    'sort_order'  => $i + 1,
                    'status'      => 1,
                ]);
            }
        }
    }
}

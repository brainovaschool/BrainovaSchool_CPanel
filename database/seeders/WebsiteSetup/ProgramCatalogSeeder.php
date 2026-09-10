<?php

namespace Database\Seeders\WebsiteSetup;

use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Models\WebsiteSetup\ProgramFocus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the 4 program categories + their focus areas, then imports the
 * starter courses from database/seeders/data/legacy_programs.php.
 * Idempotent — safe to run more than once (matches on slug).
 */
class ProgramCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'          => 'Homeschooling',
                'slug'          => 'homeschooling',
                'tagline'       => 'A full curriculum, taught with structure and care.',
                'hero_title'    => 'Homeschooling with Brainova',
                'hero_subtitle' => 'The framework of a real school with the flexibility of learning at home — Pre-K through Grade 5, with a dedicated teacher and weekly reporting.',
                'accent'        => 'indigo',
                'sort_order'    => 1,
                'focuses'       => ['Pre-K & Kindergarten', '1st Grade', '2nd Grade', '3rd Grade', '4th Grade', '5th Grade'],
            ],
            [
                'name'          => 'Tutoring',
                'slug'          => 'tutoring',
                'tagline'       => 'Targeted support in the subjects that matter most.',
                'hero_title'    => 'Tutoring at Brainova',
                'hero_subtitle' => 'One-to-one and small-group coaching across academic subjects, with placement by short diagnostic and progress shared with guardians.',
                'accent'        => 'teal',
                'sort_order'    => 2,
                'focuses'       => ['Academic Subjects', 'Private Lessons', '1-on-1 Coaching', 'Test Prep'],
            ],
            [
                'name'          => 'Electives & Enrichment',
                'slug'          => 'electives-enrichment',
                'tagline'       => 'Room to explore beyond the core subjects.',
                'hero_title'    => 'Electives & Enrichment',
                'hero_subtitle' => 'Arts, coding and technology, world languages, life skills, and wellbeing — taught by the same faculty, tracked with the same weekly reporting.',
                'accent'        => 'violet',
                'sort_order'    => 3,
                'focuses'       => ['Arts', 'Health & Wellness', 'Coding & Tech', 'Life Skills', 'World Languages'],
            ],
            [
                'name'          => 'Social Clubs',
                'slug'          => 'social-clubs',
                'tagline'       => 'Friendships built around shared interests.',
                'hero_title'    => 'Social Clubs',
                'hero_subtitle' => 'Low-pressure, moderated clubs where learners meet others who share their interests.',
                'accent'        => 'coral',
                'sort_order'    => 4,
                'focuses'       => ['Games & Trivia', 'Art Hub'],
            ],
        ];

        $focusIndex = [];   // "category-slug/focus-slug" => focus id

        foreach ($categories as $catData) {
            $focuses = $catData['focuses'];
            unset($catData['focuses']);

            $category = ProgramCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData + ['status' => 1]
            );

            foreach ($focuses as $i => $focusName) {
                $focusSlug = Str::slug($focusName);
                $focus = ProgramFocus::updateOrCreate(
                    ['program_category_id' => $category->id, 'slug' => $focusSlug],
                    ['name' => $focusName, 'sort_order' => $i + 1, 'status' => 1]
                );
                $focusIndex[$category->slug . '/' . $focusSlug] = $focus->id;
            }
        }

        $this->importLegacyCourses($focusIndex);
    }

    private function importLegacyCourses(array $focusIndex): void
    {
        $path = database_path('seeders/data/legacy_programs.php');
        if (!is_readable($path)) {
            return;
        }

        $courses = require $path;
        if (!is_array($courses)) {
            return;
        }

        // course slug => [category slug, focus slug]
        $map = [
            'basic-to-intermediate-maths'          => ['tutoring', 'academic-subjects'],
            'advanced-maths'                        => ['tutoring', 'academic-subjects'],
            'basic-to-intermediate-english'         => ['tutoring', 'academic-subjects'],
            'advanced-english'                      => ['tutoring', 'academic-subjects'],
            'brainova-stem-discovery'               => ['electives-enrichment', 'coding-tech'],
            'brainova-ai-digital-literacy'          => ['electives-enrichment', 'coding-tech'],
            'brainova-math-confidence-pathway'      => ['tutoring', 'academic-subjects'],
            'brainova-math-competition-studio'      => ['tutoring', 'test-prep'],
            'brainova-english-basic-intermediate'   => ['tutoring', 'academic-subjects'],
            'brainova-english-communication-studio' => ['tutoring', 'academic-subjects'],
            'brainova-biliteracy-foundations'       => ['electives-enrichment', 'world-languages'],
            'brainova-leadership-voice-studio'      => ['electives-enrichment', 'life-skills'],
            'brainova-future-projects-lab'          => ['electives-enrichment', 'life-skills'],
            'junior-coding-explorers'               => ['electives-enrichment', 'coding-tech'],
            'science-lab-juniors'                   => ['electives-enrichment', 'coding-tech'],
            'creative-writing-spark'                => ['electives-enrichment', 'arts'],
            'study-skills-bootcamp'                 => ['tutoring', 'test-prep'],
        ];
        $default = ['tutoring', 'academic-subjects'];

        $categoryIds = ProgramCategory::pluck('id', 'slug');

        foreach (array_values($courses) as $i => $course) {
            if (empty($course['slug']) || empty($course['title'])) {
                continue;
            }

            [$catSlug, $focusSlug] = $map[$course['slug']] ?? $default;
            $categoryId = $categoryIds[$catSlug] ?? null;
            if (!$categoryId) {
                continue;
            }
            $focusId = $focusIndex[$catSlug . '/' . $focusSlug] ?? null;

            Program::updateOrCreate(
                ['slug' => $course['slug']],
                [
                    'program_category_id' => $categoryId,
                    'program_focus_id'    => $focusId,
                    'title'               => $course['title'],
                    'badge'               => $course['badge'] ?? null,
                    'description'         => $course['description'] ?? null,
                    'age_range'           => $course['age_range'] ?? null,
                    'grade'               => $course['grade'] ?? null,
                    'lessons'             => $course['lessons'] ?? null,
                    'duration'            => $course['duration'] ?? null,
                    'enrolled'            => $course['enrolled'] ?? null,
                    'price'               => $course['price'] ?? null,
                    'accent'              => $course['accent'] ?? 'teal',
                    'image_url'           => $course['image'] ?? null,
                    'meta_description'    => $course['meta_description'] ?? null,
                    'overview'            => isset($course['overview']) && is_array($course['overview']) ? $course['overview'] : [],
                    'highlights'          => isset($course['highlights']) && is_array($course['highlights']) ? $course['highlights'] : [],
                    'format'              => $course['format'] ?? null,
                    'sort_order'          => $i + 1,
                    'status'              => 1,
                ]
            );
        }
    }
}

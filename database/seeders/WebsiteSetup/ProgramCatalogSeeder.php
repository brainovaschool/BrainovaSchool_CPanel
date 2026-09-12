<?php

namespace Database\Seeders\WebsiteSetup;

use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Models\WebsiteSetup\ProgramFocus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the 4 program categories + their focus areas, then the starter
 * courses (legacy catalogue + one programme per empty category).
 *
 * IMPORTANT: this runs on every visit to the keyed installer route
 * (/db/migrate/{key}), because it's the only way this host can apply new
 * rows without shell access. That means it MUST be safe to re-run forever
 * — and "safe" means CREATE-ONLY for anything the school can since edit in
 * the dashboard. A category/focus/program is only ever written here the
 * first time it's created; once it exists, this seeder never touches its
 * content again (the one exception: it will fill in a photo, and only a
 * photo, on a program that still has no image at all — see imageFor()).
 * Earlier versions of this seeder used updateOrCreate() with the full data
 * array, which silently overwrote every dashboard edit (title, overview,
 * description, hero text, ...) back to these hardcoded defaults on every
 * single installer run. That was the bug — don't reintroduce it.
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

            $category = ProgramCategory::where('slug', $catData['slug'])->first()
                ?: ProgramCategory::create($catData + ['status' => 1]);

            foreach ($focuses as $i => $focusName) {
                $focusSlug = Str::slug($focusName);
                $focus = ProgramFocus::where('program_category_id', $category->id)->where('slug', $focusSlug)->first()
                    ?: ProgramFocus::create([
                        'program_category_id' => $category->id,
                        'slug'                => $focusSlug,
                        'name'                => $focusName,
                        'sort_order'          => $i + 1,
                        'status'              => 1,
                    ]);
                $focusIndex[$category->slug . '/' . $focusSlug] = $focus->id;
            }
        }

        $this->importLegacyCourses($focusIndex);
        $this->seedStarterPrograms($focusIndex);
    }

    /**
     * One distinct thumbnail per programme (matched on slug). These are neutral
     * hosted photos so no card is ever blank or a duplicate; a real image
     * uploaded against a Programme in the dashboard always takes precedence.
     */
    private function imageFor(string $slug): ?string
    {
        static $ids = [
            // legacy catalogue
            'basic-to-intermediate-maths'          => '1635070041078-e363dbe005cb',
            'advanced-maths'                       => '1509228627152-72ae9ae6848d',
            'basic-to-intermediate-english'        => '1456513080510-7bf3a84b82f8',
            'advanced-english'                     => '1455390582262-044cdead277a',
            'brainova-stem-discovery'              => '1485827404703-89b55fcc595e',
            'brainova-ai-digital-literacy'         => '1677442136019-21780ecad995',
            'brainova-math-confidence-pathway'     => '1503676260728-1c00da094a0b',
            'brainova-math-competition-studio'     => '1519389950473-47ba0277781c',
            'brainova-english-basic-intermediate'  => '1497633762265-9d179a990aa6',
            'brainova-english-communication-studio'=> '1475721027785-f74eccf877e2',
            'brainova-biliteracy-foundations'      => '1513258496099-48168024aec0',
            'brainova-leadership-voice-studio'     => '1524178232363-1fb2b075b655',
            'brainova-future-projects-lab'         => '1522071820081-009f0129c71c',
            'junior-coding-explorers'              => '1516321318423-f06f85e504b3',
            'science-lab-juniors'                  => '1532094349884-543bc11b234d',
            'creative-writing-spark'               => '1452860606245-08befc0ff44b',
            'study-skills-bootcamp'                => '1434030216411-0b793f4b4173',
            'games-trivia-club'                    => '1503945438517-f65904a52ce6',
            'art-hub'                              => '1541961017774-22349e4a1262',
        ];

        // Homeschooling grades — South Asian / Pakistani-context children,
        // age-matched per grade band (real photo search, not guessed IDs).
        static $pexelsIds = [
            'pre-k-kindergarten-programme' => 17639079,  // toddler at a table, colourful kindergarten room
            'grade-1-programme'            => 38245944,  // young schoolboy, bright blue uniform
            'grade-2-programme'            => 11697526,  // young student, uniform + tie
            'grade-3-programme'            => 12714636,  // boy with glasses, classroom, curious
            'grade-4-programme'            => 28646079,  // girl focused on writing, classroom
            'grade-5-programme'            => 11445244,  // older primary pupils, focused studying
        ];

        if (!empty($pexelsIds[$slug])) {
            return 'https://images.pexels.com/photos/' . $pexelsIds[$slug] . '/pexels-photo-' . $pexelsIds[$slug] . '.jpeg?auto=compress&cs=tinysrgb&w=1200';
        }

        if (empty($ids[$slug])) {
            return null;
        }

        return 'https://images.unsplash.com/photo-' . $ids[$slug] . '?auto=format&fit=crop&w=1200&q=80';
    }

    /**
     * If a program still has no photo at all (no dashboard upload, no
     * image_url), give it one. Never touches a program that already has
     * either — that's the school's choice at that point, seeded or not.
     */
    private function fillBlankImage(Program $program, string $slug): void
    {
        if ($program->upload_id || $program->image_url) {
            return;
        }

        $img = $this->imageFor($slug);
        if ($img) {
            $program->image_url = $img;
            $program->save();
        }
    }

    /**
     * Give the categories that had no legacy courses (Homeschooling, Social
     * Clubs) at least one real programme each. Create-only — see class docblock.
     */
    private function seedStarterPrograms(array $focusIndex): void
    {
        $categoryIds = ProgramCategory::pluck('id', 'slug');

        $rows = [
            // Homeschooling — one programme per grade band
            ['homeschooling', 'pre-k-kindergarten', 'Pre-K & Kindergarten Programme', 'Ages 3–5',  'Early Years',
                'Play-based, inquiry-driven learning that builds language, number sense, fine-motor skills and curiosity — EYFS-aligned, with a dedicated teacher and weekly check-ins.'],
            ['homeschooling', '1st-grade', 'Grade 1 Programme', 'Ages 6–7', 'Grade 1',
                'The full Grade 1 curriculum — literacy, numeracy, discovery science and the arts — taught online with structure, live sessions and weekly progress shared to your parent account.'],
            ['homeschooling', '2nd-grade', 'Grade 2 Programme', 'Ages 7–8', 'Grade 2',
                'Grade 2 across literacy, numeracy, STEM and humanities, with project work that connects subjects and a teacher who knows your child.'],
            ['homeschooling', '3rd-grade', 'Grade 3 Programme', 'Ages 8–9', 'Grade 3',
                'Grade 3 with growing independence — stronger writing, multiplication and division, inquiry science and first research projects.'],
            ['homeschooling', '4th-grade', 'Grade 4 Programme', 'Ages 9–10', 'Grade 4',
                'Grade 4 building study skills alongside the core curriculum — extended reading, fractions and decimals, and cross-curricular projects.'],
            ['homeschooling', '5th-grade', 'Grade 5 Programme', 'Ages 10–11', 'Grade 5',
                'Grade 5 preparing learners for the transition ahead — analytical writing, pre-algebra thinking, and independent project work.'],

            // Social Clubs
            ['social-clubs', 'games-trivia', 'Games & Trivia Club', 'Ages 7–14', 'All levels',
                'A weekly live club of quizzes, strategy games and friendly team challenges — a low-pressure way to meet other Brainova learners and think fast together.'],
            ['social-clubs', 'art-hub', 'Art Hub', 'Ages 6–14', 'All levels',
                'A creative club where members share work, try a new medium each month and take part in seasonal showcases — drawing, digital art, craft and mixed media.'],
        ];

        foreach (array_values($rows) as $i => $r) {
            [$catSlug, $focusSlug, $title, $age, $grade, $desc] = $r;
            $categoryId = $categoryIds[$catSlug] ?? null;
            if (!$categoryId) {
                continue;
            }
            $slug = Str::slug($title);

            $existing = Program::where('slug', $slug)->first();
            if ($existing) {
                $this->fillBlankImage($existing, $slug);
                continue;
            }

            Program::create([
                'slug'                 => $slug,
                'program_category_id'  => $categoryId,
                'program_focus_id'     => $focusIndex[$catSlug . '/' . $focusSlug] ?? null,
                'title'                => $title,
                'image_url'            => $this->imageFor($slug),
                'badge'                => $catSlug === 'social-clubs' ? 'Club' : 'Homeschooling',
                'description'          => $desc,
                'age_range'            => $age,
                'grade'                => $grade,
                'lessons'              => $catSlug === 'social-clubs' ? 'Weekly sessions' : 'Full year',
                'duration'             => $catSlug === 'social-clubs' ? 'Ongoing' : 'Academic year',
                'enrolled'             => 'Open enrolment',
                'price'                => 'Contact for fee',
                'accent'               => $catSlug === 'social-clubs' ? 'coral' : 'indigo',
                'meta_description'     => $title . ' at Brainova School — online.',
                'overview'             => [$desc],
                'highlights'           => ['Fully online', 'Small groups', 'Weekly progress to your parent account'],
                'format'               => $catSlug === 'social-clubs' ? 'One live session weekly.' : 'Daily live sessions + guided independent work on the learning portal.',
                'sort_order'           => $i + 1,
                'status'               => 1,
            ]);
        }
    }

    /** Create-only — see class docblock. */
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

            $existing = Program::where('slug', $course['slug'])->first();
            if ($existing) {
                $this->fillBlankImage($existing, $course['slug']);
                continue;
            }

            $focusId = $focusIndex[$catSlug . '/' . $focusSlug] ?? null;

            Program::create([
                'slug'                 => $course['slug'],
                'program_category_id'  => $categoryId,
                'program_focus_id'     => $focusId,
                'title'                => $course['title'],
                'badge'                => $course['badge'] ?? null,
                'description'          => $course['description'] ?? null,
                'age_range'            => $course['age_range'] ?? null,
                'grade'                => $course['grade'] ?? null,
                'lessons'              => $course['lessons'] ?? null,
                'duration'             => $course['duration'] ?? null,
                'enrolled'             => $course['enrolled'] ?? null,
                'price'                => $course['price'] ?? null,
                'accent'               => $course['accent'] ?? 'teal',
                'image_url'            => $this->imageFor($course['slug']) ?? ($course['image'] ?? null),
                'meta_description'     => $course['meta_description'] ?? null,
                'overview'             => isset($course['overview']) && is_array($course['overview']) ? $course['overview'] : [],
                'highlights'           => isset($course['highlights']) && is_array($course['highlights']) ? $course['highlights'] : [],
                'format'               => $course['format'] ?? null,
                'sort_order'           => $i + 1,
                'status'               => 1,
            ]);
        }
    }
}

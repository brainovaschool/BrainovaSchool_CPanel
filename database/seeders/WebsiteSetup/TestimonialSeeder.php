<?php

namespace Database\Seeders\WebsiteSetup;

use App\Models\WebsiteSetup\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Starter testimonials + reviews spanning Brainova's scope (homeschooling,
 * tutoring, early years, coding, art club, test prep). Idempotent — matched on
 * name + type. Edit or remove any of these in Website Setup > Testimonials.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ---- Testimonials (no star rating, longer voice) ----
            [
                'type' => 'testimonial', 'rating' => null,
                'name' => 'Ayesha Siddiqui', 'role' => 'Parent · Grade 3, Homeschooling',
                'quote' => "We moved to Brainova after two years of trying to homeschool on our own. Having a real teacher, a proper timetable and a weekly report changed everything. My daughter is reading above her level and actually looks forward to her morning sessions.",
            ],
            [
                'type' => 'testimonial', 'rating' => null,
                'name' => 'Bilal Ahmed', 'role' => 'Parent · Grade 6, Tutoring',
                'quote' => "The maths tutoring found gaps we did not know were there. They began with a short assessment, built a plan around it, and six months on my son solves problems he used to avoid. The tutor messages us after every session.",
            ],
            [
                'type' => 'testimonial', 'rating' => null,
                'name' => 'Fatima Noor', 'role' => 'Parent · Kindergarten',
                'quote' => "I was nervous about screen time for a five-year-old, but the early years sessions are short, playful and hands-on. She is doing phonics, puzzles and number games with a small group of children she now calls her friends.",
            ],
            [
                'type' => 'testimonial', 'rating' => null,
                'name' => 'Hamza Tariq', 'role' => 'Student · Age 12, Coding Explorers',
                'quote' => "I have built three games and a working weather app. The teacher never just gives the answer — they ask questions until I work it out myself. It is the best part of my week.",
            ],
            [
                'type' => 'testimonial', 'rating' => null,
                'name' => 'Zara Khan', 'role' => 'Parent · Art Hub',
                'quote' => "The Art Hub gave my quiet son a place to share his work without pressure. Every month there is a new medium and a small showcase. His confidence has grown as much as his sketchbook.",
            ],
            [
                'type' => 'testimonial', 'rating' => null,
                'name' => 'Usman Malik', 'role' => 'Parent · Test Prep',
                'quote' => "Structured, honest and calm. No false promises — just steady practice, timed papers and clear feedback. My daughter walked into her exam prepared and relaxed.",
            ],

            // ---- Reviews (star rating, short) ----
            [
                'type' => 'review', 'rating' => 5,
                'name' => 'Nadia Rehman', 'role' => 'Parent',
                'quote' => "Best decision we made this year. Small classes, real teachers, and I always know how my children are doing.",
            ],
            [
                'type' => 'review', 'rating' => 5,
                'name' => 'Omar Farooq', 'role' => 'Parent',
                'quote' => "The parent portal alone is worth it — attendance, progress and teacher notes in one place, updated every week.",
            ],
            [
                'type' => 'review', 'rating' => 5,
                'name' => 'Sana Javed', 'role' => 'Parent · Grade 1',
                'quote' => "Warm, patient teachers and a proper curriculum. My son settled in within a week.",
            ],
            [
                'type' => 'review', 'rating' => 4,
                'name' => 'Kamran Shah', 'role' => 'Parent',
                'quote' => "Excellent teaching and communication. I would love a few more elective options, but the core programme is strong.",
            ],
            [
                'type' => 'review', 'rating' => 5,
                'name' => 'Iqra Aslam', 'role' => 'Student · Grade 8',
                'quote' => "My English tutor took me from dreading essays to enjoying them. Genuinely.",
            ],
        ];

        $palette = ['0097b2', '5e17eb', '5ce1e6', '8c52ff'];

        foreach (array_values($rows) as $i => $r) {
            $bg = $palette[$i % count($palette)];

            Testimonial::updateOrCreate(
                ['name' => $r['name'], 'type' => $r['type']],
                [
                    'role'       => $r['role'],
                    'quote'      => $r['quote'],
                    'rating'     => $r['rating'],
                    'image_url'  => 'https://ui-avatars.com/api/?name=' . urlencode($r['name'])
                                    . '&background=' . $bg . '&color=ffffff&size=128&bold=true&format=png',
                    'sort_order' => $i + 1,
                    'status'     => 1,
                ]
            );
        }
    }
}

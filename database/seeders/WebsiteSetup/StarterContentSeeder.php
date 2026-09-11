<?php

namespace Database\Seeders\WebsiteSetup;

use App\Models\News;
use App\Models\NewsTranslate;
use App\Models\NoticeBoard;
use App\Models\NoticeBoardTranslate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Small, specific pieces of real content the school asked for:
 *  - remove the leftover CodeCanyon demo notices ("exam", "exam2")
 *  - the "coding demo class" notice (idempotent, matched on title)
 *  - the body of the "Cube Root" blog post (only if that post already exists)
 * Everything else on the Notice Board / News is managed in the dashboard.
 */
class StarterContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeDemoNotices();
        $this->codingDemoNotice();
        $this->cubeRootBlog();
    }

    /** Deletes the CodeCanyon sample notices ("exam" / "exam2") by exact
     *  title match only — never touches anything the school actually wrote. */
    private function removeDemoNotices(): void
    {
        if (!Schema::hasTable('notice_boards')) {
            return;
        }

        NoticeBoard::whereRaw('LOWER(title) IN (?, ?)', ['exam', 'exam2'])->delete();
    }

    private function codingDemoNotice(): void
    {
        if (!Schema::hasTable('notice_boards') || !Schema::hasTable('notice_board_translates')) {
            return;
        }

        $sessionId = setting('session');
        if (!$sessionId) {
            return;
        }

        $title = 'Free Demo Class — Coding (20 September)';
        $html  = '<p>Join a <strong>free live demo class</strong> of our Coding programme on '
               . '<strong>Saturday, 20 September 2026</strong>. Your child meets the teacher, writes '
               . 'their first lines of code and builds a small project in a friendly small group.</p>'
               . '<p><strong>Ages:</strong> 8&ndash;14 &nbsp;&bull;&nbsp; <strong>Where:</strong> Online '
               . '(joining link sent after booking) &nbsp;&bull;&nbsp; <strong>Duration:</strong> 45 minutes</p>'
               . '<p>Places are limited. Book a seat on the <a href="/book-a-free-trial">Book a Free Trial</a> '
               . 'page and choose the Coding programme.</p>';

        // Create once. If it already exists, leave it — the school may have
        // edited it (image, wording, date) in the dashboard.
        if (NoticeBoard::where('title', $title)->exists()) {
            return;
        }

        $notice = new NoticeBoard();
        $notice->title          = $title;
        $notice->session_id     = $sessionId;
        $notice->date           = '2026-09-20';
        $notice->publish_date   = now();
        $notice->description    = $html;
        $notice->attachment     = null;
        $notice->department_id  = null;
        $notice->is_visible_web = 1;
        $notice->status         = 1;
        $notice->visible_to     = null;
        $notice->save();

        NoticeBoardTranslate::updateOrCreate(
            ['notice_board_id' => $notice->id, 'locale' => 'en'],
            ['title' => $title, 'description' => $html]
        );
    }

    private function cubeRootBlog(): void
    {
        if (!Schema::hasTable('news') || !Schema::hasTable('news_translates')) {
            return;
        }

        $ids = DB::table('news_translates')->where('title', 'like', '%cube%root%')->pluck('news_id')
            ->merge(DB::table('news')->where('title', 'like', '%cube%root%')->pluck('id'))
            ->unique()
            ->filter();

        if ($ids->isEmpty()) {
            return;
        }

        $html = <<<'HTML'
<p><strong>Can you find &#8731;13,824 without a calculator?</strong> &#129327;</p>
<h3>&#128293; The trick</h3>
<p><strong>13 | 824</strong> &mdash; split the number into two parts.</p>
<h3>&#127919; Step 1: check the last digit</h3>
<p>824 ends in <strong>4</strong>, so the answer ends in <strong>4</strong>.</p>
<p>Quick memory hack for the last digit:</p>
<ul>
<li>0, 1, 4, 5, 6, 9 &rarr; stay the same</li>
<li>2 &harr; 8</li>
<li>3 &harr; 7</li>
</ul>
<h3>&#129504; Step 2: look at 13</h3>
<p>Which cube fits without going over?</p>
<ul>
<li>2&sup3; = 8 &#9989;</li>
<li>3&sup3; = 27 &#10060;</li>
</ul>
<p>So the first digit is <strong>2</strong>.</p>
<h3>&#128640; Step 3: put them together</h3>
<p>2 | 4 &rarr; <strong>24</strong></p>
<p>&#127881; <strong>&#8731;13,824 = 24</strong></p>
<p>One split. One tiny hack. One answer &mdash; and a real feel for how numbers work. That number sense is exactly what we build at Brainova.</p>
HTML;

        foreach ($ids as $id) {
            $news = News::find($id);
            if (!$news) {
                continue;
            }

            $tr = NewsTranslate::where('news_id', $id)->where('locale', 'en')->first()
                ?: NewsTranslate::where('news_id', $id)->first();

            // One-time only: once the body carries our marker, never touch this
            // post again (so a featured image uploaded later is left alone).
            if ($tr && str_contains((string) $tr->description, '13,824')) {
                continue;
            }

            $dirty = false;
            if (Schema::hasColumn('news', 'type') && $news->type !== 'blog') {
                $news->type = 'blog';
                $dirty = true;
            }
            // Drop the old "cube root" diagram image once — the school uploads a
            // new featured image in the dashboard; until then the default shows.
            if ($news->upload_id) {
                $news->upload_id = null;
                $dirty = true;
            }
            if ($dirty) {
                $news->save();
            }

            if ($tr) {
                $tr->description = $html;
                $tr->save();
            } else {
                NewsTranslate::create([
                    'news_id'     => $id,
                    'locale'      => 'en',
                    'title'       => 'Cube Root in Seconds',
                    'description' => $html,
                ]);
            }
        }
    }
}

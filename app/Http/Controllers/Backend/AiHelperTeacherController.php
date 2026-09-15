<?php

namespace App\Http\Controllers\Backend;

use PDF;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\WebsiteSetup\AiHelperRepository;

class AiHelperTeacherController extends Controller
{
    private $repo;

    public function __construct(AiHelperRepository $repo)
    {
        $this->repo = $repo;
    }

    private function guard(): void
    {
        // Teachers (role_id 5) and Super Admin (role_id 1, for previewing/managing the tool).
        if (!Auth::check() || !in_array((int) Auth::user()->role_id, [1, 5], true)) {
            abort(403);
        }
    }

    public function index()
    {
        $this->guard();

        $data['title']       = ___('settings.ai_help_teacher');
        $data['button_text'] = setting('ai_helper_button_text') ?: 'Generate Lesson Plan';
        $data['mascot']      = setting('ai_helper_teacher_mascot') ? globalAsset(setting('ai_helper_teacher_mascot')) : null;

        return view('backend.ai-helper.teacher', compact('data'));
    }

    private function fields(Request $request): array
    {
        return $request->validate([
            'grade'        => 'required|string|max:100',
            'subject'      => 'required|string|max:100',
            'term'         => 'required|string|max:100',
            'unit'         => 'required|string|max:150',
            'module'       => 'required|string|max:150',
            'lesson_title' => 'required|string|max:150',
        ]);
    }

    private function filename(array $fields, string $suffix): string
    {
        $parts = [
            'g' . \Illuminate\Support\Str::slug($fields['grade'], '_'),
            \Illuminate\Support\Str::slug($fields['subject'], '_'),
            't' . \Illuminate\Support\Str::slug($fields['term'], '_'),
            \Illuminate\Support\Str::slug($fields['unit'], '_'),
            \Illuminate\Support\Str::slug($fields['module'], '_'),
            \Illuminate\Support\Str::slug($fields['lesson_title'], '_'),
            $suffix,
        ];
        return implode('_', $parts) . '.pdf';
    }

    /** Returns an error response to short-circuit the caller with, or null if allowed. Super Admin is exempt. */
    private function enforceLimit(): ?\Illuminate\Http\Response
    {
        if ((int) Auth::user()->role_id === 1) {
            return null;
        }

        $check = $this->repo->checkLimit(Auth::id(), 'teacher');
        if (!$check['allowed']) {
            return response($check['message'], 429);
        }

        return null;
    }

    private function logUsage(string $tool, array $fields): void
    {
        $summary = "{$fields['grade']} / {$fields['subject']} / {$fields['term']} / {$fields['unit']} / {$fields['module']} / {$fields['lesson_title']}";
        $this->repo->logUsage(Auth::id(), Auth::user()->name, 'teacher', $tool, $summary);
    }

    private function driveFolderPath(array $fields): array
    {
        $grade = trim($fields['grade']);
        $term  = trim($fields['term']);

        return [
            setting('ai_helper_drive_root_folder') ?: 'Brainova Lessons',
            stripos($grade, 'grade') === 0 ? $grade : 'Grade ' . $grade,
            $fields['subject'],
            stripos($term, 'term') === 0 ? $term : 'Term ' . $term,
            $fields['unit'],
            $fields['module'],
            $fields['lesson_title'],
        ];
    }

    public function generate(Request $request)
    {
        $this->guard();
        if ($limited = $this->enforceLimit()) {
            return $limited;
        }
        $fields = $this->fields($request);
        $result = $this->repo->generateLessonPlan($fields);

        if (!$result['ok']) {
            return response($result['message'], 422);
        }

        $this->logUsage('lesson_plan_text', $fields);

        $html = e($result['text']);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = nl2br($html);

        $data['fields']       = $fields;
        $data['content_html'] = $html;

        $pdf = PDF::loadView('frontend.ai-helper-lesson-pdf', compact('data'));
        return $pdf->download($this->filename($fields, 'text'));
    }

    public function generateVisual(Request $request)
    {
        $this->guard();
        if ($limited = $this->enforceLimit()) {
            return $limited;
        }
        $fields = $this->fields($request);
        $result = $this->repo->generateLessonPlanStructured($fields);

        if (!$result['ok']) {
            return response($result['message'], 422);
        }

        $this->logUsage('lesson_plan_visual', $fields);

        $data['fields'] = $fields;
        $data['plan']   = $result['data'];
        $data['logo']   = globalAsset(setting('dark_logo'), 'favicon.png');

        $pdf = PDF::loadView('frontend.ai-helper-lesson-visual-pdf', compact('data'));

        if (setting('ai_helper_delivery_mode') === 'drive') {
            $path  = $this->driveFolderPath($fields);
            $drive = app(\App\Services\GoogleDriveClient::class)->uploadToPath($path, 'lesson-plan.pdf', $pdf->output());
            if (!$drive['ok']) {
                return response($drive['message'], 422);
            }
            return response('Saved to Google Drive: ' . implode(' / ', $path) . ' / lesson-plan.pdf');
        }

        return $pdf->download($this->filename($fields, 'lessonplan'));
    }

    public function generateSlides(Request $request)
    {
        $this->guard();
        if ($limited = $this->enforceLimit()) {
            return $limited;
        }
        $fields = $this->fields($request);
        $result = $this->repo->generateLessonPlanStructured($fields);

        if (!$result['ok']) {
            return response($result['message'], 422);
        }

        $this->logUsage('lesson_plan_slides', $fields);

        $data['fields'] = $fields;
        $data['plan']   = $result['data'];
        $data['logo']   = globalAsset(setting('dark_logo'), 'favicon.png');

        $pdf = PDF::loadView('frontend.ai-helper-lesson-slides-pdf', compact('data'))->setPaper('a4', 'landscape');

        if (setting('ai_helper_delivery_mode') === 'drive') {
            $path  = $this->driveFolderPath($fields);
            $drive = app(\App\Services\GoogleDriveClient::class)->uploadToPath($path, 'slides.pdf', $pdf->output());
            if (!$drive['ok']) {
                return response($drive['message'], 422);
            }
            return response('Saved to Google Drive: ' . implode(' / ', $path) . ' / slides.pdf');
        }

        return $pdf->download($this->filename($fields, 'slide'));
    }
}

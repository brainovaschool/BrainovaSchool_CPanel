<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\WebsiteSetup\AiHelperRepository;

class AiHelpController extends Controller
{
    private $repo;

    public function __construct(AiHelperRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['title']         = setting('ai_help_student_page_title') ?: 'AI Study Helper';
        $data['button_text']   = setting('ai_help_student_button_text') ?: 'Ask';
        $data['question_label'] = setting('ai_help_student_question_label') ?: 'What are you stuck on?';
        $data['mascot']        = setting('ai_helper_student_mascot') ? globalAsset(setting('ai_helper_student_mascot')) : null;

        return view('student-panel.ai-help.index', compact('data'));
    }

    public function ask(Request $request)
    {
        $request->validate(['question' => 'required|string|max:1000']);

        $check = $this->repo->checkLimit(Auth::id(), 'student');
        if (!$check['allowed']) {
            return response()->json(['ok' => false, 'message' => $check['message']], 429);
        }

        $result = $this->repo->askStudentHelper($request->input('question'));

        if ($result['ok']) {
            $this->repo->logUsage(Auth::id(), Auth::user()->name, 'student', 'student_question', $request->input('question'));
        }

        return response()->json($result);
    }
}

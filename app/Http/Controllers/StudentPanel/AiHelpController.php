<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LearningEngine\Skill;
use App\Repositories\WebsiteSetup\AiHelperRepository;
use App\Repositories\LearningEngine\LearningEventRepository;

class AiHelpController extends Controller
{
    private $repo;
    private $learningEvents;

    public function __construct(AiHelperRepository $repo, LearningEventRepository $learningEvents)
    {
        $this->repo           = $repo;
        $this->learningEvents = $learningEvents;
    }

    public function index()
    {
        $data['title']         = setting('ai_help_student_page_title') ?: 'AI Study Helper';
        $data['button_text']   = setting('ai_help_student_button_text') ?: 'Ask';
        $data['question_label'] = setting('ai_help_student_question_label') ?: 'What are you stuck on?';
        $data['mascot']        = setting('ai_helper_student_mascot') ? globalAsset(setting('ai_helper_student_mascot')) : null;

        $sessionClassStudent = Auth::user()->student->session_class_student;
        $data['kea_skills']  = $sessionClassStudent
            ? Skill::active()->where('classes_id', $sessionClassStudent->classes_id)->orderBy('sort_order')->get()
            : collect();

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

    public function teachKea(Request $request)
    {
        $request->validate([
            'skill_id'    => 'required|integer',
            'explanation' => 'required|string|max:2000',
        ]);

        $skill = Skill::active()->find($request->input('skill_id'));
        if (!$skill) {
            return response()->json(['ok' => false, 'message' => 'That skill could not be found.'], 422);
        }

        $check = $this->repo->checkLimit(Auth::id(), 'student');
        if (!$check['allowed']) {
            return response()->json(['ok' => false, 'message' => $check['message']], 429);
        }

        $result = $this->repo->teachKea($skill->title, $request->input('explanation'));

        if ($result['ok']) {
            $this->repo->logUsage(Auth::id(), Auth::user()->name, 'student', 'teach_kea', $skill->title . ': ' . $request->input('explanation'));

            $this->learningEvents->record(
                Auth::user()->student->id,
                LearningEventRepository::EVENT_TAUGHT_KEA,
                $skill->id,
                ['understood' => $result['understood']]
            );
        }

        return response()->json($result);
    }

    public function factCheck(Request $request)
    {
        $request->validate(['claim' => 'required|string|max:1000']);

        $check = $this->repo->checkLimit(Auth::id(), 'student');
        if (!$check['allowed']) {
            return response()->json(['ok' => false, 'message' => $check['message']], 429);
        }

        $result = $this->repo->factCheck($request->input('claim'));

        if ($result['ok']) {
            $this->repo->logUsage(Auth::id(), Auth::user()->name, 'student', 'fact_check', $request->input('claim'));
        }

        return response()->json($result);
    }
}

<?php

namespace App\Http\Requests\Examination\Homework;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core fields — always required on update
            'class'     => 'required',
            'section'   => 'required',
            'subject'   => 'required',
            'date'            => 'required|date',
            'submission_date' => 'required|date|after_or_equal:date',
            'marks'           => 'required|numeric|min:0',
            'task_type' => 'required|in:homework,project,activity,game,assignment,quiz',
            'topic'     => 'required|string|max:255',
            'title'     => 'required|string|max:500',

            // Optional - links this task to a specific Class Content lesson.
            'class_content_lesson_id' => 'nullable|integer|exists:class_content_lessons,id',

            // On update, re-uploading a document or CSV is optional.
            // If a new file is provided it replaces the old one; if omitted the existing file is kept.
            'document'            => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,doc,docx|max:10240',
            'bulk_questions_file' => 'nullable|file|mimes:csv,txt|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'bulk_questions_file.mimes'      => 'The quiz file must be a CSV file (.csv).',
            'topic.required'                 => 'Please enter a homework topic heading.',
            'submission_date.required'       => 'Submission date is required.',
            'submission_date.after_or_equal' => 'Submission date must be on or after the assignment date.',
        ];
    }
}

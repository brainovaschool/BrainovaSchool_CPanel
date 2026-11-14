<?php

namespace App\Http\Requests\Examination\Homework;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core fields — always required
            'class'       => 'required',
            'section'     => 'required',
            'subject'     => 'required',
            'date'            => 'required|date',
            'submission_date' => 'required|date|after_or_equal:date',
            'marks'           => 'required|numeric|min:0',
            'task_type'   => 'required|in:homework,project,activity,game,assignment,quiz',
            'topic'       => 'required|string|max:255',
            'title'       => 'required|string|max:500',

            // Optional - links this task to a specific Class Content lesson
            // (the "Link to a class" dropdown), so it shows up on that
            // lesson's Course Details card.
            'class_content_lesson_id' => 'nullable|integer|exists:class_content_lessons,id',

            // Standard document upload — optional for all non-quiz task types.
            // Accepted: images, PDF, Word documents.
            'document'            => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,doc,docx|max:10240',

            // Quiz CSV — required ONLY when task_type is 'quiz'.
            // Must be a CSV or plain-text file. Max 5 MB.
            'bulk_questions_file' => 'required_if:task_type,quiz|nullable|file|mimes:csv,txt|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'bulk_questions_file.required_if' => 'A CSV question file is required when creating a Quiz.',
            'bulk_questions_file.mimes'        => 'The quiz file must be a CSV file (.csv).',
            'topic.required'                   => 'Please enter a homework topic heading.',
            'submission_date.required'         => 'Submission date is required.',
            'submission_date.after_or_equal'   => 'Submission date must be on or after the assignment date.',
        ];
    }
}

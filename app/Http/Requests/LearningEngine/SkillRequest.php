<?php

namespace App\Http\Requests\LearningEngine;

use Illuminate\Foundation\Http\FormRequest;

class SkillRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'subject_id'  => 'required|exists:subjects,id',
            'classes_id'  => 'required|exists:classes,id',
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
            'status'      => 'required|in:0,1',
        ];
    }
}

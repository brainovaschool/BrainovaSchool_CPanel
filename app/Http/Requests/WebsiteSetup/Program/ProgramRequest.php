<?php

namespace App\Http\Requests\WebsiteSetup\Program;

use Illuminate\Foundation\Http\FormRequest;

class ProgramRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'program_category_id' => 'required|exists:program_categories,id',
            'program_focus_id'    => 'nullable|exists:program_focuses,id',
            'classes_id'          => 'nullable|exists:classes,id',
            'section_id'          => 'nullable|exists:sections,id',
            'subject_id'          => 'nullable|exists:subjects,id',
            'title'               => 'required|string|max:191|unique:programs,title,' . $this->route('id'),
            'badge'               => 'nullable|string|max:60',
            'description'         => 'nullable|string|max:1000',
            'age_range'           => 'nullable|string|max:60',
            'grade'               => 'nullable|string|max:60',
            'skill_level'         => 'nullable|integer|in:1,2,3',
            'lessons'             => 'nullable|string|max:60',
            'classes_per_week'    => 'nullable|integer|min:1|max:14',
            'class_days'          => 'nullable|string|max:120',
            'class_time'          => 'nullable|string|max:60',
            'duration'            => 'nullable|string|max:60',
            'enrolled'            => 'nullable|string|max:120',
            'price'               => 'nullable|string|max:60',
            'accent'              => 'nullable|string|max:30',
            'image'               => 'nullable|image|mimes:jpeg,jpg,png,webp',
            'image_url'           => 'nullable|url|max:500',
            'meta_description'    => 'nullable|string|max:300',
            'overview'            => 'nullable|string|max:4000',
            'highlights'          => 'nullable|string|max:2000',
            'format'              => 'nullable|string|max:500',
            'sort_order'          => 'nullable|integer|min:0',
            'status'              => 'required|in:0,1',
        ];
    }
}

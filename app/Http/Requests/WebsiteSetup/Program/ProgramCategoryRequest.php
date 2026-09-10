<?php

namespace App\Http\Requests\WebsiteSetup\Program;

use Illuminate\Foundation\Http\FormRequest;

class ProgramCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'          => 'required|string|max:191|unique:program_categories,name,' . $this->route('id'),
            'tagline'       => 'nullable|string|max:191',
            'hero_title'    => 'nullable|string|max:191',
            'hero_subtitle' => 'nullable|string|max:1000',
            'image'         => 'nullable|image|mimes:jpeg,jpg,png,webp',
            'image_url'     => 'nullable|url|max:500',
            'accent'        => 'nullable|string|max:30',
            'sort_order'    => 'nullable|integer|min:0',
            'status'        => 'required|in:0,1',
        ];
    }
}

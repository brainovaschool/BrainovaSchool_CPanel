<?php

namespace App\Http\Requests\WebsiteSetup\Program;

use Illuminate\Foundation\Http\FormRequest;

class ProgramFocusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'program_category_id' => 'required|exists:program_categories,id',
            'name'                => 'required|string|max:191',
            'description'         => 'nullable|string|max:500',
            'sort_order'          => 'nullable|integer|min:0',
            'status'              => 'required|in:0,1',
        ];
    }
}

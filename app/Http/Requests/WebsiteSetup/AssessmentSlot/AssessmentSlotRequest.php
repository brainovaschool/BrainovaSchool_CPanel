<?php

namespace App\Http\Requests\WebsiteSetup\AssessmentSlot;

use Illuminate\Foundation\Http\FormRequest;

class AssessmentSlotRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'slot_date'  => 'required|date',
            'start_time' => 'required|string|max:20',
            'end_time'   => 'nullable|string|max:20',
            'capacity'   => 'required|integer|min:1|max:500',
            'note'       => 'nullable|string|max:191',
            'status'     => 'required|in:0,1',
        ];
    }
}

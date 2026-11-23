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
            // The form's native time picker always submits 24-hour
            // HH:MM - end_time is no longer asked for at all, the
            // repository sets it automatically to 15 minutes later.
            'start_time' => 'required|date_format:H:i',
            'capacity'   => 'required|integer|min:1|max:500',
            'note'       => 'nullable|string|max:191',
            'status'     => 'required|in:0,1',
        ];
    }
}

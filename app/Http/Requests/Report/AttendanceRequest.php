<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'department' => 'nullable',
            'class'     => 'nullable',
            'section'   => 'nullable',
            'subject'   => 'nullable',
            'student'   => 'nullable',
            'month'     => 'nullable|date_format:Y-m',
        ];
    }
}

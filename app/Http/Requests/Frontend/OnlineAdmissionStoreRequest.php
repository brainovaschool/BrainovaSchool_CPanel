<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OnlineAdmissionStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'student_name'   => 'required|string|max:255',
            'student_age'    => 'required|string|max:50',
            'student_class'  => 'required|string|max:255',
            'parent_name'    => 'required|string|max:255',
            'parent_email'   => 'required|email|max:255',
            'parent_phone'   => 'required|string|max:50',
            'program'        => ['required', Rule::in(config('online_admission.programs'))],
        ];
    }
}

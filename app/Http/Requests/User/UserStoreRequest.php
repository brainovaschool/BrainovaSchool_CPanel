<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
            'staff_id'     => 'required|unique:staff,staff_id',
            'role'         => 'required',
            'designation'  => 'required',
            'department'   => 'required',
            'first_name'   => 'required|max:25',
            'email'        => 'required|unique:users,email',
            'gender'       => 'required',
            'dob'          => 'required',
            'phone'        => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|max:11',
            'status'       => 'required',
            'image'        => 'max:2048',
            'coordinators'   => 'nullable|array',
            'coordinators.*' => 'integer|exists:staff,id',
        ];
    }
}

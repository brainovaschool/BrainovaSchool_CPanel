<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentRole = (string) RoleEnum::STUDENT;

        return [
            'role_id' => 'required',
            'student' => 'required_if:role_id,' . $studentRole . '|exists:users,id',
            'class' => 'required_if:role_id,' . $studentRole . '|exists:classes,id',
            'staff' => 'required_unless:role_id,' . $studentRole . '|exists:staff,id',
            'leave_type' => 'required|exists:leave_types,id',
            'start_date' => 'required',
            'end_date' => 'required',
//            'section' => 'required|exists:sections,id',
            'description' => 'nullable|string|max:255',
            'is_half_day' => 'nullable|boolean',
            'replacement_day' => 'nullable|date',
        ];
    }
}

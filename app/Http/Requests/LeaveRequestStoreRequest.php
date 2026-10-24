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

        $rules = [
            'leave_type' => 'required|exists:leave_types,id',
            'start_date' => 'required',
            'end_date' => 'required',
            'description' => 'nullable|string|max:255',
            'is_half_day' => 'nullable|boolean',
            'replacement_day' => 'nullable|date',
        ];

        // Who the leave is about is only ever picked explicitly when raising
        // it for someone else - raising your own never posts these fields,
        // the server resolves "you" from the session instead.
        if (hasPermission('leave_request_update')) {
            $rules['role_id'] = 'required';
            $rules['student'] = 'required_if:role_id,' . $studentRole . '|exists:users,id';
            $rules['class'] = 'required_if:role_id,' . $studentRole . '|exists:classes,id';
            $rules['staff'] = 'required_unless:role_id,' . $studentRole . '|exists:staff,id';
        }

        return $rules;
    }
}

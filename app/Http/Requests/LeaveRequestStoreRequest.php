<?php

namespace App\Http\Requests;

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
        return [
            'leave_type' => 'required|exists:leave_types,id',
            'start_date' => 'required',
            'end_date' => 'required',
            'description' => 'nullable|string|max:255',
            'is_half_day' => 'nullable|boolean',
            'replacement_day' => 'nullable|date',
        ];
    }
}

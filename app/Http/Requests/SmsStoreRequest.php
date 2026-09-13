<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsStoreRequest extends FormRequest
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
            'twilio_account_sid'  => 'required',
            'twilio_auth_token'   => 'required',
            'twilio_phone_number'   => 'required',

        ];
    }
}

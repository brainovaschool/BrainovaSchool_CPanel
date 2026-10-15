<?php

namespace App\Http\Requests\GeneralSetting;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingStoreRequest extends FormRequest
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
            'application_name' => 'required',
            'footer_text' => 'required',
            'light_logo' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'dark_logo' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'dark_logo' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'favicon' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'app_logo' => 'image|mimes:jpg,png,jpeg|max:2048',
        ];
    }
}

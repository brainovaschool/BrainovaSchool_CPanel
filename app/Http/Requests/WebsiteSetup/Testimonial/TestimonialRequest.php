<?php

namespace App\Http\Requests\WebsiteSetup\Testimonial;

use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type'       => 'required|in:testimonial,review',
            'name'       => 'required|string|max:120',
            'role'       => 'nullable|string|max:150',
            'quote'      => 'required|string|max:2000',
            'rating'     => 'nullable|integer|min:1|max:5',
            'image'      => 'nullable|image|mimes:jpeg,jpg,png,webp',
            'image_url'  => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status'     => 'required|in:0,1',
        ];
    }
}

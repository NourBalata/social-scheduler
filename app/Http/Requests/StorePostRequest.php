<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
  public function rules(): array
{
    return [
        'facebook_page_id' => [
            'required',
            'exists:facebook_pages,id',
            \Illuminate\Validation\Rule::exists('facebook_pages', 'id')->where(function ($query) {
                $query->where('user_id', auth()->id());
            }),
        ],
        'content' => [
            'required',
            'string',
            'max:63206', 
        ],
        'scheduled_at' => [
            'required',
            'date',
            'after:now',
        ],
        'media_url' => 'nullable|url', 
    ];
}

public function messages(): array
{
    return [
        'scheduled_at.after' => 'The scheduled time must be a date in the future.',
        'facebook_page_id.exists' => 'The selected Facebook page is invalid or does not belong to your account.',
    ];
}
}
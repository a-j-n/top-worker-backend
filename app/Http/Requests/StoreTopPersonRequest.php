<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreTopPersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'avatar' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp'])->max('5mb')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'is_approved' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The top person name is required.',
            'name.string' => 'The top person name must be a string.',
            'name.max' => 'The top person name may not be greater than 255 characters.',
            'phone.required' => 'The phone number is required.',
            'phone.string' => 'The phone number must be a string.',
            'phone.max' => 'The phone number may not be greater than 255 characters.',
            'bio.string' => 'The bio must be a string.',
            'category_id.required' => 'The category is required.',
            'category_id.integer' => 'The category must be a valid identifier.',
            'category_id.exists' => 'The selected category does not exist.',
            'is_approved.required' => 'The approval status is required.',
            'is_approved.boolean' => 'The approval status must be true or false.',
        ];
    }
}

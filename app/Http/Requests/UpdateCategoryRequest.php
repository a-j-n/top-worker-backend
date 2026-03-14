<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Str;

class UpdateCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug')) {
            return;
        }

        $this->merge([
            'slug' => Str::slug((string) $this->input('slug')),
        ]);
    }

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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignoreModel($this->route('category')),
            ],
            'icon' => ['sometimes', 'nullable', File::types(['jpg', 'jpeg', 'png', 'svg', 'webp'])->max('2mb')],
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
            'name.required' => 'The category name is required.',
            'name.string' => 'The category name must be a string.',
            'name.max' => 'The category name may not be greater than 255 characters.',
            'name_ar.required' => 'The Arabic category name is required.',
            'name_ar.string' => 'The Arabic category name must be a string.',
            'name_ar.max' => 'The Arabic category name may not be greater than 255 characters.',
            'slug.string' => 'The slug must be a string.',
            'slug.max' => 'The slug may not be greater than 255 characters.',
            'slug.unique' => 'The slug has already been taken.',
        ];
    }
}

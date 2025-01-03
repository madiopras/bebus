<?php

namespace App\Http\Requests\Classes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'has_ac' => 'required|boolean',
            'has_toilet' => 'required|boolean',
            'has_tv' => 'required|boolean',
            'has_music' => 'required|boolean',
            'has_air_mineral' => 'required|boolean',
            'has_wifi' => 'required|boolean',
            'has_snack' => 'required|boolean',
        ];
    }
}

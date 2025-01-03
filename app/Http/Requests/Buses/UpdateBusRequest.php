<?php

namespace App\Http\Requests\Buses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bus_number' => 'required|string|max:255',
            'type_bus' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'bus_name' => 'required|string|max:255',
            'class_id' => 'required|integer',
            'description' => 'required|string|max:255',
            'cargo' => 'integer',
            'is_active' => 'required|boolean',
        ];
    }
}

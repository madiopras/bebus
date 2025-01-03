<?php

namespace App\Http\Requests\SpecialDays;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialDaysRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'description' => 'sometimes|required|string|max:255',
            'price_percentage' => 'sometimes|required|numeric',
            'is_increase' => 'sometimes|required|boolean',
            'is_active' => 'sometimes|required|boolean',
        ];
    }
}

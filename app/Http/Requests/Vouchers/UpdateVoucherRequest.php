<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:255',
            'discount_percentage' => 'sometimes|required|numeric',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'is_active' => 'sometimes|required|boolean',
        ];
    }
}

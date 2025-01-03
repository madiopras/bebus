<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleSeatRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'schedule_id' => 'sometimes|required|integer',
            'seat_id' => 'sometimes|required|integer',
            'is_available' => 'sometimes|required|boolean',
            'description' => 'sometimes|required|string|max:255',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleSeatRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'schedule_id' => 'required|integer',
            'seat_id' => 'required|integer',
            'is_available' => 'required|boolean',
            'description' => 'required|string|max:255',
        ];
    }
}

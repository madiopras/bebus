<?php

namespace App\Http\Requests\Seats;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bus_id' => 'required|integer',
            'seat_number' => 'required|string|max:255',
        ];
    }
}

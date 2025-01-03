<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePassengerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'booking_id' => 'sometimes|required|integer',
            'schedule_seat_id' => 'sometimes|required|integer',
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:255',
        ];
    }
}

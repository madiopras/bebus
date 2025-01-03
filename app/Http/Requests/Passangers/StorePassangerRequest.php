<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePassengerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'booking_id' => 'required|integer',
            'schedule_seat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ];
    }
}

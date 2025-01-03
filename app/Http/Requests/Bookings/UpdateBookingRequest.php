<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'sometimes|required|integer',
            'schedule_id' => 'sometimes|required|integer',
            'booking_date' => 'sometimes|required|date',
            'payment_status' => 'sometimes|required|string|max:255',
            'final_price' => 'sometimes|required|numeric',
            'voucher_id' => 'nullable|integer',
            'specialdays_id' => 'nullable|integer',
            'description' => 'nullable|string|max:255',
        ];
    }
}

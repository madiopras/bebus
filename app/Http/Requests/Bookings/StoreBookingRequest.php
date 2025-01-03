<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|integer',
            'schedule_id' => 'required|integer',
            'booking_date' => 'required|date',
            'payment_status' => 'required|string|max:255',
            'final_price' => 'required|numeric',
            'voucher_id' => 'nullable|integer',
            'specialdays_id' => 'nullable|integer',
            'description' => 'nullable|string|max:255',
        ];
    }
}

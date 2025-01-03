<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'booking_id' => 'required|integer',
            'payment_method' => 'required|string|max:255',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ];
    }
}

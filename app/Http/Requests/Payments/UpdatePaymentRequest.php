<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'booking_id' => 'sometimes|required|integer',
            'payment_method' => 'sometimes|required|string|max:255',
            'payment_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric',
            'description' => 'nullable|string|max:255',
        ];
    }
}

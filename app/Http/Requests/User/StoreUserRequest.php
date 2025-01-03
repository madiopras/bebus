<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:10',
            'role' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ];
    }
}

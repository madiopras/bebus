<?php
// UpdateUserRequest.php
namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->route('user'),
            'password' => 'sometimes|string|min:8',
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:10',
            'role' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = null;
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $userId = $this->route('id') ?: $this->route('user');
        }

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                $userId 
                    ? Rule::unique('users')->ignore($userId)
                    : 'unique:users,email'
            ],
            'password' => $this->isMethod('post') ? 'required|min:8' : 'nullable|min:8',
            'role' => 'required|in:0,1',
            'department_id' => 'nullable|exists:departments,id',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            'category_id' => 'required|exists:categories,id',
            'priority_id' => 'required|exists:priorities,id',

            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',

            'moodle_user_id' => 'nullable|integer',
        ];
    }
}

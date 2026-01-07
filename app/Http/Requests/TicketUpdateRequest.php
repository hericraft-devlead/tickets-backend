<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'priority_id' => 'sometimes|exists:priorities,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'status_id' => 'sometimes|exists:ticket_statuses,id',
            'contact_name' => 'sometimes|string|max:255',
            'contact_email' => 'sometimes|email|max:255',
            'closed_at' => 'nullable|date',
        ];
    }
    
    public function messages()
    {
        return [
            'assigned_user_id.exists' => 'El usuario asignado no existe en el sistema.',
            'status_id.exists' => 'El estado seleccionado no es válido.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'priority_id.exists' => 'La prioridad seleccionada no existe.',
        ];
    }
}
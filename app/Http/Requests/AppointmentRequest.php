<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pet_id' => 'required|exists:pets,id',
            'service_id' => 'required|exists:services,id',
            'employee_id' => 'nullable|exists:employees,id',
            'start_time' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:15',
            'status' => 'required|in:pending,confirmed,cancelled,finished,paid',
            'notes' => 'nullable|string|max:500',
            'payment_type' => 'nullable|string',
        ];
    }
}

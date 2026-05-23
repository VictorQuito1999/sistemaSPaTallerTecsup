<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PetRequest extends FormRequest
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
            'customer_id' => ['required', 'exists:customers,id'],
            'breed_id' => ['required', 'exists:breeds,id'],
            'name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'size' => ['nullable', 'string', 'in:small,medium,large,giant'],
            'allergies' => ['nullable', 'string'],
            'temperament' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'  => 'sometimes|required|string|max:255',
            'last_name'   => 'sometimes|required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:20',
            'gender'      => 'sometimes|required|in:male,female',
            'dob'         => 'nullable|date',
            'address'     => 'nullable|string|max:500',
            'occupation'  => 'nullable|string|max:255',
            'status'      => 'nullable|in:active,inactive,archived',
            'photo'       => 'nullable|image|max:5120',
            'family_id'   => 'nullable|exists:families,id',
        ];
    }
}

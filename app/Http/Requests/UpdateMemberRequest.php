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
            'nic'         => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:20',
            'gender'      => 'sometimes|required|in:male,female',
            'dob'         => 'nullable|date',
            'address'     => 'nullable|string|max:500',
            'address_type'=> 'nullable|string|in:permanent,postal,both',
            'permanent_address' => 'nullable|string|max:500',
            'postal_address'    => 'nullable|string|max:500',
            'is_baptized'       => 'nullable|boolean',
            'baptism_church'    => 'nullable|string|max:255',
            'baptism_partner_name' => 'nullable|string|max:255',
            'baptism_certificate'  => 'nullable|file|max:5120',
            'baptism_date'      => 'nullable|date',
            'membership_date'   => 'nullable|date',
            'occupation'        => 'nullable|string|max:255',
            'marital_status'    => 'nullable|string|in:single,married',
            'marriage_date'     => 'nullable|date',
            'marriage_certificate' => 'nullable|file|max:5120',
            'birth_certificate' => 'nullable|file|max:5120',
            'photo'             => 'nullable|image|max:5120',
            'family_id'         => 'nullable|exists:families,id',
        ];
    }
}

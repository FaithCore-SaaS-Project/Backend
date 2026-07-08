<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'member_no' => $this->member_no,
            'name' => $this->first_name . ' ' . $this->last_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'nic' => $this->nic,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'dob' => $this->dob ? ($this->dob instanceof \DateTimeInterface ? $this->dob->format('Y-m-d') : substr($this->dob, 0, 10)) : null,
            'address' => $this->address,
            'address_type' => $this->address_type,
            'permanent_address' => $this->permanent_address,
            'postal_address' => $this->postal_address,
            'is_baptized' => (bool) $this->is_baptized,
            'baptism_church' => $this->baptism_church,
            'baptism_partner_name' => $this->baptism_partner_name,
            'baptism_certificate' => $this->baptism_certificate,
            'baptism_certificate_url' => $this->baptism_certificate ? asset('storage/' . $this->baptism_certificate) : null,
            'baptism_date' => $this->baptism_date ? ($this->baptism_date instanceof \DateTimeInterface ? $this->baptism_date->format('Y-m-d') : substr($this->baptism_date, 0, 10)) : null,
            'membership_date' => $this->membership_date ? ($this->membership_date instanceof \DateTimeInterface ? $this->membership_date->format('Y-m-d') : substr($this->membership_date, 0, 10)) : null,
            'occupation' => $this->occupation,
            'marital_status' => $this->marital_status,
            'marriage_date' => $this->marriage_date ? ($this->marriage_date instanceof \DateTimeInterface ? $this->marriage_date->format('Y-m-d') : substr($this->marriage_date, 0, 10)) : null,
            'marriage_certificate' => $this->marriage_certificate,
            'marriage_certificate_url' => $this->marriage_certificate ? asset('storage/' . $this->marriage_certificate) : null,
            'birth_certificate' => $this->birth_certificate,
            'birth_certificate_url' => $this->birth_certificate ? asset('storage/' . $this->birth_certificate) : null,
            'status' => $this->status,
            'church_id' => (string) $this->church_id,
            'family_id' => (string) $this->family_id,
            'photo_url' => $this->photo ? asset('storage/' . $this->photo) : null,
            'family' => $this->whenLoaded('family', function () {
                return $this->family->family_name;
            })
        ];
    }
}

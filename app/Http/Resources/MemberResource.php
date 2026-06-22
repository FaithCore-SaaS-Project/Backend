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
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'dob' => $this->dob instanceof \DateTimeInterface ? $this->dob->format('Y-m-d') : substr($this->dob, 0, 10),
            'address' => $this->address,
            'baptism_date' => $this->baptism_date instanceof \DateTimeInterface ? $this->baptism_date->format('Y-m-d') : substr($this->baptism_date, 0, 10),
            'membership_date' => $this->membership_date instanceof \DateTimeInterface ? $this->membership_date->format('Y-m-d') : substr($this->membership_date, 0, 10),
            'occupation' => $this->occupation,
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

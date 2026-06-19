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
            'id' => $this->id,
            'member_no' => $this->member_no,
            'name' => $this->first_name . ' ' . $this->last_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'family' => $this->whenLoaded('family', function () {
                return $this->family->family_name;
            })
        ];
    }
}

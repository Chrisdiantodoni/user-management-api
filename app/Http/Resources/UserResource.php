<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'user_status' => $this->user_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'password_reset_at' => $this->password_reset_at,
        ];
    }
}

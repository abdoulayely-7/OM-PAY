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
            'name' => $this->name,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations conditionnelles
            'compte' => $this->whenLoaded('compte', function () {
                return [
                    'id' => $this->compte->id,
                    'solde' => 0, // À calculer selon les transactions
                    'created_at' => $this->compte->created_at,
                ];
            }),

            'transactions_count' => $this->whenCounted('transactions'),
            'tokens_count' => $this->whenCounted('tokens'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'api_version' => 'v1',
                'resource_type' => 'user',
            ],
        ];
    }
}

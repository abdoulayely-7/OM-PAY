<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'montant' => $this->montant,
            'reference' => $this->reference,
            'date_transaction' => $this->created_at->format('Y-m-d H:i:s'),
            'client' => $this->compte ? [
                'id' => $this->compte->user->id,
                'nom' => $this->compte->user->name,
                'telephone' => $this->compte->user->telephone,
            ] : null,
            'distributeur' => $this->merchant ? [
                'id' => $this->merchant->id,
                'nom' => $this->merchant->name,
            ] : null,
        ];
    }
}

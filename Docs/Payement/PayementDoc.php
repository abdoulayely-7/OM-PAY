<?php

namespace App\Docs\Payement;

/**
 * @OA\Schema(
 *     schema="Marchand",
 *     type="object",
 *     title="Marchand",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique du marchand"),
 *     @OA\Property(property="nom", type="string", description="Nom du marchand"),
 *     @OA\Property(property="code", type="string", description="Code unique du marchand"),
 *     @OA\Property(property="description", type="string", description="Description du marchand"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 *
 * @OA\Schema(
 *     schema="PaiementRequest",
 *     type="object",
 *     title="Requête de paiement",
 *     required={"code_marchand", "montant"},
 *     @OA\Property(property="code_marchand", type="string", description="Code unique du marchand"),
 *     @OA\Property(property="montant", type="number", format="decimal", description="Montant du paiement")
 * )
 */

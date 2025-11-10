<?php

namespace App\Docs\Compte;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique du compte"),
 *     @OA\Property(property="user_id", type="string", format="uuid", description="ID de l'utilisateur"),
 *     @OA\Property(property="balance", type="number", format="decimal", description="Solde du compte"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
 * )
 *
 * @OA\Schema(
 *     schema="SoldeResponse",
 *     type="object",
 *     title="Réponse de solde",
 *     @OA\Property(property="solde", type="number", format="decimal", description="Solde actuel du compte")
 * )
 */

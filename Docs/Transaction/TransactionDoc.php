<?php

namespace App\Docs\Transaction;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique de la transaction"),
 *     @OA\Property(property="compte_id", type="string", format="uuid", description="ID du compte"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "paiement", "transfer_debit", "transfer_credit"}, description="Type de transaction"),
 *     @OA\Property(property="montant", type="number", format="decimal", description="Montant de la transaction"),
 *     @OA\Property(property="reference", type="string", description="Référence unique de la transaction"),
 *     @OA\Property(property="merchant_id", type="string", format="uuid", nullable=true, description="ID du marchand (pour les paiements)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(property="compte", ref="#/components/schemas/Compte"),
 *     @OA\Property(property="merchant", ref="#/components/schemas/Marchand")
 * )
 *
 * @OA\Schema(
 *     schema="TransactionRequest",
 *     type="object",
 *     title="Requête de transaction",
 *     required={"telephone", "montant"},
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone du client"),
 *     @OA\Property(property="montant", type="number", format="decimal", description="Montant de la transaction")
 * )
 *
 * @OA\Schema(
 *     schema="TransferRequest",
 *     type="object",
 *     title="Requête de transfert",
 *     required={"telephone_destinataire", "montant"},
 *     @OA\Property(property="telephone_destinataire", type="string", description="Numéro de téléphone du destinataire"),
 *     @OA\Property(property="montant", type="number", format="decimal", description="Montant du transfert")
 * )
 *
 * @OA\Schema(
 *     schema="TransferResponse",
 *     type="object",
 *     title="Réponse de transfert",
 *     @OA\Property(property="transaction_debit", ref="#/components/schemas/Transaction"),
 *     @OA\Property(property="transaction_credit", ref="#/components/schemas/Transaction")
 * )
 */

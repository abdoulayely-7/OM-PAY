<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaiementRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;


/**
 * @OA\Schema(
 *     schema="PaiementRequest",
 *     type="object",
 *     @OA\Property(property="code_marchand", type="string", example="ORA123", description="Code du marchand"),
 *     @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant du paiement")
 * )
 */
class PayementController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/client/paiement",
     *     tags={"Client"},
     *     summary="Effectuer un paiement",
     *     description="Permet à un client d'effectuer un paiement vers un marchand",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand","montant"},
     *             @OA\Property(property="code_marchand", type="string", example="PAY535", description="Code du marchand (ex: PAY535, FRE960, PAY152, YOB872, SUN353)"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant du paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="uuid"),
     *                 @OA\Property(property="type", type="string", example="paiement"),
     *                 @OA\Property(property="montant", type="number", format="float", example=5000),
     *                 @OA\Property(property="reference", type="string", example="TRF-ABC123"),
     *                 @OA\Property(property="date_transaction", type="string", format="date-time", example="2025-11-11 14:30:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du paiement",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors du paiement: Solde insuffisant.")
     *         )
     *     )
     * )
     */
    public function paiement(PaiementRequest $request)
    {
        try {
            $transaction = $this->transactionService->effectuerPaiement(
                $request->code_marchand,
                $request->montant,
                auth()->id()
            );

            return $this->successResponse(
                new TransactionResource($transaction),
                'Paiement effectué avec succès.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du paiement: ' . $e->getMessage(), 500);
        }
    }
}

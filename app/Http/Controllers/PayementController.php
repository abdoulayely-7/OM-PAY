<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaiementRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Paiements",
 *     description="Endpoints de gestion des paiements"
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
     *     path="/api/client/paiement",
     *     tags={"Paiements"},
     *     summary="Effectuer un paiement",
     *     description="Permet à un client d'effectuer un paiement vers un marchand",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PaiementRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du paiement",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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

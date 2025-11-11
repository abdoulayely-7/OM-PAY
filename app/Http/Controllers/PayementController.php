<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaiementRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;


class PayementController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }


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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Endpoints de gestion des transactions"
 * )
 */
class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Post(
     *     path="/api/distributeur/depot",
     *     tags={"Transactions"},
     *     summary="Effectuer un dépôt",
     *     description="Permet à un distributeur d'effectuer un dépôt sur le compte d'un client",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TransactionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du dépôt",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function depot(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->effectuerDepot(
                $request->telephone,
                $request->montant,
                auth()->id()
            );

            $client = $transaction->compte->user;

            return $this->successResponse(
                new TransactionResource($transaction),
                'Dépôt effectué avec succès.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du dépôt: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/distributeur/retrait",
     *     tags={"Transactions"},
     *     summary="Effectuer un retrait",
     *     description="Permet à un distributeur d'effectuer un retrait sur le compte d'un client",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TransactionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retrait effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="message", type="string", example="Retrait effectué avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du retrait",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function retrait(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->effectuerRetrait(
                $request->telephone,
                $request->montant,
                auth()->id()
            );

            $client = $transaction->compte->user;

            return $this->successResponse(
                new TransactionResource($transaction),
                'Retrait effectué avec succès.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du retrait: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/distributeur/transactions",
     *     tags={"Transactions"},
     *     summary="Lister les transactions du distributeur",
     *     description="Récupère toutes les transactions effectuées par le distributeur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index()
    {
        $transactions = $this->transactionService->getTransactionsDistributeur(auth()->id());

        return $this->successResponse(
            TransactionResource::collection($transactions),
            'Transactions récupérées avec succès.'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/distributeur/transactions/{transaction}",
     *     tags={"Transactions"},
     *     summary="Afficher une transaction spécifique",
     *     description="Récupère les détails d'une transaction spécifique du distributeur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="message", type="string", example="Transaction récupérée avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show($transactionId)
    {
        try {
            $transaction = $this->transactionService->getTransaction($transactionId, auth()->id());

            return $this->successResponse(
                'Transaction récupérée avec succès.',
                new TransactionResource($transaction)
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/client/solde",
     *     tags={"Transactions"},
     *     summary="Récupérer le solde du client",
     *     description="Récupère le solde actuel du compte du client connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SoldeResponse"),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getSolde()
    {
        try {
            $user = auth()->user();
            if (!$user->compte) {
                return $this->errorResponse('Aucun compte associé trouvé.', 404);
            }

            $solde = $user->compte->balance;

            return $this->successResponse(
                ['solde' => $solde],
                'Solde récupéré avec succès.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du solde: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/client/transfert",
     *     tags={"Transactions"},
     *     summary="Effectuer un transfert",
     *     description="Permet à un client d'effectuer un transfert vers un autre numéro",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TransferRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TransferResponse"),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du transfert",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function transfert(TransferRequest $request)
    {
        try {
            $user = auth()->user();
            if (!$user->compte) {
                return $this->errorResponse('Aucun compte associé trouvé.', 404);
            }

            $transfert = $this->transactionService->effectuerTransfert(
                $user->telephone,
                $request->telephone_destinataire,
                $request->montant
            );

            return $this->successResponse(
                [
                    'transaction_debit' => new TransactionResource($transfert['transaction_debit']),
                    'transaction_credit' => new TransactionResource($transfert['transaction_credit']),
                ],
                'Transfert effectué avec succès.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du transfert: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/client/transactions",
     *     tags={"Transactions"},
     *     summary="Récupérer les transactions du client",
     *     description="Récupère l'historique des transactions du client connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getTransactionsClient(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user->compte) {
                return $this->errorResponse('Aucun compte associé trouvé.', 404);
            }

            $perPage = $request->get('per_page', 20);
            $transactions = $user->compte->transactions()
                ->with(['compte.user'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->successResponse(
                'Transactions récupérées avec succès.',
                TransactionResource::collection($transactions)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des transactions: ' . $e->getMessage(), 500);
        }
    }
}

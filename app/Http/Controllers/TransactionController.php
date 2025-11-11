<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="TransactionResource",
 *     type="object",
 *     @OA\Property(property="id", type="string", example="uuid"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfer_debit", "transfer_credit", "paiement"}, example="depot"),
 *     @OA\Property(property="montant", type="number", format="float", example=5000),
 *     @OA\Property(property="reference", type="string", example="TRF-ABC123"),
 *     @OA\Property(property="date_transaction", type="string", format="date-time", example="2025-11-11 14:30:00"),
 *     @OA\Property(property="client", type="object",
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="nom", type="string"),
 *         @OA\Property(property="telephone", type="string")
 *     ),
 *     @OA\Property(property="distributeur", type="object", nullable=true,
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="nom", type="string")
 *     )
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
     *     tags={"Distributeur"},
     *     summary="Effectuer un dépôt",
     *     description="Permet à un distributeur d'effectuer un dépôt sur le compte d'un client",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","montant"},
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone du client"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant du dépôt")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/TransactionResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du dépôt",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors du dépôt: Client non trouvé.")
     *         )
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
     *     tags={"Distributeur"},
     *     summary="Effectuer un retrait",
     *     description="Permet à un distributeur d'effectuer un retrait sur le compte d'un client",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","montant"},
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone du client"),
     *             @OA\Property(property="montant", type="number", format="float", example=2000, description="Montant du retrait")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retrait effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrait effectué avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/TransactionResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du retrait",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors du retrait: Solde insuffisant.")
     *         )
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
     *     tags={"Distributeur"},
     *     summary="Lister les transactions du distributeur",
     *     description="Récupère toutes les transactions effectuées par le distributeur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès."),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransactionResource"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
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
     *     tags={"Distributeur"},
     *     summary="Afficher une transaction spécifique",
     *     description="Récupère les détails d'une transaction spécifique du distributeur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction récupérée avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/TransactionResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Transaction non trouvée.")
     *         )
     *     )
     * )
     */
    public function show($transactionId)
    {
        try {
            $transaction = $this->transactionService->getTransaction($transactionId, auth()->id());

            return $this->successResponse(
                new TransactionResource($transaction),
                'Transaction récupérée avec succès.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/client/solde",
     *     tags={"Client"},
     *     summary="Récupérer le solde du client",
     *     description="Récupère le solde actuel du compte du client connecté (dépôts - retraits/paiements/transferts)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=15000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun compte associé trouvé.")
     *         )
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
     *     tags={"Client"},
     *     summary="Effectuer un transfert",
     *     description="Permet à un client d'effectuer un transfert vers un autre numéro",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone_destinataire","montant"},
     *             @OA\Property(property="telephone_destinataire", type="string", example="772345678", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=3000, description="Montant du transfert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_debit", ref="#/components/schemas/TransactionResource"),
     *                 @OA\Property(property="transaction_credit", ref="#/components/schemas/TransactionResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun compte associé trouvé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors du transfert",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors du transfert: Solde insuffisant.")
     *         )
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
     *     tags={"Client"},
     *     summary="Récupérer les transactions du client",
     *     description="Récupère l'historique des transactions du client connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès."),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransactionResource"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun compte associé trouvé.")
     *         )
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
                TransactionResource::collection($transactions),
                'Transactions récupérées avec succès.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des transactions: ' . $e->getMessage(), 500);
        }
    }
}

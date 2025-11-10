<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="OM Pay API",
 *     version="1.0.0",
 *     description="API de paiement mobile pour OM Pay - Système de paiement et transfert d'argent",
 *     @OA\Contact(
 *         email="contact@ompay.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://om-pay-qdx2.onrender.com",
 *     description="Serveur de production"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur de développement"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class HomeController extends Controller
{
    /**
     * Page d'accueil de l'API
     *
     * @OA\Get(
     *     path="/",
     *     summary="Page d'accueil",
     *     description="Page d'accueil de l'API OM Pay",
     *     @OA\Response(
     *         response=200,
     *         description="Page d'accueil affichée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bienvenue sur l'API OM Pay"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="documentation", type="string", example="https://om-pay-qdx2.onrender.com/api/documentation")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json([
            'message' => 'Bienvenue sur l\'API OM Pay',
            'version' => '1.0.0',
            'documentation' => url('/api/documentation'),
            'endpoints' => [
                'auth' => [
                    'register' => url('/api/v1/auth/register'),
                    'login' => url('/api/v1/auth/login'),
                    'logout' => url('/api/v1/auth/logout'),
                    'refresh' => url('/api/v1/auth/refresh'),
                    'user' => url('/api/user')
                ],
                'client' => [
                    'solde' => url('/api/client/solde'),
                    'transactions' => url('/api/client/transactions'),
                    'transfert' => url('/api/client/transfert'),
                    'paiement' => url('/api/client/paiement')
                ],
                'distributeur' => [
                    'depot' => url('/api/distributeur/depot'),
                    'retrait' => url('/api/distributeur/retrait'),
                    'transactions' => url('/api/distributeur/transactions')
                ]
            ]
        ]);
    }
}

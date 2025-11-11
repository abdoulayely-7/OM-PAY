<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class HomeController extends Controller
{

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

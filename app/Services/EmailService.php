<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.brevo.com/v3/smtp/email';

    
    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key');
    }

    public function sendVerificationEmail(string $email, string $code): bool
    {
        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, [
            'sender' => [
                'name' => 'OM Pay',
                'email' => 'abdoulayely148@gmail.com',
            ],
            'to' => [
                [
                    'email' => $email,
                    'name' => 'Utilisateur',
                ],
            ],
            'subject' => 'Code de vérification OM Pay',
            'htmlContent' => "
                <html>
                <body>
                    <h2>Code de vérification</h2>
                    <p>Votre code de vérification est : <strong>{$code}</strong></p>
                    <p>Utilisez ce code pour vérifier votre compte lors de votre première connexion.</p>
                    <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                </body>
                </html>
            ",
        ]);

        // Log the response for debugging
        Log::info('Brevo API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->successful();
    }
}

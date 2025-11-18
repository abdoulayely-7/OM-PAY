# Guide Complet : Configuration de l'Envoi d'Emails avec Brevo dans Laravel

Ce guide explique étape par étape comment configurer l'envoi d'emails de vérification avec Brevo (anciennement Sendinblue) dans une application Laravel, en utilisant une architecture event-driven.

## Prérequis

- Application Laravel 10+
- Compte Brevo (https://www.brevo.com)
- PHP 8.1+
- Composer

## Étape 1 : Configuration du Compte Brevo

### 1.1 Création du compte
1. Allez sur https://www.brevo.com
2. Créez un compte gratuit
3. Vérifiez votre email

### 1.2 Configuration de l'expéditeur
1. Dans le dashboard Brevo, allez dans "Senders & IP"
2. Cliquez sur "Add a Sender"
3. Ajoutez votre email (ex: `noreply@votre-domaine.com`)
4. Vérifiez l'email en cliquant sur le lien envoyé

### 1.3 Récupération de la clé API
1. Allez dans "SMTP & API"
2. Cliquez sur "API Keys"
3. Créez une nouvelle clé API
4. Copiez la clé (elle commence par `xkeysib-`)

## Étape 2 : Installation des Dépendances

### 2.1 Installation du package OTP
```bash
composer require christian-riesen/otp
```

### 2.2 Installation du package HTTP (si pas déjà présent)
Laravel inclut déjà Guzzle, donc pas besoin d'installation supplémentaire.

## Étape 3 : Configuration Laravel

### 3.1 Configuration des services
Ajoutez dans `config/services.php` :
```php
'brevo' => [
    'api_key' => env('BREVO_API_KEY'),
],
```

### 3.2 Variables d'environnement
Ajoutez dans `.env` :
```env
BREVO_API_KEY=xkeysib-votre-cle-api-ici
```

Et dans `.env.example` :
```env
BREVO_API_KEY=
```

## Étape 4 : Création du Service Email

Créez `app/Services/EmailService.php` :

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.brevo.com/v3/smtp/email';

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
                'email' => 'votre-email-verifie@brevo.com',
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

        Log::info('Brevo API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->successful();
    }
}
```

## Étape 5 : Modification du Modèle User

### 5.1 Migration
Ajoutez dans la migration `create_users_table.php` :
```php
$table->string('verification_code')->nullable();
```

### 5.2 Modèle User
Ajoutez dans `app/Models/User.php` :
```php
protected $fillable = [
    // ... autres champs
    'verification_code',
];
```

## Étape 6 : Architecture Event-Driven

### 6.1 Création de l'Event
Créez `app/Events/CompteCreated.php` :
```php
<?php

namespace App\Events;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, SerializesModels;

    public Compte $compte;
    public User $user;

    public function __construct(Compte $compte, User $user)
    {
        $this->compte = $compte;
        $this->user = $user;
    }
}
```

### 6.2 Création du Listener
Créez `app/Listeners/SendClientNotification.php` :
```php
<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(CompteCreated $event): void
    {
        $code = $event->user->verification_code;
        $this->emailService->sendVerificationEmail($event->user->email, $code);
    }
}
```

### 6.3 Création de l'Observer
Créez `app/Observers/CompteObserver.php` :
```php
<?php

namespace App\Observers;

use App\Events\CompteCreated;
use App\Models\Compte;

class CompteObserver
{
    public function created(Compte $compte): void
    {
        CompteCreated::dispatch($compte, $compte->user);
    }
}
```

### 6.4 Enregistrement dans les Providers

#### AppServiceProvider.php
```php
use App\Models\Compte;
use App\Observers\CompteObserver;

public function boot(): void
{
    // ... autres configurations
    Compte::observe(CompteObserver::class);
}
```

#### EventServiceProvider.php
```php
use App\Events\CompteCreated;
use App\Listeners\SendClientNotification;

protected $listen = [
    // ... autres events
    CompteCreated::class => [
        SendClientNotification::class,
    ],
];
```

## Étape 7 : Modification du Contrôleur

Modifiez `app/Http/Controllers/AuthController.php` :

### 7.1 Imports
```php
use OTPHP\TOTP;
```

### 7.2 Méthode register
```php
public function register(RegisterRequest $request)
{
    $validated = $request->validated();

    try {
        DB::beginTransaction();

        // Générer le code de vérification
        $totp = TOTP::create();
        $code = $totp->now();

        // Créer l'utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'verification_code' => $code,
        ]);

        // Créer un compte
        $compte = Compte::create([
            'user_id' => $user->id,
        ]);

        DB::commit();

        // L'email sera envoyé automatiquement via l'observer

        $user->load('compte');
        return $this->successResponse(
            new UserResource($user),
            'Utilisateur créé avec succès. Un code de vérification a été envoyé à votre email.',
            201
        );

    } catch (\Exception $e) {
        DB::rollBack();
        return $this->errorResponse('Erreur lors de la création du compte.', 500);
    }
}
```

### 7.3 Méthode login
Ajoutez la vérification du code :
```php
if (!$user->email_verified_at) {
    if (!$request->has('code') || $request->code !== $user->verification_code) {
        return response()->json(['error' => 'Code de vérification requis ou invalide'], 403);
    }

    $user->email_verified_at = now();
    $user->verification_code = null;
    $user->save();
}
```

## Étape 8 : Configuration des Queues (Optionnel)

Pour l'envoi asynchrone des emails :

### 8.1 Configuration
Dans `.env` :
```env
QUEUE_CONNECTION=database
```

### 8.2 Création des tables de queue
```bash
php artisan queue:table
php artisan migrate
```

### 8.3 Lancement du worker
```bash
php artisan queue:work
```

## Étape 9 : Test

### 9.1 Migration
```bash
php artisan migrate:fresh
```

### 9.2 Test de l'inscription
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "telephone": "771234567",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 9.3 Vérification des logs
```bash
tail -f storage/logs/laravel.log
```

## Dépannage

### Email non reçu
1. Vérifiez que l'expéditeur est validé dans Brevo
2. Vérifiez les logs Laravel pour les erreurs API
3. Vérifiez le statut de votre compte Brevo (quota d'emails)

### Erreur API Brevo
- Code 401 : Clé API invalide
- Code 400 : Données invalides (vérifiez l'expéditeur)
- Code 429 : Quota dépassé

### Queue non fonctionnelle
- Vérifiez la configuration `QUEUE_CONNECTION`
- Lancez `php artisan queue:work` en arrière-plan

## Sécurité

- Ne stockez jamais la clé API en dur dans le code
- Utilisez des variables d'environnement
- Validez toujours les emails avant envoi
- Surveillez les logs pour détecter les abus

## Optimisations Futures

- Templates d'emails dans Brevo
- Suivi des ouvertures/clicks
- Gestion des bounces
- Intégration avec des services de monitoring

Ce guide couvre une implémentation complète et sécurisée de l'envoi d'emails avec Laravel et Brevo.

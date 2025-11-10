# Guide Complet : Authentification avec Laravel Passport

## Table des Matières
1. [Introduction à OAuth2 et Passport](#introduction-à-oauth2-et-passport)
2. [Installation et Configuration](#installation-et-configuration)
3. [Tables de Base de Données](#tables-de-base-de-données)
4. [Modèles et Relations](#modèles-et-relations)
5. [Configuration](#configuration)
6. [Endpoints d'API](#endpoints-dapi)
7. [Middlewares](#middlewares)
8. [Scopes et Permissions](#scopes-et-permissions)
9. [Problèmes Rencontrés et Solutions](#problèmes-rencontrés-et-solutions)
10. [Tests](#tests)
11. [Bonnes Pratiques](#bonnes-pratiques)

---

## Introduction à OAuth2 et Passport

### Qu'est-ce qu'OAuth2 ?
OAuth2 est un protocole d'autorisation qui permet à une application d'accéder aux ressources d'un utilisateur sur un autre service sans exposer ses identifiants.

### Qu'est-ce que Laravel Passport ?
Laravel Passport est une implémentation OAuth2 complète pour Laravel qui fournit un serveur d'autorisation OAuth2 complet.

### Avantages de Passport
- ✅ Authentification basée sur des tokens JWT
- ✅ Support des refresh tokens
- ✅ Gestion des scopes et permissions
- ✅ Clients multiples
- ✅ API RESTful complète
- ✅ Intégration transparente avec Laravel

---

## Installation et Configuration

### 1. Installation du Package
```bash
composer require laravel/passport
```

### 2. Publication des Migrations
```bash
php artisan passport:install
```
Cette commande :
- Publie les migrations OAuth2
- Génère les clés de chiffrement RSA
- Crée les clients par défaut

### 3. Migration de la Base de Données
```bash
php artisan migrate
```

### 4. Ajout du Trait HasApiTokens
Dans le modèle User :
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens; // Ajoute les méthodes OAuth2

    // ...
}
```

---

## Tables de Base de Données

### 1. Table `users`
**Description** : Stocke les informations des utilisateurs
| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `id` | `string` (UUID) | Clé primaire unique | `a051c2fb-9de1-4f64-b10a-cc843eecab6a` |
| `name` | `string` | Nom complet de l'utilisateur | `Admin User` |
| `email` | `string` | Email unique | `admin@om-pay.com` |
| `telephone` | `string` | Numéro de téléphone | `+221771234567` |
| `role` | `enum` | Rôle de l'utilisateur | `admin`, `distributeur`, `client` |
| `password` | `string` | Mot de passe hashé | `$2y$12$...` |
| `email_verified_at` | `timestamp` | Date de vérification email | `2025-11-09 23:17:16` |
| `remember_token` | `string` | Token de connexion persistante | `SmfvC5alIB` |
| `created_at` | `timestamp` | Date de création | `2025-11-09 23:17:16` |
| `updated_at` | `timestamp` | Date de modification | `2025-11-09 23:17:16` |
| `deleted_at` | `timestamp` | Soft delete | `null` |

### 2. Table `oauth_access_tokens`
**Description** : Stocke les tokens d'accès OAuth2
| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `id` | `string(100)` | Token ID unique | `8b18b10e2779d9ae04e955408e511d765e16cf6c099a150248566259d6bbf6928e37361500a3bac3` |
| `user_id` | `string` | ID de l'utilisateur (UUID) | `a051c2fb-9de1-4f64-b10a-cc843eecab6a` |
| `client_id` | `bigint unsigned` | ID du client OAuth | `1` |
| `name` | `string` | Nom du token | `API Access Token` |
| `scopes` | `text` | Permissions du token (JSON) | `["*"]` |
| `revoked` | `boolean` | Token révoqué ? | `false` |
| `created_at` | `timestamp` | Date de création | `2025-11-09 23:29:16` |
| `updated_at` | `timestamp` | Date de modification | `2025-11-09 23:29:16` |
| `expires_at` | `timestamp` | Date d'expiration | `2026-11-09 23:29:16` |

### 3. Table `oauth_refresh_tokens`
**Description** : Stocke les refresh tokens pour renouveler l'accès
| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `id` | `string(100)` | Refresh token ID | `def502003a8e4c2b8f8e4c2b8f8e4c2b` |
| `access_token_id` | `string(100)` | ID du token d'accès lié | `8b18b10e2779d9ae04e955408e511d765e16cf6c099a150248566259d6bbf6928e37361500a3bac3` |
| `revoked` | `boolean` | Token révoqué ? | `false` |
| `expires_at` | `timestamp` | Date d'expiration | `2025-12-09 23:29:16` |

### 4. Table `oauth_clients`
**Description** : Stocke les clients OAuth2 autorisés
| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `id` | `bigint unsigned` | ID unique du client | `1` |
| `user_id` | `bigint unsigned` | ID de l'utilisateur propriétaire | `null` |
| `name` | `string` | Nom du client | `Laravel Personal Access Client` |
| `secret` | `string` | Secret du client | `86JmvjXTqV503Yb54HXJQbXfJFpNPSNi1DFVCEnB` |
| `provider` | `string` | Provider d'authentification | `users` |
| `redirect` | `string` | URL de redirection | `http://localhost` |
| `personal_access_client` | `boolean` | Client d'accès personnel ? | `true` |
| `password_client` | `boolean` | Client password grant ? | `false` |
| `revoked` | `boolean` | Client révoqué ? | `false` |
| `created_at` | `timestamp` | Date de création | `2025-11-09 23:28:44` |
| `updated_at` | `timestamp` | Date de modification | `2025-11-09 23:28:44` |

### 5. Table `oauth_personal_access_clients`
**Description** : Stocke les clients d'accès personnel
| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `id` | `bigint unsigned` | ID unique | `1` |
| `client_id` | `bigint unsigned` | ID du client lié | `1` |
| `created_at` | `timestamp` | Date de création | `2025-11-09 23:28:44` |
| `updated_at` | `timestamp` | Date de modification | `2025-11-09 23:28:44` |

---

## Modèles et Relations

### Modèle User
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telephone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations Passport
    public function tokens()
    {
        return $this->hasMany(\Laravel\Passport\Token::class);
    }

    public function clients()
    {
        return $this->hasMany(\Laravel\Passport\Client::class);
    }
}
```

---

## Configuration

### 1. Configuration Auth (`config/auth.php`)
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
        'hash' => false,
    ],
],
```

### 2. Configuration Passport (`config/passport.php`)
Fichier généré automatiquement avec :
- Clés RSA pour la signature JWT
- Configuration des grants OAuth2
- Paramètres d'expiration des tokens

### 3. Service Provider (`app/Providers/AppServiceProvider.php`)
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Configuration des scopes
        Passport::tokensCan([
            'view-own-account' => 'Voir son propre compte',
            'view-own-transactions' => 'Voir ses propres transactions',
            'create-transaction' => 'Créer des transactions',
            'view-client-transactions' => 'Voir les transactions des clients',
            'manage-clients' => 'Gérer les clients',
            'view-all-transactions' => 'Voir toutes les transactions',
            'view-all-accounts' => 'Voir tous les comptes',
            'manage-users' => 'Gérer les utilisateurs',
            'manage-merchants' => 'Gérer les marchands',
            'system-admin' => 'Administration système',
        ]);

        // Scopes par défaut
        Passport::setDefaultScope([
            'view-own-account',
            'view-own-transactions',
        ]);
    }
}
```

---

## Endpoints d'API

### Routes API (`routes/api.php`)
```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Routes d'authentification (non protégées)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
});

// Routes protégées
Route::middleware(['auth:api', 'log'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    // Routes par rôle
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Routes admin
    });

    Route::middleware('role:distributeur')->prefix('distributeur')->group(function () {
        // Routes distributeur
    });

    Route::middleware('role:client')->prefix('client')->group(function () {
        // Routes client
    });
});
```

### Contrôleur d'Authentification (`app/Http/Controllers/AuthController.php`)
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Créer access token
        $token = $user->createToken('API Access Token');

        // Créer refresh token
        $refreshToken = $user->createToken('Refresh Token');
        $refreshToken->token->expires_at = now()->addDays(30);
        $refreshToken->token->save();

        // Cookie sécurisé
        $cookie = Cookie::make(
            'access_token',
            $token->accessToken,
            60 * 24 * 7, // 7 jours
            '/',
            null,
            true,  // secure
            true,  // httpOnly
            false,
            'Strict'
        );

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => $token->token->expires_at->diffInSeconds(now()),
            'access_token' => $token->accessToken,
            'refresh_token' => $refreshToken->accessToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ])->withCookie($cookie);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $http = new \GuzzleHttp\Client;

        try {
            $response = $http->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $request->refresh_token,
                    'client_id' => 1,
                    'client_secret' => '',
                    'scope' => '',
                ],
            ]);

            return response()->json(json_decode((string) $response->getBody(), true));
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->revoke();
        });

        $cookie = Cookie::forget('access_token');

        return response()->json([
            'message' => 'Successfully logged out'
        ])->withCookie($cookie);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'role' => $request->user()->role,
        ]);
    }
}
```

---

## Middlewares

### 1. AuthMiddleware (`app/Http/Middleware/AuthMiddleware.php`)
**Rôle** : Vérifie l'authentification de l'utilisateur
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        $request->merge(['authenticated_user' => Auth::guard('api')->user()]);

        return $next($request);
    }
}
```

### 2. RoleMiddleware (`app/Http/Middleware/RoleMiddleware.php`)
**Rôle** : Vérifie les permissions selon le rôle
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        if ($user->role !== $role) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions. Required role: ' . $role
            ], 403);
        }

        $permissions = $this->getPermissionsForRole($user->role);
        $request->merge(['user_permissions' => $permissions]);

        return $next($request);
    }

    private function getPermissionsForRole(string $role): array
    {
        $permissions = [
            'client' => [
                'view_own_transactions',
                'view_own_account',
                'create_transaction',
            ],
            'distributeur' => [
                'view_own_transactions',
                'view_own_account',
                'create_transaction',
                'view_client_transactions',
                'manage_clients',
            ],
            'admin' => [
                'view_all_transactions',
                'view_all_accounts',
                'manage_users',
                'manage_merchants',
                'system_admin',
            ],
        ];

        return $permissions[$role] ?? [];
    }
}
```

### 3. LoggingMiddleware (`app/Http/Middleware/LoggingMiddleware.php`)
**Rôle** : Log toutes les opérations sur les ressources
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $user = Auth::guard('api')->user();
        $userId = $user ? $user->id : 'guest';

        $logData = [
            'user_id' => $userId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'request_size' => strlen($request->getContent()),
            'response_size' => strlen($response->getContent()),
        ];

        if ($response->getStatusCode() >= 500) {
            Log::error('API Request Error', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('API Request Warning', $logData);
        } elseif ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            Log::info('API Request Modification', $logData);
        } else {
            Log::debug('API Request', $logData);
        }

        $response->headers->set('X-Request-ID', uniqid());
        $response->headers->set('X-Response-Time', $duration . 'ms');

        return $response;
    }
}
```

### Enregistrement des Middlewares (`app/Http/Kernel.php`)
```php
protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
    'auth.api' => \App\Http\Middleware\AuthMiddleware::class,
    'role' => \App\Http\Middleware\RoleMiddleware::class,
    'log' => \App\Http\Middleware\LoggingMiddleware::class,
    // ...
];
```

---

## Scopes et Permissions

### Définition des Scopes
```php
Passport::tokensCan([
    'view-own-account' => 'Voir son propre compte',
    'view-own-transactions' => 'Voir ses propres transactions',
    'create-transaction' => 'Créer des transactions',
    'view-client-transactions' => 'Voir les transactions des clients',
    'manage-clients' => 'Gérer les clients',
    'view-all-transactions' => 'Voir toutes les transactions',
    'view-all-accounts' => 'Voir tous les comptes',
    'manage-users' => 'Gérer les utilisateurs',
    'manage-merchants' => 'Gérer les marchands',
    'system-admin' => 'Administration système',
]);
```

### Attribution des Scopes
```php
// Lors de la création du token
$token = $user->createToken('API Access Token', [
    'view-own-account',
    'view-own-transactions',
    'create-transaction'
]);
```

### Vérification des Scopes dans les Routes
```php
Route::middleware(['auth:api', 'scope:view-own-account'])->get('/account', function () {
    // Route accessible seulement avec le scope view-own-account
});
```

---

## Problèmes Rencontrés et Solutions

### 1. **Problème : Ordre des Migrations Incorrect**
**Symptôme** : Erreur de clé étrangère lors des migrations
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "comptes" does not exist
```

**Cause** : Les migrations étaient créées dans le mauvais ordre chronologique

**Solution** : Renommer les fichiers de migration pour respecter l'ordre
```bash
# Avant
2025_11_09_190249_create_comptes_table.php
2025_11_09_192930_create_marchands_table.php
2025_11_09_164450_create_transactions_table.php

# Après
2025_11_09_164448_create_marchands_table.php
2025_11_09_164449_create_comptes_table.php
2025_11_09_164451_create_transactions_table.php
```

### 2. **Problème : Type de Données Incompatible pour user_id**
**Symptôme** : Erreur lors de l'insertion dans oauth_access_tokens
```
SQLSTATE[42804]: Datatype mismatch: 7 ERROR: foreign key constraint "comptes_user_id_foreign" cannot be implemented
```

**Cause** : La table oauth_access_tokens attendait un BIGINT mais nos utilisateurs utilisent des UUID

**Solution** : Modifier la migration Passport
```php
// Dans database/migrations/2016_06_01_000002_create_oauth_access_tokens_table.php
$table->string('user_id')->nullable()->index(); // Au lieu de unsignedBigInteger
```

### 3. **Problème : Personal Access Client Manquant**
**Symptôme** : Exception lors de l'utilisation des tokens
```
RuntimeException: Personal access client not found
```

**Cause** : Le client d'accès personnel n'était pas créé

**Solution** : Créer le client manuellement
```bash
php artisan passport:client --personal --name="Laravel Personal Access Client"
```

### 4. **Problème : Refresh Token Null**
**Symptôme** : Le refresh_token était null dans la réponse de login

**Cause** : Passport ne crée pas automatiquement de refresh token séparé

**Solution** : Créer manuellement deux tokens
```php
// Access token
$token = $user->createToken('API Access Token');

// Refresh token séparé
$refreshToken = $user->createToken('Refresh Token');
$refreshToken->token->expires_at = now()->addDays(30);
$refreshToken->token->save();
```

### 5. **Problème : Trait HasUuids Manquant**
**Symptôme** : Erreur lors du seeding
```
Trait "App\Models\HasUuids" not found
```

**Cause** : Import manquant dans les modèles

**Solution** : Ajouter l'import correct
```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Marchand extends Model
{
    use HasFactory, HasUuids;
    // ...
}
```

---

## Tests

### Tests d'Authentification (`tests/Feature/AuthTest.php`)
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected $passwordClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordClient = \Laravel\Passport\Client::factory()->create([
            'password_client' => true,
            'revoked' => false,
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token_type',
                    'expires_in',
                    'access_token',
                    'refresh_token',
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
    }
}
```

### Exécution des Tests
```bash
php artisan test --filter AuthTest
```

---

## Bonnes Pratiques

### 1. **Sécurité**
- ✅ Utiliser HTTPS en production
- ✅ Configurer correctement les cookies (secure, httpOnly, sameSite)
- ✅ Valider toutes les entrées utilisateur
- ✅ Utiliser des scopes appropriés
- ✅ Révoquer les tokens compromis

### 2. **Performance**
- ✅ Utiliser des indexes appropriés sur user_id
- ✅ Configurer la durée de vie des tokens selon les besoins
- ✅ Nettoyer régulièrement les tokens expirés

### 3. **Maintenance**
- ✅ Documenter tous les endpoints et scopes
- ✅ Créer des tests complets
- ✅ Monitorer les logs d'API
- ✅ Mettre à jour régulièrement Passport

### 4. **Architecture**
- ✅ Séparer les responsabilités (AuthController, Middlewares)
- ✅ Utiliser des repositories pour la logique métier
- ✅ Implémenter le versioning d'API
- ✅ Documenter avec OpenAPI/Swagger

---

## Commandes Utiles

```bash
# Installation
composer require laravel/passport
php artisan passport:install
php artisan migrate

# Gestion des clients
php artisan passport:client --personal
php artisan passport:client --password

# Tests
php artisan test --filter AuthTest

# Debugging
php artisan tinker
# Puis : User::first()->tokens
```

---

## Conclusion

Laravel Passport offre une solution complète et sécurisée pour l'authentification OAuth2. En suivant ce guide, vous pouvez implémenter un système d'authentification robuste avec :

- ✅ Authentification basée sur des tokens JWT
- ✅ Gestion des rôles et permissions
- ✅ Refresh tokens pour la sécurité
- ✅ Logging complet des opérations
- ✅ Tests automatisés
- ✅ Gestion des erreurs et edge cases

N'oubliez pas de toujours tester votre implémentation et de suivre les bonnes pratiques de sécurité ! 🔐

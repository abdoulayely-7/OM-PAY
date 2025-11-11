<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Compte;
use App\Models\User;
use App\Services\EmailService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;

/**
 * @OA\Info(
 *     title="OM Pay API",
 *     version="1.0.0",
 *     description="API pour l'application OM Pay<br><br>**Comptes de test :**<br>- **Admin :** admin@om-pay.com / password<br>- **Distributeur :** distributeur@om-pay.com / +221772345678 / password<br>- **Clients :** Générés automatiquement avec mot de passe 'password'<br><br>**Codes marchands :** PAY535, FRE960, PAY152, YOB872, SUN353"
 * )
 *
 * * @OA\Server(
 *     url="https://om-pay-qdx2.onrender.com",
 *     description="Serveur de production"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Serveur de développement"
 * )
 *

 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 *
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     @OA\Property(property="id", type="string", example="uuid"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="role", type="string", enum={"client", "distributeur", "admin"}, example="client"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="compte", ref="#/components/schemas/CompteResource")
 * )
 *
 * @OA\Schema(
 *     schema="CompteResource",
 *     type="object",
 *     @OA\Property(property="id", type="string", example="uuid"),
 *     @OA\Property(property="solde", type="number", format="float", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Inscrire un nouvel utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","telephone","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="771234567"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès. Un code de vérification a été envoyé à votre email."),
     *             @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        Log::info('Register method called');
        $validated = $request->validated();


        try {
            DB::beginTransaction();

            // Générer le code de vérification
            $code = random_int(100000, 999999);

            // Créer l'utilisateur
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'],
                'password' => Hash::make($validated['password']),
                'role' => 'client', // Par défaut, tous les nouveaux utilisateurs sont clients
                'verification_code' => $code,
            ]);

            // Créer un compte pour l'utilisateur
            $compte = Compte::create([
                'user_id' => $user->id,
            ]);

            DB::commit();

            // Envoyer l'email de vérification
            $emailService = app(EmailService::class);
            $emailService->sendVerificationEmail($user->email, $code);

            // Charger les relations pour la réponse
            $user->load('compte');

            return $this->successResponse(
                new UserResource($user),
                'Utilisateur créé avec succès. Un code de vérification a été envoyé à votre email.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Erreur lors de la création du compte utilisateur.',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Se connecter",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"identifier","password"},
     *             @OA\Property(property="identifier", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="code", type="string", example="123456", description="Requis pour la première connexion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Code de vérification requis ou invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Code de vérification requis ou invalide")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $identifier = $validated['identifier'];

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $identifier)->first();
        } else {
            // Normaliser le numéro de téléphone
            $telephone = $identifier;
            $telephone = preg_replace('/\s+/', '', $telephone);
            if (!str_starts_with($telephone, '+221')) {
                if (str_starts_with($telephone, '+')) {
                    $telephone = '+221' . substr($telephone, 1);
                } else {
                    $telephone = '+221' . $telephone;
                }
            }
            $user = User::where('telephone', $telephone)->first();
        }

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Vérifier si l'email est vérifié
        if (!$user->email_verified_at) {
            if (!$request->has('code') || $request->code !== $user->verification_code) {
                return response()->json(['error' => 'Code de vérification requis ou invalide'], 403);
            }

            // Marquer comme vérifié
            $user->email_verified_at = now();
            $user->verification_code = null;
            $user->save();
        }

        // Créer le token d'accès avec Passport
        $token = $user->createToken('API Access Token');

        // Générer un refresh token séparé
        $refreshToken = $user->createToken('Refresh Token');
        $refreshToken->token->expires_at = now()->addDays(30); // Refresh token valide 30 jours
        $refreshToken->token->save();

        // Stocker le token dans un cookie sécurisé
        $cookie = Cookie::make(
            'access_token',
            $token->accessToken,
            60 * 24 * 7, // 7 jours
            '/',
            null,
            true, // secure
            true, // httpOnly
            false,
            'Strict'
        );

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => $token->token->expires_at->diffInSeconds(now()),
            'access_token' => $token->accessToken,
            'refresh_token' => $refreshToken->accessToken,
            // 'user' => [
            //     'id' => $user->id,
            //     'name' => $user->name,
            //     'email' => $user->email,
            //     'role' => $user->role,
            // ],
        ])->withCookie($cookie);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Rafraîchir le token d'accès",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid refresh token")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // Utiliser Passport pour rafraîchir le token
        $http = new \GuzzleHttp\Client;

        try {
            $response = $http->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $request->refresh_token,
                    'client_id' => 9, // Personal access client
                    'client_secret' => '', // Pas de secret pour personal access
                    'scope' => '',
                ],
            ]);

            return response()->json(json_decode((string) $response->getBody(), true));
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Se déconnecter",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Révoquer tous les tokens de l'utilisateur
        $request->user()->tokens->each(function ($token) {
            $token->revoke();
        });

        // Supprimer le cookie
        $cookie = Cookie::forget('access_token');

        return response()->json([
            'message' => 'Successfully logged out'
        ])->withCookie($cookie);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Obtenir les informations de l'utilisateur connecté",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informations utilisateur récupérées avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *         )
     *     )
     * )
     */
    public function user(Request $request)
    {
        return $this->successResponse(
            new UserResource($request->user()->load('compte')),
            'Informations utilisateur récupérées avec succès.'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Compte;
use App\Models\User;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Otp\Otp;

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
 *     @OA\Property(property="updated_at", type="string", format="date-time")
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

        $validated = $request->validated();


        try {
            DB::beginTransaction();

            // Générer le code de vérification avec OTP
            $otp = new Otp();
            $code = $otp->totp('JBSWY3DPEHPK3PXP'); // Secret fixe pour génération simple

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

            // L'email sera envoyé automatiquement via l'observer et l'événement

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
     *             @OA\Property(property="refresh_token", type="string")
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

        // Créer le token d'accès avec Sanctum
        $token = $user->createToken('API Access Token');

        // Stocker le token dans un cookie sécurisé
        $cookie = Cookie::make(
            'access_token',
            $token->plainTextToken,
            60 * 24 * 7, // 7 jours
            '/',
            null,
            true, // secure
            true, // httpOnly
            false,
            'Strict'
        );

        // Réponse avec le token
        $responseData = [
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
        ];

        return response()->json($responseData)->withCookie($cookie);
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
        $request->user()->tokens()->delete();

        // Supprimer le cookie
        $cookie = Cookie::forget('access_token');

        return response()->json([
            'message' => 'Successfully logged out'
        ])->withCookie($cookie);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/dashboard",
     *     summary="Obtenir le tableau de bord du client connecté",
     *     tags={"Client"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations client complètes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informations utilisateur récupérées avec succès."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="role", type="string", enum={"client"}, example="client")
     *                 ),
     *                 @OA\Property(property="balance", type="number", format="float", example=1500.50, description="Solde du compte"),
     *                 @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/TransactionResource"), description="10 dernières transactions"),
     *                 @OA\Property(property="qr_code_path", type="string", example="qrcodes/uuid.png", description="Chemin du QR code du compte")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Réservé aux clients",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès refusé")
     *         )
     *     )
     * )
     */
    public function dashboard(Request $request)
    {
        $user = $request->user()->load('compte');

        $responseData = [
            'user' => new UserProfileResource($user),
        ];

        // Pour les clients, ajouter solde, transactions récentes et QR code
        if ($user->role === 'client') {
            $balance = $user->compte->balance ?? 0;
            $transactionService = app(TransactionService::class);
            $transactions = $transactionService->getTransactionsClient($user->id, 10); // Plus de transactions

            $responseData['solde'] = $balance;
            $responseData['transactions'] = TransactionResource::collection($transactions);
            $responseData['qr_code_path'] = $user->compte->qr_code_path;
        }

        return $this->successResponse(
            $responseData,
            'Informations utilisateur récupérées avec succès.'
        );
    }
}

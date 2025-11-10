<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Compte;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints d'authentification"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur et retourne un token d'accès",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string', // Utilise seulement le téléphone
            'password' => 'required|string',
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ])->withCookie($cookie);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Authentification"},
     *     summary="Rafraîchir le token d'accès",
     *     description="Utilise un refresh token pour obtenir un nouveau token d'accès",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="refresh_token", type="string", description="Refresh token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
                    'client_id' => 1, // Personal access client
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
     *     tags={"Authentification"},
     *     summary="Déconnexion utilisateur",
     *     description="Révoque tous les tokens d'accès de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Authentification"},
     *     summary="Inscription utilisateur",
     *     description="Crée un nouveau compte utilisateur avec un compte associé",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès. Vous pouvez maintenant vous connecter.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreurs de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function register(Request $request)
    {
        // Valider manuellement pour avoir le contrôle sur les erreurs
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'string', 'regex:/^(?:\+221)?\s?(77|70|76|75|78)\s?\d{3}\s?\d{2}\s?\d{2}$/', 'unique:users,telephone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'email.required' => 'L\'email est obligatoire.',
            'email.string' => 'L\'email doit être une chaîne de caractères.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.max' => 'L\'email ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cet email est déjà utilisé.',

            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221) 77/70/76/75/78 XXX XX XX.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',

            'password.required' => 'Le mot de passe est obligatoire.',
            'password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',

            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire.',
        ]);

        // Nettoyer le numéro de téléphone
        if ($request->has('telephone')) {
            $telephone = $request->telephone;

            // Supprimer tous les espaces
            $telephone = preg_replace('/\s+/', '', $telephone);

            // Ajouter +221 si absent
            if (!str_starts_with($telephone, '+221')) {
                if (str_starts_with($telephone, '+')) {
                    // Si commence par + mais pas +221, remplacer
                    $telephone = '+221' . substr($telephone, 1);
                } else {
                    // Ajouter +221
                    $telephone = '+221' . $telephone;
                }
            }

            $request->merge(['telephone' => $telephone]);
        }

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password),
                'role' => 'client', // Par défaut, tous les nouveaux utilisateurs sont clients
                'email_verified_at' => now(), // Auto-vérification pour simplifier
            ]);

            // Créer un compte pour l'utilisateur
            $compte = Compte::create([
                'user_id' => $user->id,
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $user->load('compte');

            return $this->successResponse(
                new UserResource($user),
                'Utilisateur créé avec succès. Vous pouvez maintenant vous connecter.',
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
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Authentification"},
     *     summary="Informations utilisateur",
     *     description="Récupère les informations de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string", example="Informations utilisateur récupérées avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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

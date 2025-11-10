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

class AuthController extends Controller
{
    use ApiResponseTrait;
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // Peut être email ou téléphone
            'password' => 'required|string',
        ]);

        // Déterminer si c'est un email ou un numéro de téléphone
        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

        $user = User::where($field, $request->identifier)->first();

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

    public function user(Request $request)
    {
        return $this->successResponse(
            new UserResource($request->user()->load('compte')),
            'Informations utilisateur récupérées avec succès.'
        );
    }
}

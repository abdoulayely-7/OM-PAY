<?php

namespace App\Docs\Auth;

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

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="Utilisateur",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique de l'utilisateur"),
 *     @OA\Property(property="nom", type="string", description="Nom de l'utilisateur"),
 *     @OA\Property(property="prenom", type="string", description="Prénom de l'utilisateur"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="role", type="string", enum={"client", "distributeur", "admin"}, description="Rôle de l'utilisateur"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     title="Requête de connexion",
 *     required={"telephone", "password"},
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="password", type="string", description="Mot de passe")
 * )
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     title="Requête d'inscription",
 *     required={"nom", "prenom", "telephone", "password", "role"},
 *     @OA\Property(property="nom", type="string", description="Nom"),
 *     @OA\Property(property="prenom", type="string", description="Prénom"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="password", type="string", description="Mot de passe"),
 *     @OA\Property(property="role", type="string", enum={"client", "distributeur"}, description="Rôle")
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     type="object",
 *     title="Réponse d'authentification",
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="access_token", type="string", description="Token d'accès"),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", description="Durée de validité en secondes")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Réponse d'erreur",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", description="Message d'erreur"),
 *     @OA\Property(property="errors", type="object", description="Détails des erreurs")
 * )
 */

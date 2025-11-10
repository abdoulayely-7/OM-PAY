<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
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

        // Ajouter les permissions de l'utilisateur à la requête
        $permissions = $this->getPermissionsForRole($user->role);
        $request->merge(['user_permissions' => $permissions]);

        return $next($request);
    }

    /**
     * Récupère les permissions pour un rôle donné
     */
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

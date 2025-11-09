<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuration des scopes Passport
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

        // Configuration des claims personnalisés
        Passport::setDefaultScope([
            'view-own-account',
            'view-own-transactions',
        ]);
    }
}

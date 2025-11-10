<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Compte;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Effectuer un dépôt pour un client
     */
    public function effectuerDepot(string $telephone, float $montant, string $distributeurId): Transaction
    {
        try {
            DB::beginTransaction();

            // Normaliser le numéro de téléphone
            $telephone = $this->normaliserTelephone($telephone);

            // Trouver l'utilisateur par numéro de téléphone
            $client = User::where('telephone', $telephone)->first();

            if (!$client) {
                throw new \Exception('Client non trouvé avec ce numéro de téléphone.');
            }

            // Vérifier que l'utilisateur a un compte
            $compte = $client->compte;
            if (!$compte) {
                throw new \Exception('Le client n\'a pas de compte associé.');
            }

            // Créer la transaction de dépôt
            $transaction = Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'depot',
                'montant' => $montant,
                'reference' => $this->genererReference(),
                'merchant_id' => null, // Les distributeurs ne sont pas dans la table marchands
            ]);

            DB::commit();

            return $transaction->load(['compte.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Effectuer un retrait pour un client
     */
    public function effectuerRetrait(string $telephone, float $montant, string $distributeurId): Transaction
    {
        try {
            DB::beginTransaction();

            // Normaliser le numéro de téléphone
            $telephone = $this->normaliserTelephone($telephone);

            // Trouver l'utilisateur par numéro de téléphone
            $client = User::where('telephone', $telephone)->first();

            if (!$client) {
                throw new \Exception('Client non trouvé avec ce numéro de téléphone.');
            }

            // Vérifier que l'utilisateur a un compte
            $compte = $client->compte;
            if (!$compte) {
                throw new \Exception('Le client n\'a pas de compte associé.');
            }

            // Vérifier le solde du compte avant retrait
            $solde = $compte->balance;
            if ($solde < $montant) {
                throw new \Exception('Solde insuffisant pour effectuer ce retrait.');
            }

            // Créer la transaction de retrait
            $transaction = Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'retrait',
                'montant' => $montant,
                'reference' => $this->genererReference(),
                'merchant_id' => null, // Les distributeurs ne sont pas dans la table marchands
            ]);

            DB::commit();

            return $transaction->load(['compte.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupérer les transactions d'un distributeur
     */
    public function getTransactionsDistributeur(string $distributeurId, int $perPage = 20)
    {
        // Pour l'instant, retourner toutes les transactions de type 'depot'
        // TODO: Ajouter un champ pour lier les transactions aux distributeurs
        return Transaction::where('type', 'depot')
            ->with(['compte.user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Récupérer une transaction spécifique
     */
    public function getTransaction(string $transactionId, string $distributeurId): Transaction
    {
        $transaction = Transaction::findOrFail($transactionId);

        // Pour l'instant, vérifier seulement que c'est une transaction de type 'depot'
        // TODO: Ajouter une vérification pour l'appartenance au distributeur
        if ($transaction->type !== 'depot') {
            throw new \Exception('Transaction non trouvée.');
        }

        return $transaction->load(['compte.user']);
    }

    /**
     * Normaliser un numéro de téléphone
     */
    private function normaliserTelephone(string $telephone): string
    {
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

        return $telephone;
    }

    /**
     * Effectuer un transfert vers un autre numéro
     */
    public function effectuerTransfert(string $telephoneExpediteur, string $telephoneDestinataire, float $montant): array
    {
        try {
            DB::beginTransaction();

            // Normaliser les numéros de téléphone
            $telephoneExpediteur = $this->normaliserTelephone($telephoneExpediteur);
            $telephoneDestinataire = $this->normaliserTelephone($telephoneDestinataire);

            // Trouver l'expéditeur
            $expediteur = User::where('telephone', $telephoneExpediteur)->first();
            if (!$expediteur) {
                throw new \Exception('Expéditeur non trouvé.');
            }

            // Vérifier que l'expéditeur a un compte
            $compteExpediteur = $expediteur->compte;
            if (!$compteExpediteur) {
                throw new \Exception('L\'expéditeur n\'a pas de compte associé.');
            }

            // Trouver le destinataire
            $destinataire = User::where('telephone', $telephoneDestinataire)->first();
            if (!$destinataire) {
                throw new \Exception('Destinataire non trouvé.');
            }

            // Vérifier que le destinataire a un compte
            $compteDestinataire = $destinataire->compte;
            if (!$compteDestinataire) {
                throw new \Exception('Le destinataire n\'a pas de compte associé.');
            }

            // Vérifier que l'expéditeur n'est pas le destinataire
            if ($compteExpediteur->id === $compteDestinataire->id) {
                throw new \Exception('Impossible de transférer vers son propre compte.');
            }

            // Vérifier le solde de l'expéditeur
            $soldeExpediteur = $compteExpediteur->balance;
            if ($soldeExpediteur < $montant) {
                throw new \Exception('Solde insuffisant pour effectuer ce transfert.');
            }

            // Créer la transaction de débit pour l'expéditeur
            $transactionDebit = Transaction::create([
                'compte_id' => $compteExpediteur->id,
                'type' => 'transfer_debit',
                'montant' => $montant,
                'reference' => $this->genererReference(),
                'merchant_id' => null,
            ]);

            // Créer la transaction de crédit pour le destinataire
            $transactionCredit = Transaction::create([
                'compte_id' => $compteDestinataire->id,
                'type' => 'transfer_credit',
                'montant' => $montant,
                'reference' => $this->genererReference(),
                'merchant_id' => null,
            ]);

            DB::commit();

            return [
                'transaction_debit' => $transactionDebit->load(['compte.user']),
                'transaction_credit' => $transactionCredit->load(['compte.user']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Effectuer un paiement vers un marchand
     */
    public function effectuerPaiement(string $codeMarchand, float $montant, string $clientId): Transaction
    {
        try {
            DB::beginTransaction();

            // Trouver le marchand par code
            $marchand = Marchand::where('code', $codeMarchand)->first();
            if (!$marchand) {
                throw new \Exception('Marchand non trouvé avec ce code.');
            }

            // Trouver l'utilisateur client
            $client = User::findOrFail($clientId);
            if (!$client->compte) {
                throw new \Exception('Le client n\'a pas de compte associé.');
            }

            // Vérifier le solde du client
            $solde = $client->compte->balance;
            if ($solde < $montant) {
                throw new \Exception('Solde insuffisant pour effectuer ce paiement.');
            }

            // Créer la transaction de paiement
            $transaction = Transaction::create([
                'compte_id' => $client->compte->id,
                'type' => 'paiement',
                'montant' => $montant,
                'reference' => $this->genererReference(),
                'merchant_id' => $marchand->id,
            ]);

            DB::commit();

            return $transaction->load(['compte.user', 'merchant']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Générer une référence unique pour la transaction
     */
    private function genererReference(): string
    {
        do {
            $reference = 'TRF-' . strtoupper(Str::random(10));
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }
}

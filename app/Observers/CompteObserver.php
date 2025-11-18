<?php

namespace App\Observers;

use App\Events\CompteCreated;
use App\Models\Compte;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class CompteObserver
{
    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        // Générer le QR code
        $qrCodeData = json_encode([
            'account_id' => $compte->id,
            'type' => 'payment_account',
            'user_id' => $compte->user_id
        ]);

        $fileName = 'qrcodes/' . $compte->id . '.png';
        $qrCodeImage = QrCode::format('png')->size(300)->generate($qrCodeData);

        // Sauvegarder dans storage
        Storage::put($fileName, $qrCodeImage);

        // Mettre à jour le chemin dans la DB
        $compte->qr_code_path = $fileName;
        $compte->save();

        // Déclencher l'événement pour l'email
        CompteCreated::dispatch($compte, $compte->user);
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        //
    }
}

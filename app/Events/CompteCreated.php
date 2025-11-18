<?php

namespace App\Events;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, SerializesModels;

    public Compte $compte;
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte, User $user)
    {
        $this->compte = $compte;
        $this->user = $user;
    }
}

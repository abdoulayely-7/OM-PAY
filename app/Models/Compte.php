<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'user_id',
        'qr_code_path',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }

    /**
     * Calculer le solde dynamique basé sur les transactions
     */
    public function getBalanceAttribute()
    {
        $deposits = $this->transactions()->whereIn('type', ['depot', 'transfer_credit'])->sum('montant');
        $withdrawals = $this->transactions()->whereIn('type', ['retrait', 'transfer_debit', 'paiement'])->sum('montant');

        return $deposits - $withdrawals;
    }
}

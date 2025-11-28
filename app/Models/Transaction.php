<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'compte_id',
        'user_id',
        'type',
        'montant',
        'reference',
        'merchant_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}

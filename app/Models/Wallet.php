<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['patient_id', 'amount', 'is_system'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
    // app/Models/Wallet.php

public function transactions()
{
    return $this->hasMany(WalletTransaction::class);
}

}

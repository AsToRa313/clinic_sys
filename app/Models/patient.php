<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blood_type',
    
    ];

    // Phase 1 Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function appointment ()
    {
        return $this->hasMany(Appointment::class);
    }
    public function wallet()
{
    return $this->hasOne(Wallet::class);
}
public function rating()
{
    return $this->hasMany(Rating::class);
}
public function doctorNotes()
{
    return $this->hasMany(DoctorNote::class);
}

}


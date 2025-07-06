<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clinic_id',
        'specialty',
        'bio',
    ];

    // Phase 1 Relationships only

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
    public function schedule()
    {
        return $this->hasMany(related: Schedule::class);
    }
public function appointment ()
    {
        return $this->hasMany(Appointment::class);
    }
    public function ratings ()
    {
        return $this->hasMany(Rating::class);
    }
    public function getAverageRatingAttribute(): float
    {
        return round($this->ratings()->avg('rating') ?? 0, 1);
    }
    public function doctorNotes()
{
    return $this->hasMany(DoctorNote::class);
}
}

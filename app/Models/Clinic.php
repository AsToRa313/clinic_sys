<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'image',
        'phone',
        'description',
    ];
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
    
}

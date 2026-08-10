<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeownerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'city',
        'district',
        'country',
        'latitude',
        'longitude',
        'preferred_contact',
        'verified',
        'profile_completed',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'profile_completed' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Homeowner profile belongs to one user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

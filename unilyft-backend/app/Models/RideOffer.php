<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideOffer extends Model
{
    /** @use HasFactory<\Database\Factories\RideOfferFactory> */
    use HasFactory;

    // protected $primaryKey = 'rideOfferId';
    protected $fillable = [
        'userId',
        'startLocation',
        'destination',
        'date',
        'time',
        'availableSeats',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:d-m-Y',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function carpool()
    {
        return $this->hasOne(Carpool::class, 'rideOfferId');
    }
}

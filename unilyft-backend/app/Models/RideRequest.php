<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideRequest extends Model
{
    /** @use HasFactory<\Database\Factories\RideRequestFactory> */
    use HasFactory;


    protected $fillable = [
        'userId',
        'startLocation',
        'destination',
        'date',
        'time',
        'numberOfPassenger',
        'description',
        // 'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:d-m-Y',
            'time' => 'timestamp:h:i A',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId'); //2nd argument is the foreign key
    }

    public function carpool()
    {
        return $this->hasOne(Carpool::class, 'rideRequestId');
    }


}

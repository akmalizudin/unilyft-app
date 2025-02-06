<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carpool extends Model
{
    /** @use HasFactory<\Database\Factories\CarpoolFactory> */
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'start_location',
        'destination',
        'date',
        'time',
        'available_seats',
        'number_of_passenger',
        'description',
        'status',
    ];

    // public function users()
    // {
    //     return $this->belongsToMany(User::class);
    // }

    // public function driver()
    // {
    //     return $this->belongsTo(User::class, 'driver_id');
    // }

    public function users()
    {
        return $this->belongsToMany(User::class, 'carpool_user', 'carpool_id', 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function scopeWithUsers($query, $userId, $operator = '=')
    {
        return $query->whereHas('users', function ($query) use ($userId, $operator) {
            $query->where('user_id', $operator, $userId);
        });
    }

    // public function feedbacks()
    // {
    //     return $this->hasMany(Feedback::class, 'carpoolId');
    // }
    // public function feedback()
    // {
    //     return $this->hasOne(Feedback::class, 'carpoolId');
    // }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'carpoolId');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}

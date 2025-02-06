<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'carpool_id',
        'is_read',
        'type',
        'triggered_by_id',
    ];

    public function carpool()
    {
        return $this->belongsTo(Carpool::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }
}

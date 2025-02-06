<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFactory> */
    use HasFactory;

    protected $fillable = [
        'carpoolId',
        'authorId',
        'rating',
        'comment',
        // 'date',        
    ];

    public function author(){
        return $this->belongsTo(User::class, 'authorId');
    }

    public function carpool(){
        return $this->belongsTo(Carpool::class, 'carpoolId');
    } //yang ada foreign key yg belongsto
}

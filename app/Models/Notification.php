<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_type',
        'event_type',
        'title',
        'message',
    ];

    
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_notification')
                    ->withPivot('is_read')
                    ->withTimestamps();
    }
}
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'full_name',
        'email',
        'password',
        'device_token',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'device_token' => 'array',
        ];
    }

    public function administrator()
    {
        return $this->hasOne(Administrator::class);
    }


    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function parent()
    {
        return $this->hasOne(Parente::class);
    }


    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'user_notification')
            ->withPivot('is_read')
            ->withTimestamps();
    }

    public function addDeviceToken($token)
    {
        $tokens = $this->device_token ?? [];
        if (!in_array($token, $tokens)) {
            $tokens[] = $token;
            $this->device_token = $tokens;
            $this->save();
        }
        return $this;
    }

    // دالة مساعدة لحذف Token معين
    public function removeDeviceToken($token)
    {
        $tokens = $this->device_token ?? [];
        $tokens = array_filter($tokens, fn($t) => $t !== $token);
        $this->device_token = array_values($tokens);
        $this->save();
        return $this;
    }


}

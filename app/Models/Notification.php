<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    protected $fillable = [
        'notifiable_type',
        'title',
        'message'
    ];

    /**
     * العلاقة مع المستخدمين (Many-to-Many)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_notification')
            ->withPivot('is_read', 'created_at')
            ->withTimestamps();
    }

    /**
     * العلاقة مع جدول user_notification (للوصول للـ pivot)
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * نطاق جلب الإشعارات غير المقروءة
     */
    public function scopeUnread($query)
    {
        return $query->whereHas('userNotifications', function ($q) {
            $q->where('is_read', false);
        });
    }

    /**
     * نطاق جلب الإشعارات المقروءة
     */
    public function scopeRead($query)
    {
        return $query->whereHas('userNotifications', function ($q) {
            $q->where('is_read', true);
        });
    }

    /**
     * نطاق جلب الإشعارات حسب النوع
     */
    public function scopeByType($query, $type)
    {
        return $query->where('notifiable_type', $type);
    }
}
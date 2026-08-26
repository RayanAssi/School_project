<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $table = 'user_notification';

    protected $fillable = [
        'user_id',
        'notification_id',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع الإشعار
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * نطاق جلب الإشعارات غير المقروءة
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * نطاق جلب الإشعارات المقروءة
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}
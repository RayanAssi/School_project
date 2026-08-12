<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //

    protected $fillable = [
        'birth_date',
        'comment',
        'gender',
        'residential_address',
        'city',
        'parent_id',
        'section_id',
        'class_id',
        'user_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($student) {
            // حذف المستخدم المرتبط
            if ($student->user) {
                $student->user->delete();
            }
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subject')
            ->withPivot('date', 'note', 'duration', 'exam_type', 'mark')
            ->withTimestamps();
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function parent()
    {
        return $this->belongsTo(Parente::class, 'parent_id');
    }

}

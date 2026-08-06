<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    //

    protected $fillable = [
        'name',
        'comment',
        'full_mark',
        'class_id',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }


    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'subject_id');
    }


    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subject')
                    ->withPivot('date', 'note', 'duration', 'exam_type', 'mark')
                    ->withTimestamps();
    }
}

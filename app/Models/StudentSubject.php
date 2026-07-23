<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSubject extends Model
{
    //
    protected $table = 'student_subject';


    protected $fillable = [
        'student_id',
        'subject_id',
        'date',
        'note',
        'duration',
        'exam_type',
        'mark',
    ];


    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}

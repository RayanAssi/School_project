<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StudentSubject extends Model
{
    use HasFactory;

    protected $table = 'student_subject';

    protected $fillable = [
        'student_id',
        'subject_id',
        'date',
        'note',
        'duration',
        'exam_type',
        'mark'
    ];

    protected $casts = [
        'date' => 'date',
        'duration' => 'datetime:H:i:s',
        'mark' => 'float'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Accessor for formatted duration
    public function getFormattedDurationAttribute()
    {
        return $this->duration ? date('H:i', strtotime($this->duration)) : null;
    }

    // Check if student passed
    public function isPassed()
    {
        // Assuming pass mark is 50
        return $this->mark && $this->mark >= 50;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //

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

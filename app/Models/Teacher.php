<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    //
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject');
    }
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'teacher_section');
    }
}

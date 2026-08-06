<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    //
    protected $fillable = [
        'name',
        'comment',
        'class_id',
    ];
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }


    public function students()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_section');
    }
}

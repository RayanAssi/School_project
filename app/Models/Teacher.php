<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    //

    protected $fillable = [
        'gender',
        'comment',
        'role', 
        'phone_number',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject');
    }
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'teacher_section');
    }
}

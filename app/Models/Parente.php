<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parente extends Model
{
    //

    protected $table = 'parents';

    protected $fillable = [
        'full_name_father',
        'job_father',
        'phone_number_father',
        'full_name_mother',
        'job_mother',
        'phone_number_mother',
        'user_id',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}

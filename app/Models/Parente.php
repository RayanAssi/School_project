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
        'full_name_mather',
        'job_mather',
        'phone_number_mather',
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

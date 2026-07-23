<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    //

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}

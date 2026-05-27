<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subjects';

    protected $fillable = [
        'macibu_prieksments',
        'kategorija',
    ];

    public function grades()
    {
        return $this->hasMany(Grades::class, 'subject_id');
    }
}
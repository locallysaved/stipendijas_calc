<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grades extends Model
{
    public $timestamps = false; // no created_at/updated_at in this table

    protected $table = 'grades';

    protected $fillable = [
        'subject_id',
        'informacijas_tips',
        'informacijas_veids',
        'prieksmetu_veids',
        'datums',
        'vertejuma_tips',
        'ilens',
        'karklins',
        'varizeja',
    ];

    protected $casts = [
        'datums' => 'date',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
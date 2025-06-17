<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'section',
    ];

    public function studients()
    {
        return $this->hasMany(Studient::class, 'grade_id');
    }
}

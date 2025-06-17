<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studient extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'name',
        'last_name',
        'ci',
        'gender',
    ];

    public function dining()
    {
        return $this->hasMany(Dining::class, 'studient_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}

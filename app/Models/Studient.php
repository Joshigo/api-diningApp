<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Studient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grade_id',
        'name',
        'last_name',
        'ci',
        'gender',
    ];

    public function dining()
    {
        return $this->hasOne(Dining::class, 'studient_id')->latest('dining_time');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}

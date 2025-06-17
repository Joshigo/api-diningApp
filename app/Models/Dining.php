<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Dining extends Model
{
    use HasFactory;

    protected $fillable = [
        'studient_id',
        'has_eaten',
        'dining_time',
    ];

    protected $casts = [
        'has_eaten' => 'boolean',
        'dining_time' => 'datetime',
    ];

    public function studient()
    {
        return $this->belongsTo(Studient::class, 'studient_id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('dining_time', Carbon::now()->format('Y-m-d'));
    }

    public function scopeHasEaten($query, $hasEaten = true)
    {
        return $query->where('has_eaten', $hasEaten);
    }
}

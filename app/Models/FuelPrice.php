<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
        ];
    }

    public function grade()
    {
        return $this->belongsTo(FuelGrade::class, 'grade_id');
    }

    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
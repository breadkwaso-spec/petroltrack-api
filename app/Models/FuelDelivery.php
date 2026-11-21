<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelDelivery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    public function grade()
    {
        return $this->belongsTo(FuelGrade::class, 'grade_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
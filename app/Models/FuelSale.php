<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelSale extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function grade()
    {
        return $this->belongsTo(FuelGrade::class, 'grade_id');
    }

    public function loan()
    {
        return $this->hasOne(Loan::class);
    }

    public function voidRequests()
    {
        return $this->hasMany(VoidRequest::class);
    }
}
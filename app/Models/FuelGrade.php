<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelGrade extends Model
{
    protected $guarded = [];

    public function prices()
    {
        return $this->hasMany(FuelPrice::class, 'grade_id');
    }

    public function currentPrice()
    {
        return $this->hasOne(FuelPrice::class, 'grade_id')->latestOfMany('effective_from');
    }

    public function deliveries()
    {
        return $this->hasMany(FuelDelivery::class, 'grade_id');
    }

    public function stock()
    {
        return $this->hasOne(FuelStock::class, 'grade_id');
    }

    public function sales()
    {
        return $this->hasMany(FuelSale::class, 'grade_id');
    }
}
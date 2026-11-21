<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pump extends Model
{
    protected $guarded = [];

    public function fuelSales()
    {
        return $this->hasMany(FuelSale::class);
    }
}
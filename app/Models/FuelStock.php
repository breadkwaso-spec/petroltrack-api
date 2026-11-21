<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelStock extends Model
{
    protected $primaryKey = 'grade_id';
    public $incrementing = false;
    const CREATED_AT = null;

    protected $guarded = [];

    public function grade()
    {
        return $this->belongsTo(FuelGrade::class, 'grade_id');
    }
}
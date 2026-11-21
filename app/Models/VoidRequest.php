<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoidRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(FuelSale::class, 'fuel_sale_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
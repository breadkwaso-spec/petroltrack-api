<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
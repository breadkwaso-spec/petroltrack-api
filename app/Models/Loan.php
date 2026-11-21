<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }

    public function sale()
    {
        return $this->belongsTo(FuelSale::class, 'fuel_sale_id');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }
}
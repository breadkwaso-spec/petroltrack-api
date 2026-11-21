<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'employee_id',
        'full_name',
        'password',
        'role',
        'status',
        'failed_attempts',
        'locked_until',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'locked_until' => 'datetime',
        ];
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function activeShift()
    {
        return $this->hasOne(Shift::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    public function fuelSales()
    {
        return $this->hasMany(FuelSale::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'Owner';
    }

    public function isAttendant(): bool
    {
        return $this->role === 'Attendant';
    }
}
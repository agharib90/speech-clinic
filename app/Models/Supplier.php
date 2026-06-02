<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'address', 'balance', 'notes'
    ];

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}

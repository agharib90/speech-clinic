<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'category', 'quantity', 'unit', 'price', 'reorder_level', 'supplier_id', 'notes'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

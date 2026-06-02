<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramEquipment extends Model
{
    protected $fillable = [
        'therapy_program_id', 'equipment_id', 'quantity', 'unit_price', 'notes'
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}

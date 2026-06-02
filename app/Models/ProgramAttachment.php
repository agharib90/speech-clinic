<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramAttachment extends Model
{
    protected $fillable = [
        'therapy_program_id', 'file_name', 'file_path', 'attachment_type', 'description'
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }
}

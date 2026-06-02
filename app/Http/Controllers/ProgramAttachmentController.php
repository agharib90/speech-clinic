<?php

namespace App\Http\Controllers;

use App\Models\TherapyProgram;
use App\Models\ProgramAttachment;
use Illuminate\Http\Request;

class ProgramAttachmentController extends Controller
{
    public function store(Request $request, TherapyProgram $program)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,mp3,mp4,wav,pdf|max:20480', // 20MB max
            'attachment_type' => 'required|in:مرفق_عام,تسجيل_قبل,تسجيل_بعد',
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('attachments', $fileName, 'public');

        ProgramAttachment::create([
            'therapy_program_id' => $program->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'attachment_type' => $request->attachment_type,
            'description' => $request->description,
        ]);

        return back()->with('success', 'تم رفع الملف بنجاح');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TherapyProgram;
use App\Models\ProgramAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProgramAttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, TherapyProgram $program)
    {
        $this->authorize('view', $program);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,mp3,mp4,wav,pdf|max:20480', // 20MB max
            'attachment_type' => 'required|in:مرفق_عام,تسجيل_قبل,تسجيل_بعد',
            'description' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $storedName = (string) Str::uuid() . ($extension ? ".{$extension}" : '');
        $filePath = $file->storeAs('attachments', $storedName, 'public');

        ProgramAttachment::create([
            'therapy_program_id' => $program->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'attachment_type' => $request->attachment_type,
            'description' => $request->description,
        ]);

        return back()->with('success', 'تم رفع الملف بنجاح');
    }
}

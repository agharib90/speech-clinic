<?php

namespace App\Http\Controllers;

use App\Models\HomeTask;
use App\Models\TherapySession;
use Illuminate\Http\Request;

class HomeTaskController extends Controller
{
    // إضافة واجب لجلسة معينة
    public function store(Request $request)
    {
        $request->validate([
            'therapy_session_id' => 'required|exists:therapy_sessions,id',
            'description' => 'required|string',
            'due_date' => 'nullable|date',
        ]);

        HomeTask::create($request->all());

        return back()->with('success', 'تم إضافة الواجب المنزلي بنجاح');
    }

    // تسجيل ملاحظات ولي الأمر أو اكتمال الواجب
    public function parentFeedback(Request $request, HomeTask $task)
    {
        $request->validate([
            'parent_feedback' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
        ]);

        if ($request->has('is_completed')) {
            $task->update(['is_completed' => true]);
        }

        if ($request->filled('parent_feedback')) {
            $task->update(['parent_feedback' => $request->parent_feedback]);
        }

        return back()->with('success', 'تم تحديث حالة الواجب بنجاح');
    }
}

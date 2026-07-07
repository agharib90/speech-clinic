<?php

namespace App\Http\Controllers;

use App\Models\HomeTask;
use App\Models\TherapySession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class HomeTaskController extends Controller
{
    use AuthorizesRequests;

    // إضافة واجب لجلسة معينة
    public function store(Request $request)
    {
        $data = $request->validate([
            'therapy_session_id' => 'required|exists:therapy_sessions,id',
            'description' => 'required|string',
            'due_date' => 'nullable|date',
        ]);

        $session = TherapySession::with('program')->findOrFail($data['therapy_session_id']);
        $this->authorize('view', $session->program);

        HomeTask::create($data);

        return back()->with('success', 'تم إضافة الواجب المنزلي بنجاح');
    }

    // تسجيل ملاحظات ولي الأمر أو اكتمال الواجب
    public function parentFeedback(Request $request, HomeTask $task)
    {
        $task->load('session.program');
        $this->authorize('view', $task->session->program);

        $data = $request->validate([
            'parent_feedback' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
        ]);

        if (array_key_exists('is_completed', $data)) {
            $task->update(['is_completed' => true]);
        }

        if (! empty($data['parent_feedback'])) {
            $task->update(['parent_feedback' => $data['parent_feedback']]);
        }

        return back()->with('success', 'تم تحديث حالة الواجب بنجاح');
    }
}

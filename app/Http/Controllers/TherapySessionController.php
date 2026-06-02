<?php

namespace App\Http\Controllers;

use App\Models\TherapySession;
use Illuminate\Http\Request;

class TherapySessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = TherapyProgram::with('patient', 'therapist')
    ->where('status', 'جاري')
    ->latest()
    ->paginate(15);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(TherapySession $therapySession)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TherapySession $therapySession)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TherapySession $therapySession)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TherapySession $therapySession)
    {
        //
    }
}

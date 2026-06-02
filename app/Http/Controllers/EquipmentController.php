<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = Equipment::with('supplier')->latest()->get();
        $suppliers = \App\Models\Supplier::all(); // للقائمة المنسدلة
        return view('inventory.equipment.index', compact('equipment', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'quantity' => 'required|integer',
            'reorder_level' => 'required|integer',
        ]);
        Equipment::create($request->all());
        return back()->with('success', 'تم إضافة المستلزم');
    }
}

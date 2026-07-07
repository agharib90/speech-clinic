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
        $data = $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
        ]);
        Equipment::create($data);
        return back()->with('success', 'تم إضافة المستلزم');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->get();
        return view('inventory.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required', 'phone' => 'nullable']);
        Supplier::create($request->all());
        return back()->with('success', 'تم إضافة المورد');
    }
}

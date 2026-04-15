<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query()->where('is_deleted', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderByDesc('id')->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $data['is_deleted'] = 0;
        $data['is_locked'] = 0;

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'تمت إضافة المورد بنجاح.');
    }

    public function edit($id)
    {
        $supplier = Supplier::where('is_deleted', 0)->findOrFail($id);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->is_locked) {
            return redirect()->route('suppliers.index')->with('error', 'لا يمكن تعديل مورد مقفل.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'تم تعديل المورد بنجاح.');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->is_locked) {
            return redirect()->route('suppliers.index')->with('error', 'لا يمكن حذف مورد مقفل.');
        }

        $supplier->update([
            'is_deleted' => 1,
        ]);

        return redirect()->route('suppliers.index')->with('success', 'تم حذف المورد بنجاح.');
    }
}
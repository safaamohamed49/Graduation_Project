<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->where('is_deleted', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('id')->paginate(15);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:customers,name'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_deleted'] = 0;
        $data['is_locked'] = 0;

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'تمت إضافة العميل بنجاح.');
    }

    public function edit($id)
    {
        $customer = Customer::where('is_deleted', 0)->findOrFail($id);

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->is_locked) {
            return redirect()->route('customers.index')->with('error', 'لا يمكن تعديل عميل مقفل.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:customers,name,' . $customer->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'تم تعديل العميل بنجاح.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->is_locked) {
            return redirect()->route('customers.index')->with('error', 'لا يمكن حذف عميل مقفل.');
        }

        $customer->update([
            'is_deleted' => 1,
        ]);

        return redirect()->route('customers.index')->with('success', 'تم حذف العميل بنجاح.');
    }
}
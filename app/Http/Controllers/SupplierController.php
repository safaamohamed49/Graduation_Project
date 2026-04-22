<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));

        $query = Supplier::query()->where('is_deleted', false);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $suppliers = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Supplier::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existingByName) {
            if ($existingByName->is_deleted) {
                return back()->withErrors([
                    'name' => 'يوجد مورد محذوف سابقًا بنفس الاسم "' . $existingByName->name . '". يفضل مراجعته بدل إنشاء مورد جديد.',
                ])->withInput();
            }

            return back()->withErrors([
                'name' => 'لا يمكن إضافة المورد، لأن الاسم "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Supplier::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();
        if ($existingByCode) {
            if ($existingByCode->is_deleted) {
                return back()->withErrors([
                    'code' => 'يوجد مورد محذوف سابقًا يستخدم الرمز "' . $existingByCode->code . '" واسمه "' . $existingByCode->name . '".',
                ])->withInput();
            }

            return back()->withErrors([
                'code' => 'لا يمكن إضافة المورد، لأن الرمز "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        Supplier::create([
            'branch_id' => null,
            'name' => $name,
            'code' => $code,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'account_id' => null,
            'is_active' => $request->boolean('is_active', true),
            'is_deleted' => false,
            'is_locked' => false,
        ]);

        return redirect('/suppliers')->with('success', 'تمت إضافة المورد بنجاح.');
    }

    public function edit(Supplier $supplier): Response
    {
        if ($supplier->is_deleted) {
            abort(404);
        }

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        if ($supplier->is_locked) {
            return back()->withErrors([
                'edit' => 'لا يمكن تعديل مورد مقفل.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Supplier::where('id', '!=', $supplier->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل المورد، لأن الاسم "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Supplier::where('id', '!=', $supplier->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن تعديل المورد، لأن الرمز "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        $supplier->update([
            'name' => $name,
            'code' => $code,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect('/suppliers')->with('success', 'تم تعديل المورد بنجاح.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->is_locked) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف مورد مقفل.',
            ]);
        }

        $supplier->update([
            'is_deleted' => true,
            'is_active' => false,
        ]);

        return redirect('/suppliers')->with('success', 'تم حذف المورد منطقيًا بنجاح.');
    }
}
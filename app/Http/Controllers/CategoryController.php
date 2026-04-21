<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));

        $query = Category::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $categories = $query
            ->withCount('products')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $existingByName = Category::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])->first();
        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن إضافة الفئة، لأن اسم الفئة "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Category::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($data['code']))])->first();
        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن إضافة الفئة، لأن رمز الفئة "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        Category::create([
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تمت إضافة الفئة بنجاح.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $existingByName = Category::where('id', '!=', $category->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل الفئة، لأن اسم الفئة "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Category::where('id', '!=', $category->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($data['code']))])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن تعديل الفئة، لأن رمز الفئة "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        $category->update([
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تم تعديل الفئة بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف الفئة "' . $category->name . '" لأنها مرتبطة بمنتجات موجودة في النظام.',
            ]);
        }

        $category->delete();

        return back()->with('success', 'تم حذف الفئة بنجاح.');
    }
}
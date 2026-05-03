<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('categories.view', 'ليس لديك صلاحية لعرض الفئات.');

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
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('categories.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('categories.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('categories.delete') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('categories.create', 'ليس لديك صلاحية لإضافة فئة.');

        return Inertia::render('Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('categories.create', 'ليس لديك صلاحية لإضافة فئة.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن إضافة الفئة، لأن اسم الفئة "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Category::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن إضافة الفئة، لأن رمز الفئة "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        Category::create([
            'name' => $name,
            'code' => $code,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('categories.index')->with('success', 'تمت إضافة الفئة بنجاح.');
    }

    public function edit(Category $category): Response
    {
        $this->authorizePermission('categories.update', 'ليس لديك صلاحية لتعديل الفئات.');

        return Inertia::render('Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorizePermission('categories.update', 'ليس لديك صلاحية لتعديل الفئات.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $existingByName = Category::where('id', '!=', $category->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل الفئة، لأن اسم الفئة "' . $existingByName->name . '" موجود بالفعل.',
            ])->withInput();
        }

        $existingByCode = Category::where('id', '!=', $category->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'code' => 'لا يمكن تعديل الفئة، لأن رمز الفئة "' . $existingByCode->code . '" مستخدم بالفعل.',
            ])->withInput();
        }

        $category->update([
            'name' => $name,
            'code' => $code,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('categories.index')->with('success', 'تم تعديل الفئة بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorizePermission('categories.delete', 'ليس لديك صلاحية لحذف الفئات.');

        if ($category->products()->exists()) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف الفئة "' . $category->name . '" لأنها مرتبطة بمنتجات موجودة في النظام.',
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'تم حذف الفئة بنجاح.');
    }
}
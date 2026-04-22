<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));
        $categoryId = $request->get('category_id');
        $status = $request->get('status', 'active');

        $query = Product::query()->with('category');

        if ($status === 'active') {
            $query->where('is_deleted', false);
        } elseif ($status === 'deleted') {
            $query->where('is_deleted', true);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        $products = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'product_code' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'unit_name' => ['required', 'string', 'max:100'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'current_price' => ['required', 'numeric', 'min:0'],
            'last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_service' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $name = trim($data['name']);
        $productCode = strtoupper(trim($data['product_code']));
        $barcode = isset($data['barcode']) && $data['barcode'] !== null && trim($data['barcode']) !== ''
            ? trim($data['barcode'])
            : null;

        $normalizedName = mb_strtolower($name);
        $normalizedCode = mb_strtolower($productCode);
        $normalizedBarcode = $barcode ? mb_strtolower($barcode) : null;

        $existingByName = Product::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        if ($existingByName) {
            if ($existingByName->is_deleted) {
                return back()->withErrors([
                    'name' => 'يوجد منتج محذوف سابقًا بنفس الاسم "' . $existingByName->name . '" وبرمز ' . $existingByName->product_code . '. يفضل إعادة تفعيله بدل إنشاء منتج جديد.',
                ])->withInput();
            }

            return back()->withErrors([
                'name' => 'لا يمكن إضافة المنتج، لأن اسم المنتج "' . $existingByName->name . '" موجود بالفعل برمز ' . $existingByName->product_code . '.',
            ])->withInput();
        }

        $existingByCode = Product::whereRaw('LOWER(product_code) = ?', [$normalizedCode])->first();
        if ($existingByCode) {
            if ($existingByCode->is_deleted) {
                return back()->withErrors([
                    'product_code' => 'يوجد منتج محذوف سابقًا يستخدم الرمز "' . $existingByCode->product_code . '" واسمه "' . $existingByCode->name . '". يفضل إعادة تفعيله بدل إنشاء منتج جديد.',
                ])->withInput();
            }

            return back()->withErrors([
                'product_code' => 'لا يمكن إضافة المنتج، لأن رمز المنتج "' . $existingByCode->product_code . '" مستخدم بالفعل للمنتج "' . $existingByCode->name . '".',
            ])->withInput();
        }

        if ($normalizedBarcode) {
            $existingByBarcode = Product::whereRaw('LOWER(barcode) = ?', [$normalizedBarcode])->first();
            if ($existingByBarcode) {
                if ($existingByBarcode->is_deleted) {
                    return back()->withErrors([
                        'barcode' => 'يوجد منتج محذوف سابقًا يستخدم الباركود "' . $existingByBarcode->barcode . '" واسمه "' . $existingByBarcode->name . '". يفضل إعادة تفعيله بدل إنشاء منتج جديد.',
                    ])->withInput();
                }

                return back()->withErrors([
                    'barcode' => 'لا يمكن إضافة المنتج، لأن الباركود "' . $existingByBarcode->barcode . '" مستخدم بالفعل للمنتج "' . $existingByBarcode->name . '".',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($data, $name, $productCode, $barcode) {
            $product = Product::create([
                'category_id' => $data['category_id'] ?? null,
                'name' => $name,
                'product_code' => $productCode,
                'barcode' => $barcode,
                'unit_name' => trim($data['unit_name']),
                'image_path' => $data['image_path'] ?? null,
                'current_price' => $data['current_price'],
                'last_purchase_price' => $data['last_purchase_price'] ?? 0,
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_service' => $data['is_service'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'is_deleted' => false,
                'notes' => $data['notes'] ?? null,
            ]);

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'price' => $product->current_price,
                'start_date' => now(),
                'end_date' => null,
                'notes' => 'سعر افتتاحي للمنتج',
            ]);
        });

        return redirect()->route('products.index')->with('success', 'تمت إضافة المنتج بنجاح.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $product->load('category'),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'product_code' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'unit_name' => ['required', 'string', 'max:100'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'current_price' => ['required', 'numeric', 'min:0'],
            'last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_service' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $name = trim($data['name']);
        $productCode = strtoupper(trim($data['product_code']));
        $barcode = isset($data['barcode']) && $data['barcode'] !== null && trim($data['barcode']) !== ''
            ? trim($data['barcode'])
            : null;

        $normalizedName = mb_strtolower($name);
        $normalizedCode = mb_strtolower($productCode);
        $normalizedBarcode = $barcode ? mb_strtolower($barcode) : null;

        $existingByName = Product::where('id', '!=', $product->id)
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->first();

        if ($existingByName) {
            return back()->withErrors([
                'name' => 'لا يمكن تعديل المنتج، لأن اسم المنتج "' . $existingByName->name . '" موجود بالفعل برمز ' . $existingByName->product_code . '.',
            ])->withInput();
        }

        $existingByCode = Product::where('id', '!=', $product->id)
            ->whereRaw('LOWER(product_code) = ?', [$normalizedCode])
            ->first();

        if ($existingByCode) {
            return back()->withErrors([
                'product_code' => 'لا يمكن تعديل المنتج، لأن رمز المنتج "' . $existingByCode->product_code . '" مستخدم بالفعل للمنتج "' . $existingByCode->name . '".',
            ])->withInput();
        }

        if ($normalizedBarcode) {
            $existingByBarcode = Product::where('id', '!=', $product->id)
                ->whereRaw('LOWER(barcode) = ?', [$normalizedBarcode])
                ->first();

            if ($existingByBarcode) {
                return back()->withErrors([
                    'barcode' => 'لا يمكن تعديل المنتج، لأن الباركود "' . $existingByBarcode->barcode . '" مستخدم بالفعل للمنتج "' . $existingByBarcode->name . '".',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($product, $data, $name, $productCode, $barcode) {
            $oldPrice = (float) $product->current_price;
            $newPrice = (float) $data['current_price'];

            $product->update([
                'category_id' => $data['category_id'] ?? null,
                'name' => $name,
                'product_code' => $productCode,
                'barcode' => $barcode,
                'unit_name' => trim($data['unit_name']),
                'image_path' => $data['image_path'] ?? null,
                'current_price' => $newPrice,
                'last_purchase_price' => $data['last_purchase_price'] ?? 0,
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_service' => $data['is_service'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($oldPrice !== $newPrice) {
                ProductPriceHistory::where('product_id', $product->id)
                    ->whereNull('end_date')
                    ->update(['end_date' => now()]);

                ProductPriceHistory::create([
                    'product_id' => $product->id,
                    'price' => $newPrice,
                    'start_date' => now(),
                    'end_date' => null,
                    'notes' => 'تحديث سعر المنتج',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'تم تعديل المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update([
            'is_deleted' => true,
            'is_active' => false,
        ]);

        return redirect()->route('products.index')->with('success', 'تم حذف المنتج منطقيًا بنجاح.');
    }

    public function restore(Product $product): RedirectResponse
    {
        if (!$product->is_deleted) {
            return back()->withErrors([
                'restore' => 'هذا المنتج غير محذوف أصلًا.',
            ]);
        }

        $conflictByName = Product::where('id', '!=', $product->id)
            ->where('is_deleted', false)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($product->name)])
            ->first();

        if ($conflictByName) {
            return back()->withErrors([
                'restore' => 'لا يمكن استرجاع المنتج لأن الاسم "' . $product->name . '" مستخدم حاليًا في منتج آخر برمز ' . $conflictByName->product_code . '.',
            ]);
        }

        $conflictByCode = Product::where('id', '!=', $product->id)
            ->where('is_deleted', false)
            ->whereRaw('LOWER(product_code) = ?', [mb_strtolower($product->product_code)])
            ->first();

        if ($conflictByCode) {
            return back()->withErrors([
                'restore' => 'لا يمكن استرجاع المنتج لأن الرمز "' . $product->product_code . '" مستخدم حاليًا في منتج آخر اسمه "' . $conflictByCode->name . '".',
            ]);
        }

        if (!empty($product->barcode)) {
            $conflictByBarcode = Product::where('id', '!=', $product->id)
                ->where('is_deleted', false)
                ->whereRaw('LOWER(barcode) = ?', [mb_strtolower($product->barcode)])
                ->first();

            if ($conflictByBarcode) {
                return back()->withErrors([
                    'restore' => 'لا يمكن استرجاع المنتج لأن الباركود "' . $product->barcode . '" مستخدم حاليًا في منتج آخر اسمه "' . $conflictByBarcode->name . '".',
                ]);
            }
        }

        $product->update([
            'is_deleted' => false,
            'is_active' => true,
        ]);

        return redirect()->route('products.index')->with('success', 'تمت إعادة تفعيل المنتج بنجاح.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_deleted', 0);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('id')->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:100', 'unique:products,product_code'],
            'current_price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $existingProduct = Product::where('name', $data['name'])->first();

        if ($existingProduct) {
            if ((int) $existingProduct->is_deleted === 1) {
                DB::transaction(function () use ($existingProduct, $data) {
                    $existingProduct->update([
                        'name' => $data['name'],
                        'product_code' => $data['product_code'] ?? $existingProduct->product_code,
                        'current_price' => $data['current_price'],
                        'category_id' => $data['category_id'] ?? null,
                        'is_deleted' => 0,
                    ]);

                    ProductPriceHistory::where('product_id', $existingProduct->id)
                        ->whereNull('end_date')
                        ->update(['end_date' => now()]);

                    ProductPriceHistory::create([
                        'product_id' => $existingProduct->id,
                        'price' => $data['current_price'],
                        'start_date' => now(),
                        'end_date' => null,
                    ]);
                });

                return redirect()
                    ->route('products.index')
                    ->with('success', 'المنتج كان موجودًا ومحذوفًا وتمت إعادة تفعيله بنجاح.');
            }

            return back()
                ->withErrors(['name' => 'يوجد منتج فعال بنفس الاسم بالفعل.'])
                ->withInput();
        }

        DB::transaction(function () use ($data) {
            $product = Product::create([
                'name' => $data['name'],
                'product_code' => $data['product_code'] ?? null,
                'stock' => 0,
                'current_price' => $data['current_price'],
                'last_purchase_price' => 0,
                'is_deleted' => 0,
                'category_id' => $data['category_id'] ?? null,
            ]);

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'price' => $data['current_price'],
                'start_date' => now(),
                'end_date' => null,
            ]);
        });

        return redirect()->route('products.index')->with('success', 'تمت إضافة المنتج بنجاح.');
    }

    public function edit($id)
    {
        $product = Product::where('is_deleted', 0)->findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:products,name,' . $product->id],
            'product_code' => ['nullable', 'string', 'max:100', 'unique:products,product_code,' . $product->id],
            'current_price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        DB::transaction(function () use ($product, $data) {
            $oldPrice = (float) $product->current_price;
            $newPrice = (float) $data['current_price'];

            $product->update([
                'name' => $data['name'],
                'product_code' => $data['product_code'] ?? null,
                'current_price' => $newPrice,
                'category_id' => $data['category_id'] ?? null,
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
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'تم تعديل المنتج بنجاح.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $product->update([
            'is_deleted' => 1,
        ]);

        return redirect()->route('products.index')->with('success', 'تم حذف المنتج بنجاح.');
    }
}
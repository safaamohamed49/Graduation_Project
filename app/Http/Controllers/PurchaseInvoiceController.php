<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));

        $query = PurchaseInvoice::query()
            ->with(['supplier', 'branch', 'warehouse'])
            ->where('is_deleted', false);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PurchaseInvoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PurchaseInvoices/Create', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_deleted', false)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_deleted', false)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateInvoice($request);

        DB::transaction(function () use ($data) {
            $this->createInvoiceWithItemsAndBatches($data);
        });

        return redirect('/purchase-invoices')->with('success', 'تم حفظ فاتورة الشراء وتحديث المخزون بنجاح.');
    }

    public function edit(PurchaseInvoice $purchaseInvoice): Response
    {
        if ($purchaseInvoice->is_deleted) {
            abort(404);
        }

        $purchaseInvoice->load(['items.product', 'supplier', 'branch', 'warehouse']);

        return Inertia::render('PurchaseInvoices/Edit', [
            'invoice' => $purchaseInvoice,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_deleted', false)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_deleted', false)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->is_deleted) {
            abort(404);
        }

        if ($this->invoiceHasConsumedBatches($purchaseInvoice)) {
            return back()->withErrors([
                'invoice' => 'لا يمكن تعديل هذه الفاتورة لأن بعض كمياتها تم استخدامها في فواتير بيع.',
            ]);
        }

        $data = $this->validateInvoice($request, $purchaseInvoice->id);

        DB::transaction(function () use ($data, $purchaseInvoice) {
            PurchaseInvoiceItem::where('invoice_id', $purchaseInvoice->id)->delete();
            InventoryBatch::where('purchase_invoice_id', $purchaseInvoice->id)->delete();

            $this->updateInvoiceWithItemsAndBatches($purchaseInvoice, $data);
        });

        return redirect('/purchase-invoices')->with('success', 'تم تعديل فاتورة الشراء وتحديث المخزون بنجاح.');
    }

    public function destroy(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->is_deleted) {
            return back()->withErrors([
                'delete' => 'هذه الفاتورة محذوفة بالفعل.',
            ]);
        }

        if ($this->invoiceHasConsumedBatches($purchaseInvoice)) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف هذه الفاتورة لأن بعض كمياتها تم استخدامها في فواتير بيع.',
            ]);
        }

        DB::transaction(function () use ($purchaseInvoice) {
            $purchaseInvoice->update([
                'is_deleted' => true,
                'updated_by_user_id' => auth()->id(),
            ]);

            InventoryBatch::where('purchase_invoice_id', $purchaseInvoice->id)->delete();
        });

        return redirect('/purchase-invoices')->with('success', 'تم حذف فاتورة الشراء منطقيًا بنجاح.');
    }

    public function extractImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'مفتاح Gemini غير موجود في ملف .env.',
            ], 422);
        }

        $image = $request->file('image');
        $base64 = base64_encode(file_get_contents($image->getRealPath()));

        $products = Product::where('is_deleted', false)
            ->where('is_active', true)
            ->select('id', 'name', 'product_code', 'barcode')
            ->orderBy('name')
            ->get();

        $productsList = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'product_code' => $product->product_code,
                'barcode' => $product->barcode,
            ];
        })->values()->toArray();

        $prompt = '
أنت مساعد متخصص في قراءة فواتير الشراء من الصور.

المطلوب:
استخرج بيانات فاتورة شراء من الصورة، وأرجع النتيجة JSON فقط بدون أي شرح وبدون Markdown.

ملاحظات مهمة:
- إذا كانت الصورة غير واضحة، استخرج فقط البيانات الواضحة.
- إذا كان اسم المنتج أو الكمية أو السعر غير واضح، لا تعتمد البند.
- لا تخترع بيانات غير موجودة في الصورة.
- التاريخ يجب أن يكون بصيغة YYYY-MM-DD إن وجد.
- السعر المقصود هو سعر شراء الوحدة.
- حاول مطابقة اسم المنتج مع قائمة المنتجات المتاحة.

قائمة المنتجات الموجودة في النظام:
' . json_encode($productsList, JSON_UNESCAPED_UNICODE) . '

صيغة JSON المطلوبة بالضبط:
{
  "invoice_number": "",
  "invoice_date": "",
  "supplier_name": "",
  "items": [
    {
      "product_id": null,
      "product_name": "",
      "quantity": 1,
      "price": 0,
      "notes": ""
    }
  ]
}
';

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

        $response = Http::timeout(60)->post($endpoint, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $image->getMimeType(),
                                'data' => $base64,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'response_mime_type' => 'application/json',
            ],
        ]);

        if (!$response->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تحليل الصورة من Gemini.',
                'details' => $response->json(),
            ], 422);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (!$text) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم استخراج أي نص من الصورة.',
            ], 422);
        }

        $data = json_decode($text, true);

        if (!is_array($data)) {
            preg_match('/\{.*\}/s', $text, $matches);
            $data = isset($matches[0]) ? json_decode($matches[0], true) : null;
        }

        if (!is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر تحويل نتيجة الذكاء إلى JSON صالح.',
            ], 422);
        }

        $draftItems = collect($data['items'] ?? [])
            ->map(function ($item) use ($products) {
                $productId = $item['product_id'] ?? null;
                $productName = trim((string) ($item['product_name'] ?? ''));

                $matchedProduct = null;

                if ($productId) {
                    $matchedProduct = $products->firstWhere('id', (int) $productId);
                }

                if (!$matchedProduct && $productName !== '') {
                    $matchedProduct = $this->matchProductByName($products, $productName);
                }

                $quantity = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);

                if ($quantity <= 0 || $price < 0) {
                    return null;
                }

                return [
                    'product_id' => $matchedProduct?->id ?? '',
                    'product_name' => $productName,
                    'quantity' => $quantity,
                    'price' => $price,
                    'notes' => $matchedProduct
                        ? ($item['notes'] ?? '')
                        : 'لم يتم العثور على تطابق مؤكد للمنتج: ' . $productName,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'message' => 'تم تحليل الصورة بنجاح. راجعي البيانات قبل الحفظ.',
            'draft' => [
                'invoice_number' => $data['invoice_number'] ?? '',
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'supplier_name' => $data['supplier_name'] ?? '',
                'items' => $draftItems,
            ],
        ]);
    }

    private function validateInvoice(Request $request, ?int $invoiceId = null): array
    {
        $uniqueRule = 'unique:purchase_invoices,invoice_number';

        if ($invoiceId) {
            $uniqueRule .= ',' . $invoiceId;
        }

        return $request->validate([
            'invoice_number' => ['required', 'string', 'max:255', $uniqueRule],
            'branch_id' => ['required', 'exists:branches,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_expenses' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function createInvoiceWithItemsAndBatches(array $data): PurchaseInvoice
    {
        $totals = $this->calculateTotals($data);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => trim($data['invoice_number']),
            'branch_id' => $data['branch_id'],
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'],
            'invoice_date' => $data['invoice_date'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_expenses' => $totals['expenses'],
            'total_price' => $totals['total_price'],
            'total_base_price' => $totals['subtotal'],
            'journal_entry_id' => null,
            'user_id' => auth()->id(),
            'updated_by_user_id' => null,
            'is_deleted' => false,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->createItemsAndBatches($invoice, $data);

        return $invoice;
    }

    private function updateInvoiceWithItemsAndBatches(PurchaseInvoice $invoice, array $data): void
    {
        $totals = $this->calculateTotals($data);

        $invoice->update([
            'invoice_number' => trim($data['invoice_number']),
            'branch_id' => $data['branch_id'],
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'],
            'invoice_date' => $data['invoice_date'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_expenses' => $totals['expenses'],
            'total_price' => $totals['total_price'],
            'total_base_price' => $totals['subtotal'],
            'updated_by_user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->createItemsAndBatches($invoice, $data);
    }

    private function calculateTotals(array $data): array
    {
        $discount = (float) ($data['discount_amount'] ?? 0);
        $expenses = (float) ($data['total_expenses'] ?? 0);

        $subtotal = collect($data['items'])->sum(function ($item) {
            return (float) $item['quantity'] * (float) $item['price'];
        });

        if ($discount > $subtotal) {
            abort(422, 'قيمة الخصم لا يمكن أن تكون أكبر من إجمالي الفاتورة.');
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'expenses' => $expenses,
            'total_price' => $subtotal - $discount + $expenses,
        ];
    }

    private function createItemsAndBatches(PurchaseInvoice $invoice, array $data): void
    {
        foreach ($data['items'] as $item) {
            $quantity = (float) $item['quantity'];
            $price = (float) $item['price'];
            $costPrice = $price;

            PurchaseInvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'price' => $price,
                'cost_price' => $costPrice,
                'notes' => $item['notes'] ?? null,
            ]);

            InventoryBatch::create([
                'product_id' => $item['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'purchase_invoice_id' => $invoice->id,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'base_price' => $price,
                'purchase_price' => $costPrice,
                'entry_date' => $data['invoice_date'],
                'expiry_date' => null,
                'notes' => 'دفعة ناتجة عن فاتورة شراء رقم ' . $invoice->invoice_number,
            ]);

            Product::where('id', $item['product_id'])->update([
                'last_purchase_price' => $price,
            ]);
        }
    }

    private function invoiceHasConsumedBatches(PurchaseInvoice $invoice): bool
    {
        return InventoryBatch::where('purchase_invoice_id', $invoice->id)
            ->whereColumn('remaining_quantity', '<', 'quantity')
            ->exists();
    }

    private function matchProductByName($products, string $name): ?Product
    {
        $normalizedName = $this->normalizeText($name);

        return $products->first(function ($product) use ($normalizedName) {
            $productName = $this->normalizeText($product->name);
            $productCode = $this->normalizeText((string) $product->product_code);
            $barcode = $this->normalizeText((string) $product->barcode);

            return $productName === $normalizedName
                || str_contains($productName, $normalizedName)
                || str_contains($normalizedName, $productName)
                || ($productCode !== '' && str_contains($normalizedName, $productCode))
                || ($barcode !== '' && str_contains($normalizedName, $barcode));
        });
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = str_replace('ة', 'ه', $value);
        $value = str_replace('ى', 'ي', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
    }
}
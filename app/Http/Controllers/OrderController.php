<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBatch;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    private function isAdmin(): bool
    {
        return auth()->user()?->hasPermission('*') ?? false;
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('orders.view', 'ليس لديك صلاحية لعرض فواتير البيع.');

        $search = trim((string) $request->get('search', ''));

        $query = Order::query()
            ->with(['customer', 'branch', 'warehouse'])
            ->where('is_deleted', false);

        if (!$this->isAdmin()) {
            $query->where('branch_id', auth()->user()?->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => ['search' => $search],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('orders.create') ?? false,
                'canUpdate' => auth()->user()?->hasPermission('orders.update') ?? false,
                'canDelete' => auth()->user()?->hasPermission('orders.delete') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('orders.create', 'ليس لديك صلاحية لإضافة فاتورة بيع.');

        return Inertia::render('Orders/Create', [
            'orderNumber' => $this->generateOrderNumber(),
            'warehouses' => $this->availableWarehouses(),
            'customers' => $this->availableCustomers(),
            'products' => $this->availableProducts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('orders.create', 'ليس لديك صلاحية لإضافة فاتورة بيع.');

        $data = $this->validateOrder($request);

        DB::transaction(function () use ($data) {
            $this->createOrderWithFifo($data);
        });

        return redirect('/orders')->with('success', 'تم حفظ فاتورة البيع وتحديث المخزون بنظام FIFO بنجاح.');
    }

    public function show(Order $order): Response
    {
        $this->authorizePermission('orders.view', 'ليس لديك صلاحية لعرض فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        $order->load(['customer', 'branch', 'warehouse', 'items.product', 'items.batches.batch']);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order): Response
    {
        $this->authorizePermission('orders.update', 'ليس لديك صلاحية لتعديل فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        if ($order->is_deleted) {
            abort(404);
        }

        $order->load(['items.product', 'customer', 'warehouse']);

        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'warehouses' => $this->availableWarehouses(),
            'customers' => $this->availableCustomers(),
            'products' => $this->availableProducts(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorizePermission('orders.update', 'ليس لديك صلاحية لتعديل فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        if ($order->is_deleted) {
            abort(404);
        }

        $data = $this->validateOrder($request, $order->id);

        DB::transaction(function () use ($data, $order) {
            $this->restoreOrderQuantities($order);

            OrderItemBatch::whereIn(
                'order_item_id',
                $order->items()->pluck('id')
            )->delete();

            $order->items()->delete();

            $this->updateOrderWithFifo($order, $data);
        });

        return redirect('/orders')->with('success', 'تم تعديل فاتورة البيع وإعادة احتساب FIFO بنجاح.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorizePermission('orders.delete', 'ليس لديك صلاحية لحذف فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        if ($order->is_deleted) {
            return back()->withErrors([
                'delete' => 'هذه الفاتورة محذوفة بالفعل.',
            ]);
        }

        DB::transaction(function () use ($order) {
            $this->restoreOrderQuantities($order);

            $order->update([
                'is_deleted' => true,
                'updated_by_user_id' => auth()->id(),
            ]);
        });

        return redirect('/orders')->with('success', 'تم حذف الفاتورة منطقيًا وإرجاع الكميات للمخزون.');
    }

    public function extractImage(Request $request)
    {
        $this->authorizePermission('orders.create', 'ليس لديك صلاحية لاستخدام الإدخال الذكي لفواتير البيع.');

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
            ->select('id', 'name', 'product_code', 'barcode', 'current_price')
            ->orderBy('name')
            ->get();

        $productsList = $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'product_code' => $product->product_code,
            'barcode' => $product->barcode,
            'current_price' => $product->current_price,
        ])->values()->toArray();

        $prompt = '
أنت مساعد متخصص في قراءة فواتير البيع من الصور.

المطلوب:
استخرج بيانات فاتورة بيع من الصورة، وأرجع JSON فقط بدون شرح وبدون Markdown.

تعليمات مهمة:
- لا تخترع بيانات غير موجودة.
- إذا المنتج أو الكمية أو السعر غير واضح، تجاهل البند.
- التاريخ بصيغة YYYY-MM-DD إن وجد.
- السعر هو سعر بيع الوحدة.
- حاول مطابقة المنتجات مع قائمة المنتجات الموجودة في النظام.
- لا ترجع أي نص خارج JSON.

قائمة المنتجات:
' . json_encode($productsList, JSON_UNESCAPED_UNICODE) . '

صيغة JSON المطلوبة:
{
  "order_number": "",
  "order_date": "",
  "customer_name": "",
  "discount_amount": 0,
  "items": [
    {
      "product_id": null,
      "product_name": "",
      "quantity": 1,
      "unit_price": 0,
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
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                if ($quantity <= 0 || $unitPrice < 0) {
                    return null;
                }

                return [
                    'product_id' => $matchedProduct?->id ?? '',
                    'product_name' => $productName,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
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
                'order_number' => $data['order_number'] ?? '',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'customer_name' => $data['customer_name'] ?? '',
                'discount_amount' => $data['discount_amount'] ?? 0,
                'items' => $draftItems,
            ],
        ]);
    }

    private function validateOrder(Request $request, ?int $orderId = null): array
    {
        $uniqueRule = 'unique:orders,order_number';

        if ($orderId) {
            $uniqueRule .= ',' . $orderId;
        }

        return $request->validate([
            'order_number' => ['required', 'string', 'max:255', $uniqueRule],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'order_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:paid,due,partial'],
            'status' => ['nullable', 'string', 'in:posted,draft,cancelled'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function createOrderWithFifo(array $data): Order
    {
        $warehouse = $this->resolveWarehouse($data['warehouse_id']);
        $customerId = $this->resolveCustomerId($data['customer_id'] ?? null);

        $totals = $this->calculateOrderTotals($data, $warehouse->id);

        $order = Order::create([
            'order_number' => trim($data['order_number']),
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'order_date' => $data['order_date'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_price' => $totals['total_price'],
            'total_cost' => 0,
            'total_profit' => 0,
            'status' => $data['status'] ?? 'posted',
            'payment_status' => $data['payment_status'],
            'journal_entry_id' => null,
            'user_id' => auth()->id(),
            'updated_by_user_id' => null,
            'is_deleted' => false,
            'notes' => $data['notes'] ?? null,
        ]);

        $fifoTotals = $this->createItemsAndConsumeFifo($order, $data['items'], $warehouse->id);

        $order->update([
            'total_cost' => $fifoTotals['total_cost'],
            'total_profit' => $order->total_price - $fifoTotals['total_cost'],
        ]);

        return $order;
    }

    private function updateOrderWithFifo(Order $order, array $data): void
    {
        $warehouse = $this->resolveWarehouse($data['warehouse_id']);
        $customerId = $this->resolveCustomerId($data['customer_id'] ?? null);

        $totals = $this->calculateOrderTotals($data, $warehouse->id);

        $order->update([
            'order_number' => trim($data['order_number']),
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'order_date' => $data['order_date'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_price' => $totals['total_price'],
            'status' => $data['status'] ?? 'posted',
            'payment_status' => $data['payment_status'],
            'updated_by_user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $fifoTotals = $this->createItemsAndConsumeFifo($order, $data['items'], $warehouse->id);

        $order->update([
            'total_cost' => $fifoTotals['total_cost'],
            'total_profit' => $order->total_price - $fifoTotals['total_cost'],
        ]);
    }

    private function createItemsAndConsumeFifo(Order $order, array $items, int $warehouseId): array
    {
        $totalCost = 0;

        foreach ($items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->firstOrFail();

            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $fifo = $this->consumeFifo($product->id, $warehouseId, $quantity);

            $lineRevenue = $quantity * $unitPrice;
            $lineCost = $fifo['total_cost'];
            $lineProfit = $lineRevenue - $lineCost;
            $averageCost = $quantity > 0 ? $lineCost / $quantity : 0;

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'cost_price' => $averageCost,
                'profit' => $lineProfit,
                'notes' => $item['notes'] ?? null,
            ]);

            foreach ($fifo['batches'] as $batchLine) {
                OrderItemBatch::create([
                    'order_item_id' => $orderItem->id,
                    'batch_id' => $batchLine['batch_id'],
                    'quantity_used' => $batchLine['quantity_used'],
                    'unit_cost' => $batchLine['unit_cost'],
                    'total_cost' => $batchLine['total_cost'],
                ]);
            }

            $totalCost += $lineCost;
        }

        return ['total_cost' => $totalCost];
    }

    private function consumeFifo(int $productId, int $warehouseId, float $requiredQuantity): array
    {
        $available = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');

        if ((float) $available < $requiredQuantity) {
            $product = Product::find($productId);

            abort(422, 'الكمية غير كافية للمنتج "' . ($product?->name ?? '') . '". المتوفر: ' . number_format((float) $available, 2));
        }

        $batches = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $requiredQuantity;
        $totalCost = 0;
        $usedBatches = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $batch->remaining_quantity, $remaining);
            $unitCost = (float) $batch->purchase_price;
            $lineCost = $take * $unitCost;

            $batch->update([
                'remaining_quantity' => (float) $batch->remaining_quantity - $take,
            ]);

            $usedBatches[] = [
                'batch_id' => $batch->id,
                'quantity_used' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $lineCost,
            ];

            $totalCost += $lineCost;
            $remaining -= $take;
        }

        return [
            'total_cost' => $totalCost,
            'batches' => $usedBatches,
        ];
    }

    private function restoreOrderQuantities(Order $order): void
    {
        $order->loadMissing('items.batches.batch');

        foreach ($order->items as $item) {
            foreach ($item->batches as $orderItemBatch) {
                $batch = InventoryBatch::where('id', $orderItemBatch->batch_id)
                    ->lockForUpdate()
                    ->first();

                if (!$batch) {
                    continue;
                }

                $batch->update([
                    'remaining_quantity' => (float) $batch->remaining_quantity + (float) $orderItemBatch->quantity_used,
                ]);
            }
        }
    }

    private function calculateOrderTotals(array $data, int $warehouseId): array
    {
        $subtotal = collect($data['items'])->sum(function ($item) {
            return (float) $item['quantity'] * (float) $item['unit_price'];
        });

        $discount = (float) ($data['discount_amount'] ?? 0);

        if ($discount > $subtotal) {
            abort(422, 'قيمة الخصم لا يمكن أن تكون أكبر من إجمالي الفاتورة.');
        }

        foreach ($data['items'] as $item) {
            $available = InventoryBatch::where('product_id', $item['product_id'])
                ->where('warehouse_id', $warehouseId)
                ->where('remaining_quantity', '>', 0)
                ->sum('remaining_quantity');

            if ((float) $available < (float) $item['quantity']) {
                $product = Product::find($item['product_id']);

                abort(422, 'الكمية غير كافية للمنتج "' . ($product?->name ?? '') . '". المتوفر: ' . number_format((float) $available, 2));
            }
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_price' => $subtotal - $discount,
        ];
    }

    private function resolveWarehouse(int $warehouseId): Warehouse
    {
        $warehouse = Warehouse::where('id', $warehouseId)
            ->where('is_active', true)
            ->firstOrFail();

        if (!$this->isAdmin() && (int) $warehouse->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية للبيع من هذا المخزن.');
        }

        return $warehouse;
    }

    private function resolveCustomerId(?int $customerId): ?int
    {
        if ($customerId) {
            $customer = Customer::where('id', $customerId)
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->firstOrFail();

            if (!$this->isAdmin() && $customer->branch_id && (int) $customer->branch_id !== (int) auth()->user()?->branch_id) {
                abort(403, 'ليس لديك صلاحية لاستخدام هذا العميل.');
            }

            return $customer->id;
        }

        $cashCustomer = Customer::where('code', 'CASH-CUST')
            ->where('is_deleted', false)
            ->first();

        return $cashCustomer?->id;
    }

    private function availableWarehouses()
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->when(!$this->isAdmin(), function ($query) {
                $query->where('branch_id', auth()->user()?->branch_id);
            })
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'code']);
    }

    private function availableCustomers()
    {
        return Customer::query()
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->when(!$this->isAdmin(), function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('branch_id')
                        ->orWhere('branch_id', auth()->user()?->branch_id);
                });
            })
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'code', 'phone']);
    }

    private function availableProducts()
    {
        return Product::query()
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'product_code', 'barcode', 'unit_name', 'current_price']);
    }

    private function ensureCanAccessOrder(Order $order): void
    {
        if ($order->is_deleted) {
            abort(404);
        }

        if (!$this->isAdmin() && (int) $order->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية للوصول لهذه الفاتورة.');
        }
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'SAL-' . now()->format('Ymd') . '-';

        $lastNumber = Order::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('order_number');

        $next = 1;

        if ($lastNumber) {
            $number = (int) str_replace($prefix, '', $lastNumber);
            $next = $number + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
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
<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBatch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ReceiptVoucher;
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
        return auth()->user()?->hasPermission('*') ?? false
            || optional(auth()->user()?->role)->code === 'admin';
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('orders.view', 'ليس لديك صلاحية لعرض فواتير البيع.');

        $search = trim((string) $request->get('search', ''));

        $query = Order::query()
            ->with([
                'customer:id,name,code,phone',
                'branch:id,name,code',
                'warehouse:id,name,code',
                'financialAccount:id,name,code',
                'paymentMethod:id,name,code',
            ])
            ->where('is_deleted', false);

        if (!$this->isAdmin()) {
            $query->where('branch_id', auth()->user()?->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%")
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
            'filters' => [
                'search' => $search,
            ],
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
            'financialAccounts' => $this->availableFinancialAccounts(),
            'paymentMethods' => $this->availablePaymentMethods(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('orders.create', 'ليس لديك صلاحية لإضافة فاتورة بيع.');

        $data = $this->validateOrder($request);

        DB::transaction(function () use ($data) {
            $this->createOrderWithFifoAndAccounting($data);
        });

        return redirect('/orders')->with('success', 'تم حفظ فاتورة البيع وتحديث المخزون وإنشاء القيود وإيصال القبض التلقائي بنجاح.');
    }

    public function show(Order $order): Response
    {
        $this->authorizePermission('orders.view', 'ليس لديك صلاحية لعرض فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        $order->load([
            'customer:id,name,code,phone,address,account_id',
            'branch:id,name,code',
            'warehouse:id,name,code',
            'financialAccount:id,name,code,type,account_id',
            'paymentMethod:id,name,code',
            'user:id,name',
            'journalEntry.lines.account:id,name,code',
            'items.product:id,name,product_code,unit_name',
            'items.batches.batch:id,purchase_invoice_id,entry_date,purchase_price',
        ]);

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'amountInWords' => $this->amountToArabicWords((float) $order->total_price),

            'receiptVouchers' => ReceiptVoucher::query()
                ->with([
                    'financialAccount:id,name',
                    'paymentMethod:id,name',
                    'createdBy:id,name',
                ])
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->latest('voucher_date')
                ->get(),
        ]);
    }

    public function edit(Order $order): Response
    {
        $this->authorizePermission('orders.update', 'ليس لديك صلاحية لتعديل فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        $order->load([
            'items.product',
            'customer',
            'warehouse',
            'financialAccount',
            'paymentMethod',
        ]);

        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'warehouses' => $this->availableWarehouses(),
            'customers' => $this->availableCustomers(),
            'products' => $this->availableProducts(),
            'financialAccounts' => $this->availableFinancialAccounts(),
            'paymentMethods' => $this->availablePaymentMethods(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorizePermission('orders.update', 'ليس لديك صلاحية لتعديل فواتير البيع.');
        $this->ensureCanAccessOrder($order);

        $data = $this->validateOrder($request, $order->id);

        DB::transaction(function () use ($data, $order) {
            $this->restoreOrderQuantities($order);
            $this->cancelAutoReceiptVouchersForOrder($order);

            OrderItemBatch::whereIn(
                'order_item_id',
                $order->items()->pluck('id')
            )->delete();

            $order->items()->delete();

            if ($order->journalEntry) {
                $order->journalEntry->lines()->delete();
                $order->journalEntry()->delete();
            }

            $this->updateOrderWithFifoAndAccounting($order, $data);
        });

        return redirect('/orders')->with('success', 'تم تعديل فاتورة البيع وإعادة احتساب FIFO والقيود وإيصال القبض التلقائي بنجاح.');
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

            if ($order->journalEntry) {
                $reverseEntry = JournalEntry::create([
                    'entry_number' => $this->generateJournalEntryNumber(),
                    'entry_date' => now(),
                    'branch_id' => $order->branch_id,
                    'description' => 'قيد عكسي لحذف فاتورة بيع رقم ' . $order->order_number,
                    'source_type' => Order::class,
                    'source_id' => $order->id,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'posted',
                ]);

                foreach ($order->journalEntry->lines as $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $reverseEntry->id,
                        'account_id' => $line->account_id,
                        'debit' => $line->credit,
                        'credit' => $line->debit,
                        'description' => 'عكس: ' . ($line->description ?? ''),
                        'customer_id' => $line->customer_id,
                        'supplier_id' => $line->supplier_id,
                        'employee_id' => $line->employee_id,
                        'partner_id' => $line->partner_id,
                    ]);
                }
            }

          $this->cancelAutoReceiptVouchersForOrder($order);
            $this->refreshOrderPaymentStatus($order);

            $order->update([
                'is_deleted' => true,
                'status' => 'cancelled',
                'updated_by_user_id' => auth()->id(),
            ]);
        });

        return redirect('/orders')->with('success', 'تم حذف الفاتورة منطقيًا وإرجاع الكميات وإنشاء القيد العكسي.');
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

        $customers = Customer::where('is_deleted', false)
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'phone')
            ->orderBy('name')
            ->get();

        $productsList = $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'product_code' => $product->product_code,
            'barcode' => $product->barcode,
            'current_price' => $product->current_price,
        ])->values()->toArray();

        $customersList = $customers->map(fn ($customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'code' => $customer->code,
            'phone' => $customer->phone,
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
- حاول مطابقة العميل مع قائمة العملاء إن كان الاسم أو الهاتف واضحًا.
- لا ترجع أي نص خارج JSON.

قائمة المنتجات:
' . json_encode($productsList, JSON_UNESCAPED_UNICODE) . '

قائمة العملاء:
' . json_encode($customersList, JSON_UNESCAPED_UNICODE) . '

صيغة JSON المطلوبة:
{
  "order_number": "",
  "order_date": "",
  "customer_id": null,
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

        $customerId = $data['customer_id'] ?? null;
        $customerName = trim((string) ($data['customer_name'] ?? ''));

        $matchedCustomer = null;

        if ($customerId) {
            $matchedCustomer = $customers->firstWhere('id', (int) $customerId);
        }

        if (!$matchedCustomer && $customerName !== '') {
            $matchedCustomer = $this->matchCustomerByName($customers, $customerName);
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
                    'unit_price' => $unitPrice > 0
                        ? $unitPrice
                        : (float) ($matchedProduct?->current_price ?? 0),
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
                'customer_id' => $matchedCustomer?->id ?? '',
                'customer_name' => $customerName,
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

        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:255', $uniqueRule],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'financial_account_id' => ['nullable', 'integer', 'exists:financial_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'order_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:paid,due,partial'],
            'status' => ['nullable', 'string', 'in:posted,draft,cancelled'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $subtotal = collect($data['items'])->sum(fn ($item) => (float) $item['quantity'] * (float) $item['unit_price']);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $total = $subtotal - $discount;
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        if ($discount > $subtotal) {
            abort(422, 'قيمة الخصم لا يمكن أن تكون أكبر من إجمالي الفاتورة.');
        }

        if ($paidAmount > $total) {
            abort(422, 'المبلغ المدفوع لا يمكن أن يكون أكبر من صافي الفاتورة.');
        }

        if ($data['payment_status'] === 'paid') {
            $data['paid_amount'] = $total;
        }

        if ($data['payment_status'] === 'due') {
            $data['paid_amount'] = 0;
            $data['financial_account_id'] = null;
            $data['payment_method_id'] = null;
        }

        if ($data['payment_status'] === 'partial' && $paidAmount <= 0) {
            abort(422, 'في حالة الدفع الجزئي يجب إدخال مبلغ مدفوع أكبر من صفر.');
        }

        if (in_array($data['payment_status'], ['paid', 'partial'], true)) {
            if (empty($data['financial_account_id'])) {
                abort(422, 'يجب اختيار الخزينة أو البنك عند وجود مبلغ مدفوع.');
            }

            if (empty($data['payment_method_id'])) {
                abort(422, 'يجب اختيار طريقة الدفع عند وجود مبلغ مدفوع.');
            }
        }

        return $data;
    }

    private function createOrderWithFifoAndAccounting(array $data): Order
    {
        $warehouse = $this->resolveWarehouse((int) $data['warehouse_id']);
        $customerId = $this->resolveCustomerId($data['customer_id'] ?? null);
        $customer = Customer::find($customerId);

        $financialAccount = null;

        if (!empty($data['financial_account_id'])) {
            $financialAccount = $this->resolveFinancialAccount((int) $data['financial_account_id']);
        }

        $totals = $this->calculateOrderTotals($data, $warehouse->id);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        $order = Order::create([
            'order_number' => trim($data['order_number']),
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'financial_account_id' => $financialAccount?->id,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'order_date' => $data['order_date'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_price' => $totals['total_price'],
            'total_cost' => 0,
            'total_profit' => 0,
            'paid_amount' => 0,
            'status' => $data['status'] ?? 'posted',
            'payment_status' => 'due',
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

        if ($order->status === 'posted') {
            $journalEntry = $this->createSalesJournalEntry($order, $customer);

            $order->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            if ($paidAmount > 0) {
                $this->createAutoReceiptVoucher(
                    order: $order,
                    customer: $customer,
                    financialAccount: $financialAccount,
                    paymentMethodId: $data['payment_method_id'] ?? null,
                    amount: $paidAmount,
                );
            }
        }

        return $order;
    }

    private function updateOrderWithFifoAndAccounting(Order $order, array $data): void
    {
        $warehouse = $this->resolveWarehouse((int) $data['warehouse_id']);
        $customerId = $this->resolveCustomerId($data['customer_id'] ?? null);
        $customer = Customer::find($customerId);

        $financialAccount = null;

        if (!empty($data['financial_account_id'])) {
            $financialAccount = $this->resolveFinancialAccount((int) $data['financial_account_id']);
        }

        $totals = $this->calculateOrderTotals($data, $warehouse->id);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        $order->update([
            'order_number' => trim($data['order_number']),
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'financial_account_id' => $financialAccount?->id,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'order_date' => $data['order_date'],
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_price' => $totals['total_price'],
            'status' => $data['status'] ?? 'posted',
            'payment_status' => 'due',
            'paid_amount' => 0,
            'updated_by_user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $fifoTotals = $this->createItemsAndConsumeFifo($order, $data['items'], $warehouse->id);

        $order->update([
            'total_cost' => $fifoTotals['total_cost'],
            'total_profit' => $order->total_price - $fifoTotals['total_cost'],
        ]);

        if ($order->status === 'posted') {
            $journalEntry = $this->createSalesJournalEntry($order, $customer);

            $order->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            if ($paidAmount > 0) {
                $this->createAutoReceiptVoucher(
                    order: $order,
                    customer: $customer,
                    financialAccount: $financialAccount,
                    paymentMethodId: $data['payment_method_id'] ?? null,
                    amount: $paidAmount,
                );
            } else {
                $this->refreshOrderPaymentStatus($order);
            }
        }
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

        return [
            'total_cost' => $totalCost,
        ];
    }

    private function createAutoReceiptVoucher(
        Order $order,
        ?Customer $customer,
        ?FinancialAccount $financialAccount,
        ?int $paymentMethodId,
        float $amount
    ): void {
        if ($amount <= 0) {
            return;
        }

        if (!$financialAccount) {
            abort(422, 'الخزينة أو البنك مطلوب لإنشاء إيصال قبض تلقائي.');
        }

        if (!$financialAccount->account_id) {
            abort(422, 'الخزينة أو البنك غير مربوط بحساب محاسبي.');
        }

        if (!$customer || !$customer->account_id) {
            abort(422, 'العميل غير مربوط بحساب محاسبي.');
        }

        $journalEntry = JournalEntry::create([
            'entry_number' => $this->generateJournalEntryNumber(),
            'entry_date' => $order->order_date,
            'branch_id' => $order->branch_id,
            'description' => 'قيد إيصال قبض تلقائي لفاتورة البيع رقم ' . $order->order_number,
            'source_type' => ReceiptVoucher::class,
            'source_id' => null,
            'created_by_user_id' => auth()->id(),
            'status' => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $financialAccount->account_id,
            'debit' => $amount,
            'credit' => 0,
            'description' => 'تحصيل نقدي تلقائي من فاتورة بيع رقم ' . $order->order_number,
            'customer_id' => $customer->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $customer->account_id,
            'debit' => 0,
            'credit' => $amount,
            'description' => 'تسوية ذمة العميل لفاتورة بيع رقم ' . $order->order_number,
            'customer_id' => $customer->id,
        ]);

        $voucher = ReceiptVoucher::create([
            'voucher_number' => $this->generateReceiptVoucherNumber(),
            'voucher_date' => $order->order_date,
            'branch_id' => $order->branch_id,
            'financial_account_id' => $financialAccount->id,
            'payment_method_id' => $paymentMethodId,
            'received_from_type' => 'customer',
            'received_from_id' => $customer->id,
            'partner_transaction_type' => null,
            'account_id' => $customer->account_id,
            'amount' => $amount,
            'description' => 'إيصال قبض تلقائي مرتبط بفاتورة البيع رقم ' . $order->order_number,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'journal_entry_id' => $journalEntry->id,
            'created_by_user_id' => auth()->id(),
            'status' => 'posted',
        ]);

        $journalEntry->update([
            'source_id' => $voucher->id,
        ]);

        $this->refreshOrderPaymentStatus($order);
    }

    private function createSalesJournalEntry(Order $order, ?Customer $customer): JournalEntry
    {
        $salesAccountId = $this->requiredAccountIdByCode('4100', 'حساب المبيعات غير موجود.');
        $cogsAccountId = $this->requiredAccountIdByCode('5000', 'حساب تكلفة البضاعة المباعة غير موجود.');
        $inventoryAccountId = $this->requiredAccountIdByCode('1140', 'حساب المخزون غير موجود.');

        if (!$customer || !$customer->account_id) {
            abort(422, 'العميل غير مربوط بحساب محاسبي.');
        }

        $journalEntry = JournalEntry::create([
            'entry_number' => $this->generateJournalEntryNumber(),
            'entry_date' => $order->order_date,
            'branch_id' => $order->branch_id,
            'description' => 'قيد فاتورة بيع رقم ' . $order->order_number,
            'source_type' => Order::class,
            'source_id' => $order->id,
            'created_by_user_id' => auth()->id(),
            'status' => 'posted',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $customer->account_id,
            'debit' => $order->total_price,
            'credit' => 0,
            'description' => 'استحقاق على العميل لفاتورة بيع رقم ' . $order->order_number,
            'customer_id' => $customer->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $salesAccountId,
            'debit' => 0,
            'credit' => $order->total_price,
            'description' => 'إيراد مبيعات فاتورة رقم ' . $order->order_number,
            'customer_id' => $customer->id,
        ]);

        if ((float) $order->total_cost > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cogsAccountId,
                'debit' => $order->total_cost,
                'credit' => 0,
                'description' => 'تكلفة بضاعة مباعة لفاتورة رقم ' . $order->order_number,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $inventoryAccountId,
                'debit' => 0,
                'credit' => $order->total_cost,
                'description' => 'خروج مخزون حسب FIFO لفاتورة رقم ' . $order->order_number,
            ]);
        }

        return $journalEntry;
    }

    private function refreshOrderPaymentStatus(Order $order): void
    {
        $order->refresh();

        $receiptsTotal = ReceiptVoucher::query()
            ->where('status', 'posted')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->sum('amount');

        $total = (float) $order->total_price;
        $paid = min((float) $receiptsTotal, $total);

        $paymentStatus = match (true) {
            $paid >= $total && $total > 0 => 'paid',
            $paid > 0 => 'partial',
            default => 'due',
        };

        $order->update([
            'paid_amount' => $paid,
            'payment_status' => $paymentStatus,
        ]);
    }

   private function cancelAutoReceiptVouchersForOrder(Order $order): void
{
    $autoReceipts = ReceiptVoucher::query()
        ->with('journalEntry.lines')
        ->where('reference_type', 'order')
        ->where('reference_id', $order->id)
        ->where('description', 'like', 'إيصال قبض تلقائي%')
        ->where('status', 'posted')
        ->get();

    foreach ($autoReceipts as $voucher) {

        /*
        |--------------------------------------------------------------------------
        | إنشاء قيد عكسي
        |--------------------------------------------------------------------------
        */

        if ($voucher->journalEntry) {

            $reverseEntry = JournalEntry::create([
                'entry_number' => $this->generateJournalEntryNumber(),
                'entry_date' => now(),
                'branch_id' => $voucher->branch_id,
                'description' => 'قيد عكسي لإلغاء إيصال القبض رقم ' . $voucher->voucher_number,
                'source_type' => ReceiptVoucher::class,
                'source_id' => $voucher->id,
                'created_by_user_id' => auth()->id(),
                'status' => 'posted',
            ]);

            foreach ($voucher->journalEntry->lines as $line) {

                JournalEntryLine::create([
                    'journal_entry_id' => $reverseEntry->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'عكس: ' . ($line->description ?? ''),
                    'customer_id' => $line->customer_id,
                    'supplier_id' => $line->supplier_id,
                    'employee_id' => $line->employee_id,
                    'partner_id' => $line->partner_id,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | إلغاء الإيصال بدل حذفه
        |--------------------------------------------------------------------------
        */

        $voucher->update([
            'status' => 'cancelled',
            'description' => ($voucher->description ?? '') . ' (تم الإلغاء تلقائيًا)',
        ]);
    }
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
        $order->loadMissing('items.batches.batch', 'journalEntry.lines');

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

    private function resolveFinancialAccount(int $financialAccountId): FinancialAccount
    {
        $financialAccount = FinancialAccount::query()
            ->where('id', $financialAccountId)
            ->where('is_active', true)
            ->firstOrFail();

        if (!$this->isAdmin() && (int) $financialAccount->branch_id !== (int) auth()->user()?->branch_id) {
            abort(403, 'ليس لديك صلاحية لاستخدام هذه الخزينة أو البنك.');
        }

        if (!$financialAccount->account_id) {
            abort(422, 'الخزينة أو البنك المختار غير مربوط بحساب محاسبي.');
        }

        return $financialAccount;
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
            ->get(['id', 'branch_id', 'name', 'code', 'phone', 'account_id']);
    }

    private function availableProducts()
    {
        return Product::query()
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'product_code', 'barcode', 'unit_name', 'current_price']);
    }

    private function availableFinancialAccounts()
    {
        return FinancialAccount::query()
            ->where('is_active', true)
            ->when(!$this->isAdmin(), function ($query) {
                $query->where('branch_id', auth()->user()?->branch_id);
            })
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'code', 'type', 'account_id']);
    }

    private function availablePaymentMethods()
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
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

    private function requiredAccountIdByCode(string $code, string $message): int
    {
        $accountId = Account::query()
            ->where('code', $code)
            ->value('id');

        if (!$accountId) {
            abort(422, $message);
        }

        return (int) $accountId;
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

    private function generateReceiptVoucherNumber(): string
    {
        $prefix = 'RV-' . now()->format('Ymd') . '-';
        $lastId = ((int) ReceiptVoucher::query()->max('id')) + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function generateJournalEntryNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ymd') . '-';
        $lastId = ((int) JournalEntry::query()->max('id')) + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
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

    private function matchCustomerByName($customers, string $name): ?Customer
    {
        $normalizedName = $this->normalizeText($name);

        return $customers->first(function ($customer) use ($normalizedName) {
            $customerName = $this->normalizeText($customer->name);
            $customerCode = $this->normalizeText((string) $customer->code);
            $phone = $this->normalizeText((string) $customer->phone);

            return $customerName === $normalizedName
                || str_contains($customerName, $normalizedName)
                || str_contains($normalizedName, $customerName)
                || ($customerCode !== '' && str_contains($normalizedName, $customerCode))
                || ($phone !== '' && str_contains($normalizedName, $phone));
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

    private function amountToArabicWords(float $amount): string
    {
        $dinars = (int) floor($amount);
        $dirhams = (int) round(($amount - $dinars) * 100);

        $words = 'فقط ' . $this->numberToArabicWords($dinars) . ' دينار';

        if ($dirhams > 0) {
            $words .= ' و' . $this->numberToArabicWords($dirhams) . ' درهم';
        }

        return $words . ' لا غير';
    }

    private function numberToArabicWords(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        $ones = [
            0 => '',
            1 => 'واحد',
            2 => 'اثنان',
            3 => 'ثلاثة',
            4 => 'أربعة',
            5 => 'خمسة',
            6 => 'ستة',
            7 => 'سبعة',
            8 => 'ثمانية',
            9 => 'تسعة',
            10 => 'عشرة',
            11 => 'أحد عشر',
            12 => 'اثنا عشر',
            13 => 'ثلاثة عشر',
            14 => 'أربعة عشر',
            15 => 'خمسة عشر',
            16 => 'ستة عشر',
            17 => 'سبعة عشر',
            18 => 'ثمانية عشر',
            19 => 'تسعة عشر',
        ];

        $tens = [
            20 => 'عشرون',
            30 => 'ثلاثون',
            40 => 'أربعون',
            50 => 'خمسون',
            60 => 'ستون',
            70 => 'سبعون',
            80 => 'ثمانون',
            90 => 'تسعون',
        ];

        $hundreds = [
            100 => 'مائة',
            200 => 'مائتان',
            300 => 'ثلاثمائة',
            400 => 'أربعمائة',
            500 => 'خمسمائة',
            600 => 'ستمائة',
            700 => 'سبعمائة',
            800 => 'ثمانمائة',
            900 => 'تسعمائة',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $unit = $number % 10;
            $ten = $number - $unit;

            return $unit ? $ones[$unit] . ' و' . $tens[$ten] : $tens[$ten];
        }

        if ($number < 1000) {
            $hundred = intdiv($number, 100) * 100;
            $rest = $number % 100;

            return $rest ? $hundreds[$hundred] . ' و' . $this->numberToArabicWords($rest) : $hundreds[$hundred];
        }

        if ($number < 1000000) {
            $thousands = intdiv($number, 1000);
            $rest = $number % 1000;

            $thousandText = match (true) {
                $thousands === 1 => 'ألف',
                $thousands === 2 => 'ألفان',
                $thousands >= 3 && $thousands <= 10 => $this->numberToArabicWords($thousands) . ' آلاف',
                default => $this->numberToArabicWords($thousands) . ' ألف',
            };

            return $rest ? $thousandText . ' و' . $this->numberToArabicWords($rest) : $thousandText;
        }

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;

        $millionText = match (true) {
            $millions === 1 => 'مليون',
            $millions === 2 => 'مليونان',
            $millions >= 3 && $millions <= 10 => $this->numberToArabicWords($millions) . ' ملايين',
            default => $this->numberToArabicWords($millions) . ' مليون',
        };

        return $rest ? $millionText . ' و' . $this->numberToArabicWords($rest) : $millionText;
    }
}
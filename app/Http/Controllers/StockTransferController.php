<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user?->hasPermission('*') === true
            || optional($user?->role)->code === 'admin';
    }

    private function accessibleWarehousesQuery()
    {
        $user = auth()->user();

        return Warehouse::query()
            ->with('branch:id,name')
            ->where('is_active', true)
            ->when(!$this->isAdmin(), function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id);
            });
    }

    private function ensureCanAccessWarehouse(Warehouse $warehouse): void
    {
        $user = auth()->user();

        if ($this->isAdmin()) {
            return;
        }

        if ((int) $warehouse->branch_id !== (int) $user->branch_id) {
            abort(403, 'ليس لديك صلاحية للوصول لهذا المخزن.');
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('warehouses.transfer', 'ليس لديك صلاحية لعرض تحويلات المخزون.');

        $user = auth()->user();
        $search = trim((string) $request->get('search', ''));

        $query = StockTransfer::query()
            ->with([
                'branch:id,name',
                'fromWarehouse:id,name,code,branch_id',
                'toWarehouse:id,name,code,branch_id',
                'user:id,name',
            ])
            ->withCount('items')
            ->latest('id');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('fromWarehouse', function ($warehouseQuery) use ($search) {
                        $warehouseQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('toWarehouse', function ($warehouseQuery) use ($search) {
                        $warehouseQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $transfers = $query
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $transfers,
            'filters' => [
                'search' => $search,
            ],
            'permissions' => [
                'canCreate' => auth()->user()?->hasPermission('warehouses.transfer') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorizePermission('warehouses.transfer', 'ليس لديك صلاحية لإضافة تحويل مخزني.');

        $warehouses = $this->accessibleWarehousesQuery()
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'code', 'type']);

        $warehouseIds = $warehouses->pluck('id')->values();

        $warehouseProductStocks = InventoryBatch::query()
            ->join('products', 'products.id', '=', 'inventory_batches.product_id')
            ->whereIn('inventory_batches.warehouse_id', $warehouseIds)
            ->where('inventory_batches.remaining_quantity', '>', 0)
            ->where('products.is_deleted', false)
            ->select([
                'inventory_batches.warehouse_id',
                'inventory_batches.product_id',
                'products.name as product_name',
                'products.product_code',
                'products.barcode',
                'products.unit_name',
            ])
            ->selectRaw('SUM(inventory_batches.remaining_quantity) as available_quantity')
            ->groupBy(
                'inventory_batches.warehouse_id',
                'inventory_batches.product_id',
                'products.name',
                'products.product_code',
                'products.barcode',
                'products.unit_name'
            )
            ->orderBy('products.name')
            ->get();

        return Inertia::render('StockTransfers/Create', [
            'warehouses' => $warehouses,
            'warehouseProductStocks' => $warehouseProductStocks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('warehouses.transfer', 'ليس لديك صلاحية لإضافة تحويل مخزني.');

        $data = $request->validate([
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string'],
        ], [
            'to_warehouse_id.different' => 'لا يمكن التحويل من المخزن إلى نفس المخزن.',
            'items.required' => 'يجب إضافة منتج واحد على الأقل للتحويل.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر.',
        ]);

        $fromWarehouse = Warehouse::query()->findOrFail($data['from_warehouse_id']);
        $toWarehouse = Warehouse::query()->findOrFail($data['to_warehouse_id']);

        $this->ensureCanAccessWarehouse($fromWarehouse);
        $this->ensureCanAccessWarehouse($toWarehouse);

        if (!$fromWarehouse->is_active || !$toWarehouse->is_active) {
            return back()->withErrors([
                'warehouse' => 'لا يمكن التحويل من أو إلى مخزن غير فعال.',
            ])->withInput();
        }

        $mergedItems = $this->mergeTransferItems($data['items']);

        return DB::transaction(function () use ($data, $mergedItems, $fromWarehouse, $toWarehouse) {
            $transfer = StockTransfer::create([
                'transfer_number' => $this->generateTransferNumber(),
                'branch_id' => $fromWarehouse->branch_id,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'transfer_date' => $data['transfer_date'],
                'status' => 'posted',
                'user_id' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($mergedItems as $item) {
                $this->moveProductUsingFifo(
                    transfer: $transfer,
                    fromWarehouse: $fromWarehouse,
                    toWarehouse: $toWarehouse,
                    productId: (int) $item['product_id'],
                    quantity: (float) $item['quantity'],
                    notes: $item['notes'] ?? null,
                    transferDate: $data['transfer_date']
                );
            }

            return redirect()
                ->route('stock-transfers.show', $transfer)
                ->with('success', 'تم تنفيذ التحويل بين المخازن بنجاح.');
        });
    }

    public function show(StockTransfer $stockTransfer): Response
    {
        $this->authorizePermission('warehouses.transfer', 'ليس لديك صلاحية لعرض التحويل المخزني.');

        if (!$this->isAdmin() && (int) $stockTransfer->branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'ليس لديك صلاحية لعرض هذا التحويل.');
        }

        $stockTransfer->load([
            'branch:id,name',
            'fromWarehouse:id,name,code,branch_id',
            'toWarehouse:id,name,code,branch_id',
            'user:id,name',
            'items.product:id,name,product_code,barcode,unit_name',
        ]);

        return Inertia::render('StockTransfers/Show', [
            'transfer' => $stockTransfer,
        ]);
    }

    private function mergeTransferItems(array $items): array
    {
        $merged = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (float) $item['quantity'];

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            if (!isset($merged[$productId])) {
                $merged[$productId] = [
                    'product_id' => $productId,
                    'quantity' => 0,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $merged[$productId]['quantity'] += $quantity;

            if (!empty($item['notes'])) {
                $merged[$productId]['notes'] = $item['notes'];
            }
        }

        return array_values($merged);
    }

    private function moveProductUsingFifo(
        StockTransfer $transfer,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        int $productId,
        float $quantity,
        ?string $notes,
        string $transferDate
    ): void {
        $availableQuantity = (float) InventoryBatch::query()
            ->where('warehouse_id', $fromWarehouse->id)
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');

        if ($availableQuantity < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'الكمية المطلوبة للمنتج رقم ' . $productId . ' أكبر من الكمية المتاحة في المخزن المصدر.',
            ]);
        }

        $batches = InventoryBatch::query()
            ->where('warehouse_id', $fromWarehouse->id)
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remainingToMove = $quantity;
        $totalCost = 0;

        foreach ($batches as $batch) {
            if ($remainingToMove <= 0) {
                break;
            }

            $batchRemaining = (float) $batch->remaining_quantity;
            $takenQuantity = min($remainingToMove, $batchRemaining);

            $unitCost = (float) ($batch->purchase_price ?? $batch->base_price ?? 0);
            $totalCost += $takenQuantity * $unitCost;

            $batch->update([
                'remaining_quantity' => $batchRemaining - $takenQuantity,
            ]);

            InventoryBatch::create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouse->id,
                'purchase_invoice_id' => $batch->purchase_invoice_id ?? null,
                'quantity' => $takenQuantity,
                'remaining_quantity' => $takenQuantity,
                'base_price' => $batch->base_price ?? $unitCost,
                'purchase_price' => $batch->purchase_price ?? $unitCost,
                'entry_date' => $transferDate,
            ]);

            $remainingToMove -= $takenQuantity;
        }

        if ($remainingToMove > 0) {
            throw ValidationException::withMessages([
                'items' => 'تعذر إكمال التحويل بسبب نقص في دفعات المخزون.',
            ]);
        }

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
            'total_cost' => $totalCost,
            'notes' => $notes,
        ]);
    }

    private function generateTransferNumber(): string
    {
        $prefix = 'ST-' . now()->format('Ymd') . '-';

        $nextId = ((int) StockTransfer::query()->max('id')) + 1;

        return $prefix . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
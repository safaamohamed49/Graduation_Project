<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user?->hasPermission('*') === true
            || optional($user?->role)->code === 'admin';
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

    public function index(Request $request)
    {
        $user = auth()->user();
        $search = trim((string) $request->get('search', ''));

        $query = Warehouse::query()
            ->with('branch')
            ->withCount([
                'inventoryBatches as products_count' => function ($q) {
                    $q->where('remaining_quantity', '>', 0)
                        ->select(DB::raw('COUNT(DISTINCT product_id)'));
                },
            ])
            ->withSum('inventoryBatches as total_quantity', 'remaining_quantity');

        if (!$this->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $warehouses = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
            ],
            'canManageWarehouses' => $this->isAdmin(),
        ]);
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            abort(403, 'ليس لديك صلاحية لإضافة مخزن.');
        }

        return Inertia::render('Warehouses/Create', [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403, 'ليس لديك صلاحية لإضافة مخزن.');
        }

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:warehouses,code'],
            'type' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        Warehouse::create($data);

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'تمت إضافة المخزن بنجاح.');
    }

    public function show(Warehouse $warehouse)
    {
        $this->ensureCanAccessWarehouse($warehouse);

        $warehouse->load('branch');

        $products = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.product_code',
                'products.barcode',
                'products.unit_name',
                'products.current_price',
                'products.minimum_stock',
            ])
            ->join('inventory_batches', 'inventory_batches.product_id', '=', 'products.id')
            ->where('inventory_batches.warehouse_id', $warehouse->id)
            ->where('products.is_deleted', false)
            ->groupBy(
                'products.id',
                'products.name',
                'products.product_code',
                'products.barcode',
                'products.unit_name',
                'products.current_price',
                'products.minimum_stock'
            )
            ->selectRaw('COALESCE(SUM(inventory_batches.remaining_quantity), 0) as stock_quantity')
            ->orderBy('products.name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Warehouses/Show', [
            'warehouse' => $warehouse,
            'products' => $products,
            'canManageWarehouses' => $this->isAdmin(),
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        if (!$this->isAdmin()) {
            abort(403, 'ليس لديك صلاحية لتعديل المخزن.');
        }

        $warehouse->load('branch');

        return Inertia::render('Warehouses/Edit', [
            'warehouse' => $warehouse,
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        if (!$this->isAdmin()) {
            abort(403, 'ليس لديك صلاحية لتعديل المخزن.');
        }

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:warehouses,code,' . $warehouse->id],
            'type' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $warehouse->update($data);

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'تم تعديل المخزن بنجاح.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if (!$this->isAdmin()) {
            abort(403, 'ليس لديك صلاحية لحذف المخزن.');
        }

        $hasMovements =
            $warehouse->inventoryBatches()->exists()
            || $warehouse->purchaseInvoices()->exists()
            || $warehouse->returnInvoices()->exists()
            || $warehouse->outgoingTransfers()->exists()
            || $warehouse->incomingTransfers()->exists();

        if ($hasMovements) {
            return back()->withErrors([
                'warehouse' => 'لا يمكن حذف هذا المخزن لأنه مرتبط بحركات أو فواتير.',
            ]);
        }

        $warehouse->delete();

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'تم حذف المخزن بنجاح.');
    }
}
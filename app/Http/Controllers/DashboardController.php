<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Order;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'customers_count' => Customer::where('is_deleted', 0)->count(),
            'suppliers_count' => Supplier::where('is_deleted', 0)->count(),
            'products_count' => Product::where('is_deleted', 0)->count(),
            'orders_count' => Order::where('is_deleted', 0)->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Table;
use Inertia\Inertia;
use Inertia\Response;

class OrdersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Orders/Index');
    }

    public function create(): Response
    {
        $menuItems = MenuItem::with(['category', 'addons'])
            ->where('available', true)
            ->where('status', 'published')
            ->get();

        $tables = Table::where('status', '!=', 'maintenance')
            ->orderBy('number')
            ->get();

        return Inertia::render('Admin/Orders/Create', [
            'menuItems' => $menuItems,
            'tables' => $tables,
        ]);
    }

    public function edit(int $id): Response
    {
        $order = Order::with('table:id,number,location')->findOrFail($id);

        return Inertia::render('Admin/Orders/Edit', [
            'order' => $order,
            'id' => $id,
        ]);
    }
}

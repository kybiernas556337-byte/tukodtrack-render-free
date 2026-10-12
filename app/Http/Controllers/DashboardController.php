<?php
namespace App\Http\Controllers;

use App\Models\{Product, Purchase, Sale};

class DashboardController extends Controller
{
    public function index()
    {
        $today = Sale::where('status', 'completed')->whereDate('sale_date', today());
        return view('overview', [
            'todaySales' => (clone $today)->sum('total_amount'),
            'todayCount' => (clone $today)->count(),
            'lowStock' => Product::active()->low()->orderBy('name')->limit(8)->get(),
            'lowCount' => Product::active()->low()->count(),
            'productCount' => Product::active()->count(),
            'pending' => Purchase::whereIn('status', ['pending', 'partial'])->count(),
            'recent' => Sale::with('cashier')->latest('sale_date')->limit(5)->get(),
        ]);
    }
}

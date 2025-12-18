<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $featuredItems = MenuItem::where('featured', true)
            ->limit(6)
            ->get(['name', 'description', 'price', 'image_path']);
            
        return Inertia::render('Customer/Home/Index', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'featuredItems' => $featuredItems,
            'tableId' => session('table_id'),
            'tableNumber' => session('table_number'),
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', ['categories' => Category::withCount('components')->orderBy('name')->get()]);
    }
}

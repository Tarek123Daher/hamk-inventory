<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $category = Category::create($data);
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'category.created',
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'description' => 'Created category '.$category->name,
        ]);

        return back()->with('success', 'Category created.');
    }
}

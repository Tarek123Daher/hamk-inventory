<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Kit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();
        $components = Component::with(['category', 'kits'])
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', '%'.$term.'%')
                        ->orWhere('sku', 'like', '%'.$term.'%')
                        ->orWhere('model', 'like', '%'.$term.'%')
                        ->orWhere('manufacturer', 'like', '%'.$term.'%')
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $kits = Kit::with(['components' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('student-portal.index', compact('components', 'kits', 'term'));
    }
}

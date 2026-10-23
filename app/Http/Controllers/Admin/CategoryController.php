<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController
{
    public function index()
    {
        $categories = Category::query()
            ->withCount('reports')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], $this->messages());

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'category-created');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], $this->messages());

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'category-updated');
    }

    public function destroy(Category $category)
    {
        $reportCount = Report::query()->where('categories_id', $category->getKey())->count();

        // categories_id is nullable, so this is a product decision rather than a
        // hard constraint: keep history intact by refusing the delete.
        if ($reportCount > 0) {
            throw ValidationException::withMessages([
                'category' => 'Kategori ini masih digunakan oleh '.$reportCount.' laporan sehingga tidak dapat dihapus.',
            ]);
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'category-deleted');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Masukkan nama kategori.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $categories = Category::query()->paginate($perPage);

        return response()->json([
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $category = Category::query()->create($data);

        return response()->json(['data' => $category], 201);
    }

    public function show($id)
    {
        $category = Category::query()->findOrFail($id);

        return response()->json(['data' => $category]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate($this->rules(forUpdate: true, id: $category->id));

        $category->update($data);

        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy($id)
    {
        Category::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $forUpdate = false, ?int $id = null): array
    {
        $required = $forUpdate ? 'sometimes' : 'required';

        $slugRule = Rule::unique('categories', 'slug');
        if ($forUpdate && $id !== null) {
            $slugRule = $slugRule->ignore($id);
        }

        return [
            'name' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:255', $slugRule],
            'description' => [$required, 'string', 'max:2048'],
            'icon' => [$required, 'string', 'max:255'],
            'title' => [$required, 'string', 'max:255'],
        ];
    }
}


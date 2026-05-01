<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;

/**
 * Danh sách thể loại / quốc gia lưu trong DB (sau crawl hoặc CRUD admin).
 */
class TaxonomyController extends Controller
{
    public function genres(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 100), 500));
        $paginator = Category::query()->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function countries(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 100), 500));
        $paginator = Country::query()->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

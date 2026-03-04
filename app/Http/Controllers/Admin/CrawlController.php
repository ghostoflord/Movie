<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\CrawlMoviesJob;
use Illuminate\Support\Facades\Cache;

class CrawlController extends Controller
{
    public function start(Request $request)
    {
        $request->validate(['pages' => 'integer|min:1|max:20']);

        $pages = $request->input('pages', 3);

        // Dispatch job
        CrawlMoviesJob::dispatch($pages);

        // Lưu trạng thái vào cache (có thể dùng database hoặc Redis)
        Cache::put('crawl_status', [
            'status' => 'processing',
            'started_at' => now(),
            'pages' => $pages,
        ], 3600);

        return response()->json(['message' => 'Crawl started']);
    }

    public function status()
    {
        $status = Cache::get('crawl_status', ['status' => 'idle']);
        // Có thể bổ sung thêm thông tin từ log
        return response()->json($status);
    }
}

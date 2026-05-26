<?php

namespace App\Http\Controllers;

use App\Http\Resources\WatchHistoryResource;
use App\Models\WatchHistory;
use Illuminate\Http\Request;

class WatchHistoryController extends Controller
{
    /**
     * GET /api/watch-history/continue
     * Danh sách "tiếp tục xem" — mỗi phim 1 dòng, tập xem gần nhất (cho trang lịch sử / homepage).
     */
    public function continueWatching(Request $request)
    {
        $request->merge(['group_by' => 'movie']);

        return $this->index($request);
    }

    /**
     * GET /api/watch-history
     * Danh sách lịch sử xem của user đang đăng nhập.
     *
     * Query: per_page, page, movie_id, group_by=movie (mỗi phim 1 bản ghi mới nhất — dùng cho "Tiếp tục xem").
     */
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'movie_id' => 'nullable|exists:movies,id',
            'group_by' => 'nullable|in:movie',
        ]);

        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $userId = $request->user()->id;

        $query = WatchHistory::query()
            ->where('user_id', $userId)
            ->with(['episode.movie'])
            ->orderByDesc('last_watched_at');

        if ($request->filled('movie_id')) {
            $movieId = $request->query('movie_id');
            $query->whereHas('episode', fn ($q) => $q->where('movie_id', $movieId));
        }

        if ($request->query('group_by') === 'movie') {
            $latestIds = WatchHistory::query()
                ->join('episodes', 'episodes.id', '=', 'watch_history.episode_id')
                ->where('watch_history.user_id', $userId)
                ->when($request->filled('movie_id'), function ($q) use ($request) {
                    $q->where('episodes.movie_id', $request->query('movie_id'));
                })
                ->selectRaw('MAX(watch_history.id) as id')
                ->groupBy('episodes.movie_id')
                ->pluck('id');

            $query->whereIn('watch_history.id', $latestIds);
        }

        $histories = $query->paginate($perPage);

        return response()->json([
            'data' => WatchHistoryResource::collection($histories->items()),
            'meta' => [
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'from' => $histories->firstItem(),
                'to' => $histories->lastItem(),
            ],
        ]);
    }

    /**
     * GET /api/watch-history/{id}
     */
    public function show(Request $request, $id)
    {
        $history = $this->findUserHistory($request, $id);

        return response()->json([
            'data' => new WatchHistoryResource($history->load(['episode.movie'])),
        ]);
    }

    /**
     * GET /api/watch-history/episode/{episodeId}
     * Lấy tiến độ tập đang xem (resume player). Trả 404 nếu chưa có lịch sử.
     */
    public function showByEpisode(Request $request, $episodeId)
    {
        $history = WatchHistory::query()
            ->where('user_id', $request->user()->id)
            ->where('episode_id', $episodeId)
            ->with(['episode.movie'])
            ->first();

        if (! $history) {
            return response()->json([
                'data' => null,
                'message' => 'Chưa có lịch sử xem cho tập này.',
            ], 404);
        }

        return response()->json([
            'data' => new WatchHistoryResource($history),
        ]);
    }

    /**
     * GET /api/watch-history/movie/{movieId}
     * Tiến độ mới nhất của phim (tập xem gần nhất).
     */
    public function showByMovie(Request $request, $movieId)
    {
        $history = WatchHistory::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('episode', fn ($q) => $q->where('movie_id', $movieId))
            ->with(['episode.movie'])
            ->orderByDesc('last_watched_at')
            ->first();

        if (! $history) {
            return response()->json([
                'data' => null,
                'message' => 'Chưa có lịch sử xem cho phim này.',
            ], 404);
        }

        return response()->json([
            'data' => new WatchHistoryResource($history),
        ]);
    }

    /**
     * POST /api/watch-history
     * Lưu / cập nhật tiến độ (upsert theo user + episode).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'current_time' => 'required|numeric|min:0',
            'duration_watched' => 'nullable|numeric|min:0',
        ]);

        $history = WatchHistory::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'episode_id' => $data['episode_id'],
            ],
            $this->progressAttributes($data)
        );

        $history->load(['episode.movie']);

        return response()->json([
            'data' => new WatchHistoryResource($history),
        ], $history->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * PUT /api/watch-history/{id}
     */
    public function update(Request $request, $id)
    {
        $history = $this->findUserHistory($request, $id);

        $data = $request->validate([
            'current_time' => 'sometimes|numeric|min:0',
            'duration_watched' => 'sometimes|nullable|numeric|min:0',
        ]);

        $history->update($this->progressAttributes($data, $history));

        return response()->json([
            'data' => new WatchHistoryResource($history->fresh()->load(['episode.movie'])),
        ]);
    }

    /**
     * DELETE /api/watch-history/{id}
     */
    public function destroy(Request $request, $id)
    {
        $this->findUserHistory($request, $id)->delete();

        return response()->json(['message' => 'Đã xóa lịch sử xem.']);
    }

    /**
     * DELETE /api/watch-history/movie/{movieId}
     * Xóa toàn bộ lịch sử các tập của một phim.
     */
    public function destroyByMovie(Request $request, $movieId)
    {
        $deleted = WatchHistory::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('episode', fn ($q) => $q->where('movie_id', $movieId))
            ->delete();

        return response()->json([
            'message' => 'Đã xóa lịch sử xem của phim.',
            'deleted' => $deleted,
        ]);
    }

    /**
     * DELETE /api/watch-history/clear
     * Xóa toàn bộ lịch sử xem của user.
     */
    public function clear(Request $request)
    {
        $deleted = WatchHistory::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => 'Đã xóa toàn bộ lịch sử xem.',
            'deleted' => $deleted,
        ]);
    }

    private function findUserHistory(Request $request, $id): WatchHistory
    {
        return WatchHistory::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function progressAttributes(array $data, ?WatchHistory $existing = null): array
    {
        $attrs = ['last_watched_at' => now()];

        if (array_key_exists('current_time', $data)) {
            $attrs['current_time'] = (int) $data['current_time'];
        } elseif ($existing) {
            $attrs['current_time'] = $existing->current_time;
        }

        if (array_key_exists('duration_watched', $data) && $data['duration_watched'] !== null && $data['duration_watched'] !== '') {
            $attrs['duration_watched'] = (int) $data['duration_watched'];
        } elseif (array_key_exists('duration_watched', $data)) {
            $attrs['duration_watched'] = 0;
        } elseif (! $existing) {
            $attrs['duration_watched'] = 0;
        }

        return $attrs;
    }
}

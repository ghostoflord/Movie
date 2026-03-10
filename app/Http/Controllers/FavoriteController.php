<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * GET /api/favorites
     * Lấy danh sách phim đã thích
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Lấy danh sách phim kèm theo thông tin trong bảng trung gian (nếu cần)
        $favorites = $user->favoriteMovies()
            ->select('movies.id', 'movies.name', 'movies.slug', 'movies.thumb_url')
            ->orderBy('favorites.created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $favorites
        ]);
    }

    /**
     * POST /api/favorites/{movieId}
     * Thêm hoặc xóa phim khỏi danh sách yêu thích
     */
    public function toggle($movieId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $isFavorite = $user->favoriteMovies()->where('movie_id', $movieId)->exists();

        if ($isFavorite) {
            $user->favoriteMovies()->detach($movieId);
            return response()->json(['message' => 'Removed from favorites']);
        }

        $user->favoriteMovies()->attach($movieId);
        return response()->json(['message' => 'Added to favorites']);
    }

    /**
     * PUT /api/favorites/{movieId}
     * Cập nhật thông tin bổ sung cho phim đã thích (Ví dụ: Ghi chú)
     * Lưu ý: Bạn cần thêm cột 'notes' vào migration bảng favorites nếu muốn dùng
     */
    public function update(Request $request, $movieId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Kiểm tra xem phim có trong danh sách yêu thích không
        $exists = $user->favoriteMovies()->where('movie_id', $movieId)->exists();

        if (!$exists) {
            return response()->json(['message' => 'Movie not in your favorites'], 404);
        }

        // Cập nhật dữ liệu ở bảng trung gian (pivot table)
        // Ví dụ bạn muốn update thời gian hoặc một field custom nào đó
        $user->favoriteMovies()->updateExistingPivot($movieId, [
            'updated_at' => now(),
            // 'notes' => $request->notes, // Nếu bạn có cột notes
        ]);

        return response()->json(['message' => 'Favorite updated successfully']);
    }

    /**
     * GET /api/favorites/user/{userId}
     * Lấy danh sách phim yêu thích của một người dùng bất kỳ (Public Profile)
     */
    public function getByUserId($userId)
    {
        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $favorites = $targetUser->favoriteMovies()
            ->select('movies.id', 'movies.name', 'movies.slug', 'movies.thumb_url')
            ->latest('favorites.created_at')
            ->get();

        return response()->json([
            'user_name' => $targetUser->name,
            'data' => $favorites
        ]);
    }
}

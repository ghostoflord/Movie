<?php

use App\Http\Controllers\Admin\CrawlController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ActorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MovieCategoryBrowseController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\MovieActorController;
use App\Http\Controllers\OphimCatalogController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\WatchHistoryController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']); // đăng nhập -> trả Bearer token
Route::post('/login/google', [AuthController::class, 'loginGoogle']); // đăng nhập google (FE gửi id_token)
Route::post('/register', [AuthController::class, 'register']); // đăng ký (mặc định inactive, cần verify email)

Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify'); // verify email (signed URL)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // quên mật khẩu: xin OTP
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']); // quên mật khẩu: xác thực OTP
Route::post('/reset-password', [AuthController::class, 'resetPassword']); // quên mật khẩu: đặt lại mật khẩu

Route::get('/favorites/user/{userId}', [FavoriteController::class, 'getByUserId']); // favorites theo userId (public)

Route::get('/episodes', [EpisodeController::class, 'index']); // list episodes (public)
Route::post('/episodes', [EpisodeController::class, 'store']); // tạo episode (public)
Route::get('/episodes/{id}', [EpisodeController::class, 'show']); // chi tiết episode (public)
Route::put('/episodes/{id}', [EpisodeController::class, 'update']); // cập nhật episode (public)
Route::delete('/episodes/{id}', [EpisodeController::class, 'destroy']); // xoá episode (public)
Route::get('/movies/{movieId}/episodes', [EpisodeController::class, 'getByMovie']); // episodes theo movie (public)

Route::apiResource('movies', MovieController::class); // CRUD movies (public)

Route::get('/movie-categories', [MovieCategoryBrowseController::class, 'index']); // thể loại từ JSON phim (unique + đếm phim)
Route::get('/movie-categories/movies', [MovieCategoryBrowseController::class, 'movies']); // phim theo thể loại (?category=...) + episodes

// Catalog OPhim (thể loại / quốc gia + danh sách phim theo slug) — cho modal/filter FE
Route::get('/ophim/the-loai', [OphimCatalogController::class, 'theLoai']);
Route::get('/ophim/quoc-gia', [OphimCatalogController::class, 'quocGia']);
Route::get('/ophim/the-loai/{slug}', [OphimCatalogController::class, 'theLoaiBySlug']);
Route::get('/ophim/quoc-gia/{slug}', [OphimCatalogController::class, 'quocGiaBySlug']);

// Thể loại / quốc gia đã lưu DB (bảng categories, countries + pivot)
Route::get('/taxonomy/genres', [TaxonomyController::class, 'genres']);
Route::get('/taxonomy/countries', [TaxonomyController::class, 'countries']);

Route::middleware('auth:sanctum')->group(function () { // require Authorization: Bearer <token>
    Route::get('/user', [AuthController::class, 'user']); // lấy thông tin user hiện tại
    Route::post('/logout', [AuthController::class, 'logout']); // logout (xóa token)

    // Upload multipart qua PUT hay bị PHP bỏ qua body. Cho phép POST /users/{id} để update (FE dùng FormData chuyên nghiệp hơn, không cần _method).
    Route::post('users/{id}', [UserController::class, 'update']);
    Route::apiResource('users', UserController::class); // CRUD users
    Route::apiResource('categories', CategoryController::class); // CRUD categories
    Route::apiResource('actors', ActorController::class); // CRUD actors
    // Upload avatar actor qua PUT multipart hay bị PHP bỏ qua body → cho phép POST để update ổn định
    Route::post('actors/{id}', [ActorController::class, 'update']);

    // Gán/xóa diễn viên cho phim
    Route::post('movies/{movieId}/actors', [MovieActorController::class, 'attach']); // attach (không xoá cũ)
    Route::put('movies/{movieId}/actors', [MovieActorController::class, 'sync']); // sync (thay thế)
    Route::delete('movies/{movieId}/actors/{actorId}', [MovieActorController::class, 'detach']); // detach 1 diễn viên
    Route::apiResource('servers', ServerController::class); // CRUD servers

    Route::apiResource('ratings', RatingController::class)->only(['index', 'store', 'show', 'update', 'destroy']); // CRUD ratings (chỉ của user hiện tại)

    Route::post('comments', [CommentController::class, 'store']); // tạo comment

    Route::get('/favorites', [FavoriteController::class, 'index']); // list favorites của user hiện tại
    Route::post('/favorites/{movieId}', [FavoriteController::class, 'toggle']); // toggle favorite
    Route::put('/favorites/{movieId}', [FavoriteController::class, 'update']); // update favorite (pivot)

    Route::post('watch-history', [WatchHistoryController::class, 'store']); // lưu tiến độ xem (episode_id)
    Route::get('recommendations', [RecommendationController::class, 'forYou']); // gợi ý phim theo thể loại

    Route::get('notifications', [NotificationController::class, 'index']); // list notifications
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']); // đánh dấu đã đọc

    // ===== RBAC management (SUPER_ADMIN only, enforced in controller) =====
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::put('roles/{roleId}/permissions', [RolePermissionController::class, 'sync']); // sync toàn bộ
    Route::post('roles/{roleId}/permissions', [RolePermissionController::class, 'attach']); // attach thêm
    Route::put('users/{id}/role', [UserRoleController::class, 'update']); // set role cho user

    // ===== Admin APIs (RBAC) =====
    Route::middleware(\App\Http\Middleware\RbacMiddleware::class)->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'stats']); // dashboard stats
        Route::post('/admin/crawl-movies', [CrawlController::class, 'start']); // start crawl movies
        Route::get('/admin/crawl-status', [CrawlController::class, 'status']); // crawl status
        Route::post('/admin/crawl/category', [CrawlController::class, 'crawlCategory']); // crawl theo category
        Route::post('/admin/crawl/country', [CrawlController::class, 'crawlCountry']); // crawl theo quốc gia (slug OPhim)
    });


});

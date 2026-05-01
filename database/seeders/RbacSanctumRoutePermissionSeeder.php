<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;

/**
 * Tạo bản ghi permission cho mọi route có middleware auth:sanctum
 * (tương đương khối trong routes/api.php bọc auth:sanctum).
 *
 * Route ngoài nhóm đó không cần permission — coi như public.
 *
 * Chạy: php artisan db:seed --class=RbacSanctumRoutePermissionSeeder
 */
class RbacSanctumRoutePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! $this->usesAuthSanctum($route->gatherMiddleware())) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $uri = ltrim((string) $route->uri(), '/');
                $methodUpper = strtoupper($method);
                $desc = $this->describe($methodUpper, $uri);

                $perm = Permission::query()->firstOrCreate(
                    [
                        'method' => $methodUpper,
                        'api_path' => $uri,
                    ],
                    [
                        'name' => $methodUpper.' '.$uri,
                        'content' => $desc,
                    ]
                );

                // Nếu đã seed trước đó với content dạng Controller@method thì update lại sang mô tả tiếng Việt.
                if (is_string($perm->content) && str_contains($perm->content, 'App\\Http\\Controllers\\')) {
                    $perm->update([
                        'content' => $desc,
                    ]);
                }
            }
        }
    }

    private function describe(string $method, string $uri): string
    {
        $path = ltrim($uri, '/');
        $pathNoApi = preg_replace('#^api/#', '', $path) ?? $path;

        // Special auth routes
        if ($pathNoApi === 'user' && $method === 'GET') {
            return 'Lấy thông tin người dùng hiện tại';
        }
        if ($pathNoApi === 'logout' && $method === 'POST') {
            return 'Đăng xuất';
        }

        // Admin routes
        if (str_starts_with($pathNoApi, 'admin/')) {
            return match ($pathNoApi) {
                'admin/dashboard' => 'Xem dashboard admin',
                'admin/crawl-movies' => 'Admin: chạy crawl movies',
                'admin/crawl-status' => 'Admin: xem trạng thái crawl',
                'admin/crawl/category' => 'Admin: crawl theo thể loại',
                'admin/crawl/country' => 'Admin: crawl theo quốc gia',
                default => 'Admin: '.$method.' '.$pathNoApi,
            };
        }

        $resource = explode('/', $pathNoApi)[0] ?? $pathNoApi;
        $resourceLabel = match ($resource) {
            'users' => 'người dùng',
            'categories' => 'thể loại',
            'actors' => 'diễn viên',
            'servers' => 'server',
            'ratings' => 'đánh giá',
            'favorites' => 'yêu thích',
            'watch-history' => 'lịch sử xem',
            'recommendations' => 'gợi ý phim',
            'notifications' => 'thông báo',
            'roles' => 'vai trò',
            'permissions' => 'quyền',
            default => $resource,
        };

        // Common actions for apiResource
        $isCollection = $pathNoApi === $resource;
        $isShow = str_starts_with($pathNoApi, $resource.'/{');
        $isUpdate = $isShow && in_array($method, ['PUT', 'PATCH'], true);
        $isDestroy = $isShow && $method === 'DELETE';

        if ($isCollection && $method === 'GET') {
            return 'Lấy danh sách '.$resourceLabel;
        }
        if ($isCollection && $method === 'POST') {
            return 'Tạo '.$resourceLabel;
        }
        if ($isShow && $method === 'GET') {
            return 'Xem chi tiết '.$resourceLabel;
        }
        if ($isUpdate) {
            return 'Cập nhật '.$resourceLabel;
        }
        if ($isDestroy) {
            return 'Xóa '.$resourceLabel;
        }

        // Other custom endpoints
        if ($resource === 'actors' && $method === 'POST' && preg_match('#^actors/\{[^}]+\}$#', $pathNoApi)) {
            return 'Cập nhật diễn viên (multipart/form-data)';
        }
        if ($resource === 'users' && $method === 'POST' && preg_match('#^users/\{[^}]+\}$#', $pathNoApi)) {
            return 'Cập nhật người dùng (multipart/form-data)';
        }
        if ($resource === 'movies' && str_contains($pathNoApi, '/actors')) {
            return match ($method) {
                'POST' => 'Gán diễn viên cho phim (attach)',
                'PUT', 'PATCH' => 'Đồng bộ diễn viên của phim (sync)',
                'DELETE' => 'Gỡ diễn viên khỏi phim (detach)',
                default => 'Quản lý diễn viên của phim',
            };
        }
        if ($resource === 'notifications' && str_ends_with($pathNoApi, '/read') && $method === 'POST') {
            return 'Đánh dấu thông báo đã đọc';
        }

        // RBAC assign endpoints
        if ($resource === 'roles' && str_contains($pathNoApi, '/permissions')) {
            return match ($method) {
                'PUT' => 'Gán quyền cho vai trò (sync)',
                'POST' => 'Gán thêm quyền cho vai trò (attach)',
                'DELETE' => 'Xóa quyền khỏi vai trò (detach)',
                default => 'Quản lý quyền của vai trò',
            };
        }
        if ($resource === 'users' && str_ends_with($pathNoApi, '/role') && $method === 'PUT') {
            return 'Đổi vai trò người dùng';
        }

        return $method.' '.$pathNoApi;
    }

    /**
     * @param  array<int, string>  $middleware
     */
    private function usesAuthSanctum(array $middleware): bool
    {
        foreach ($middleware as $m) {
            if ($m === 'auth:sanctum') {
                return true;
            }
        }

        return false;
    }
}

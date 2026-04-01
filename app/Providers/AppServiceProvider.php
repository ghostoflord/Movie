<?php

namespace App\Providers;

use App\Enum\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureSuperAdminExists();
    }

    private function ensureSuperAdminExists(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                return;
            }

            if (User::query()->exists()) {
                return;
            }

            User::query()->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'password' => Hash::make('123456'),
                'role' => UserRoleEnum::ADMIN->value,
                'active' => true,
                'gender' => null,
                'provider' => 'LOCAL',
                'email_verified_at' => now(),
            ]);
        } catch (Throwable) {
            // DB chưa sẵn sàng (chưa migrate / chưa bật MySQL), bỏ qua để app vẫn chạy.
            return;
        }
    }
}

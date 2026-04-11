<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enum\AuthProviderEnum;
use App\Enum\GenderEnum;
use App\Enum\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,  HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'role',
        'gender',
        'provider',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Thuộc tính ảo khi serialize JSON (login, GET /api/user, …).
     *
     * Nguồn sự thật (giống Laravel / dự án thật): cột `email_verified_at`.
     * - null  → chưa xác minh email
     * - có datetime → đã bấm link xác minh trong email (route verify)
     *
     * @var list<string>
     */
    protected $appends = [
        'is_email_verified',
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            // enum
            'role'     => UserRoleEnum::class,
            'gender'   => GenderEnum::class,
            'provider' => AuthProviderEnum::class,

            // primitive
            'active'   => 'boolean',
        ];
    }

    // ===== Relationships =====

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function favoriteMovies()
    {
        return $this->belongsToMany(Movie::class, 'favorites');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Đồng bộ với Laravel MustVerifyEmail: chỉ tin vào `email_verified_at`.
     */
    public function getIsEmailVerifiedAttribute(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** URL đầy đủ để hiển thị ảnh (cần `php artisan storage:link`). */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}

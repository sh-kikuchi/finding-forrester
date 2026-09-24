<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ログインユーザー。管理者（店舗）と個人ユーザー（購入者）を`role`で区別する。
 *
 * 管理者の場合、`name`・`address`・`tel`・`time`・`image` は公開されるショップ情報として使う。
 */
#[Fillable(['name', 'email', 'password', 'address', 'tel', 'time', 'image', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * 属性のキャスト定義を返す。
     *
     * @return array<string, string> 属性名 => キャスト型
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * このユーザー（管理者）が出品している本。
     *
     * @return HasMany 出品しているBookの一覧
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * 管理者（店舗）ロールかどうか。
     *
     * @return bool 管理者なら true
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * 個人ユーザー（購入者）ロールかどうか。
     *
     * @return bool 個人ユーザーなら true
     */
    public function isUser(): bool
    {
        return $this->role === UserRole::User;
    }
}

<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

/**
 * 本の所有権のみを扱うPolicy。
 *
 * 「そもそもログイン中のユーザーが管理者（店舗）かどうか」はroute側の`role:admin`
 * ミドルウェアの責務であり、ここでは扱わない。ここでは「この管理者がこの本の
 * 所有者かどうか」だけを判定する。
 */
class BookPolicy
{
    /**
     * この本を閲覧できるか（所有者のみ）。
     *
     * @param  User  $user  ログイン中の管理者
     * @param  Book  $book  対象の本
     * @return bool 所有者なら true
     */
    public function view(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * この本を更新できるか（所有者のみ）。
     *
     * @param  User  $user  ログイン中の管理者
     * @param  Book  $book  対象の本
     * @return bool 所有者なら true
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * この本を削除できるか（所有者のみ）。
     *
     * @param  User  $user  ログイン中の管理者
     * @param  Book  $book  対象の本
     * @return bool 所有者なら true
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}

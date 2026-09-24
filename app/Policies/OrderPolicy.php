<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * 注文に対する管理者（店舗）の操作可否を扱うPolicy。
 *
 * 「管理者かどうか」はroute側の`role:admin`ミドルウェアの責務であり、ここでは
 * 「この管理者の本がこの注文に含まれているか」だけを判定する。
 */
class OrderPolicy
{
    /**
     * この注文を発送できるか（自分の本を含む注文のみ）。
     *
     * @param  User  $user  ログイン中の管理者
     * @param  Order  $order  対象の注文
     * @return bool 注文に自分（`seller_id`）の商品が含まれていれば true
     */
    public function ship(User $user, Order $order): bool
    {
        return $order->items()->where('seller_id', $user->id)->exists();
    }
}

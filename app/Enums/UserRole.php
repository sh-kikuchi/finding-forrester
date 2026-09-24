<?php

namespace App\Enums;

/**
 * ユーザーの役割（ロール）。
 *
 * - Admin: 管理者（店舗）。本の出品・管理、受注の確認と発送ができる。
 * - User: 個人ユーザー（購入者）。本の購入と注文履歴の確認ができる。
 */
enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}

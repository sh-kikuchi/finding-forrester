<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ログイン中のユーザーの役割（role）を検証する。
 *
 * ゲスト（未ログイン）は素通りさせる。ゲストを弾くかどうかは`auth`ミドルウェアの責務であり、
 * このミドルウェアは「ログインしている場合、役割が一致しているか」だけを判定する。
 */
class EnsureUserHasRole
{
    /**
     * リクエストを処理する。ログイン中のユーザーの役割が一致しない場合は403を返す。
     *
     * @param  Request  $request  処理対象のリクエスト
     * @param  Closure(Request): (Response)  $next  次のミドルウェア（または処理）
     * @param  string  $role  要求する役割（UserRole::valueに対応する文字列）
     * @return Response 次の処理のレスポンス
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user !== null && $user->role !== UserRole::from($role)) {
            abort(403);
        }

        return $next($request);
    }
}

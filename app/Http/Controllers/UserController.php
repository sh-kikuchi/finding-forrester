<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateShopRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * ショップ（管理者ユーザー）の公開ページと、ショップ情報の編集を扱うコントローラー。
 *
 * 公開ページ（show）は誰でも閲覧でき、編集・更新は`auth`+`role:admin`ミドルウェア配下。
 */
class UserController extends Controller
{
    /**
     * ショップの公開ページを表示する。
     *
     * @param  User  $shop  ルートモデルバインディングで解決された対象のショップ（ユーザー）
     * @return View ショップ情報を渡す公開ページ
     */
    public function show(User $shop): View
    {
        return view('user.shop', compact('shop'));
    }

    /**
     * ログイン中のユーザー自身のショップ情報の編集フォームを表示する。
     *
     * @param  Request  $request  ログイン中のユーザーを取得するためのリクエスト
     * @return View ショップ情報の編集フォーム画面
     */
    public function edit(Request $request): View
    {
        $shop = $request->user();

        return view('user.edit', compact('shop'));
    }

    /**
     * ログイン中のユーザー自身のショップ情報を更新する。
     *
     * 画像が指定された場合は`bookimg`ディスクへ保存する。`newPassword`が入力された場合のみ
     * パスワードを更新する。
     *
     * @param  UpdateShopRequest  $request  検証済みのショップ情報
     * @return RedirectResponse 更新後のショップ公開ページへのリダイレクト
     */
    public function update(UpdateShopRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $imageName = $user->image;

        if ($request->hasFile('image')) {
            $imageName = $request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('image', $imageName, 'bookimg');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'address' => $validated['address'] ?? null,
            'tel' => $validated['tel'] ?? null,
            'time' => $validated['time'] ?? null,
            'image' => $imageName,
        ]);

        if (! empty($validated['newPassword'])) {
            $user->update([
                'password' => Hash::make($validated['newPassword']),
            ]);
        }

        return redirect()->route('shop.show', ['shop' => $user]);
    }
}

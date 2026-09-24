<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_register_call_to_action(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('アカウント新規登録'));
        $response->assertSee(__('入荷本一覧へ'));
        $response->assertDontSee(__('書籍を探す'));
    }

    public function test_admin_sees_book_management_calls_to_action(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk();
        $response->assertSee(__('入荷本一覧へ'));
        $response->assertSee(__('本を登録する'));
        $response->assertSee(__('書籍を探す'));
        $response->assertDontSee(__('アカウント新規登録'));
    }

    public function test_individual_user_sees_the_store_call_to_action(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee(__('本屋へ'));
        $response->assertDontSee(__('入荷本一覧へ'));
        $response->assertDontSee(__('アカウント新規登録'));
    }
}

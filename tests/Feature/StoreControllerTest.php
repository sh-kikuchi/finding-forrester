<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_browse_the_store(): void
    {
        Book::factory()->create(['is_for_sale' => true]);

        $response = $this->get('/store');

        $response->assertOk();
    }

    public function test_an_individual_user_can_browse_the_store(): void
    {
        $user = User::factory()->create();
        Book::factory()->create(['is_for_sale' => true]);

        $response = $this->actingAs($user)->get('/store');

        $response->assertOk();
    }

    public function test_an_admin_cannot_browse_the_store(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/store');

        $response->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_shops_public_profile(): void
    {
        $shop = User::factory()->create(['name' => 'Forrester Books']);

        $response = $this->get("/shop/{$shop->id}");

        $response->assertOk();
        $response->assertViewHas('shop', fn ($viewShop) => $viewShop->is($shop));
        $response->assertSee('Forrester Books');
    }

    public function test_guest_is_redirected_to_login_when_opening_the_shop_edit_form(): void
    {
        $response = $this->get('/shop/edit');

        $response->assertRedirect(route('login'));
    }

    public function test_edit_form_shows_the_authenticated_users_own_profile(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/shop/edit');

        $response->assertOk();
        $response->assertViewHas('shop', fn ($shop) => $shop->is($user));
    }

    public function test_updating_the_profile_requires_name_and_email(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->put('/shop/update', []);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_email_must_be_unique_among_other_users(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->put('/shop/update', [
            'name' => $user->name,
            'email' => $other->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_keeping_the_same_email_does_not_trigger_a_uniqueness_error(): void
    {
        $user = User::factory()->admin()->create(['email' => 'me@example.com']);

        $response = $this->actingAs($user)->put('/shop/update', [
            'name' => 'Updated Name',
            'email' => 'me@example.com',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_valid_submission_updates_the_profile_and_redirects_to_the_shop_page(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->put('/shop/update', [
            'name' => 'New Shop Name',
            'email' => 'new-email@example.com',
            'address' => 'Tokyo',
            'tel' => '090-1234-5678',
            'time' => '9:00-18:00',
        ]);

        $response->assertRedirect(route('shop.show', ['shop' => $user]));

        $user->refresh();
        $this->assertSame('New Shop Name', $user->name);
        $this->assertSame('new-email@example.com', $user->email);
        $this->assertSame('Tokyo', $user->address);
        $this->assertSame('090-1234-5678', $user->tel);
        $this->assertSame('9:00-18:00', $user->time);
    }

    public function test_submitting_a_new_password_changes_the_password(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->put('/shop/update', [
            'name' => $user->name,
            'email' => $user->email,
            'newPassword' => 'a-new-password',
        ]);

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
    }

    public function test_leaving_the_password_field_blank_keeps_the_existing_password(): void
    {
        $user = User::factory()->admin()->create();
        $originalPassword = $user->password;

        $this->actingAs($user)->put('/shop/update', [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_uploaded_image_is_stored_and_associated_with_the_profile(): void
    {
        Storage::fake('bookimg');
        $user = User::factory()->admin()->create();
        $image = UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg');

        $this->actingAs($user)->put('/shop/update', [
            'name' => $user->name,
            'email' => $user->email,
            'image' => $image,
        ]);

        $this->assertSame('avatar.jpg', $user->fresh()->image);
        Storage::disk('bookimg')->assertExists('image/avatar.jpg');
    }

    public function test_individual_users_cannot_access_shop_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/shop/edit')->assertForbidden();
        $this->actingAs($user)->put('/shop/update', [])->assertForbidden();
    }
}

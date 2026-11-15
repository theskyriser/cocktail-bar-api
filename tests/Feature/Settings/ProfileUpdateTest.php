<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertNoContent();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_is_stored_in_lowercase_so_the_user_can_still_log_in(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'Test@Example.com',
            ])
            ->assertNoContent();

        $this->assertSame('test@example.com', $user->refresh()->email);

        $this->postJson(route('logout'))->assertNoContent();

        $this->postJson(route('login.store'), [
            'email' => 'Test@Example.com',
            'password' => 'password',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_must_be_unique_regardless_of_case(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'Taken@Example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ])
            ->assertNoContent();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->deleteJson(route('profile.destroy'), ['password' => 'password'])
            ->assertNoContent();

        $this->assertNull($user->fresh());

        $this->assertGuest(config('fortify.guard'));

        /*
         * One container is reused across requests here, so the guard that already
         * resolved this user would answer from memory. Drop it to see what a
         * genuinely new request sees.
         */
        app('auth')->forgetGuards();

        $this->getJson(route('user'))->assertUnauthorized();
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertNotNull($user->fresh());
    }

    public function test_guests_cannot_update_a_profile(): void
    {
        $this->patchJson(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertUnauthorized();
    }
}

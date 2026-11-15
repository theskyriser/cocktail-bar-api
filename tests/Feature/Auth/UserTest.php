<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_authenticated_user_is_returned_as_a_bare_object(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('user'))
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', $user->name)
            ->assertJsonPath('email', $user->email);

        $this->assertEqualsCanonicalizing([
            'id',
            'name',
            'email',
            'email_verified_at',
            'created_at',
            'updated_at',
        ], array_keys($response->json()));
    }

    public function test_timestamps_are_serialised_as_iso_8601(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('user'))
            ->assertJsonPath('created_at', $user->created_at->toJSON())
            ->assertJsonPath('email_verified_at', $user->email_verified_at->toJSON());
    }

    public function test_guests_get_a_401_rather_than_a_redirect(): void
    {
        $this->getJson(route('user'))
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
    }

    public function test_unverified_users_are_refused_a_verified_only_endpoint(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->deleteJson(route('profile.destroy'), ['password' => 'password'])
            ->assertForbidden();

        $this->assertNotNull($user->fresh());
    }

    public function test_email_can_be_verified(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->getJson($this->verificationUrl($user))
            ->assertNoContent();

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verifying_from_a_mail_client_lands_back_in_the_spa(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get($this->verificationUrl($user))
            ->assertRedirect(config('fortify.redirects.email-verification').'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson($this->verificationUrl($user, email: 'wrong-email'));

        Event::assertNotDispatched(Verified::class);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_user_id(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson($this->verificationUrl($user, id: 123));

        Event::assertNotDispatched(Verified::class);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_already_verified_user_does_not_fire_the_event_again(): void
    {
        Event::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson($this->verificationUrl($user))
            ->assertNoContent();

        Event::assertNotDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    protected function verificationUrl(User $user, ?int $id = null, ?string $email = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $id ?? $user->id, 'hash' => sha1($email ?? $user->email)],
        );
    }
}

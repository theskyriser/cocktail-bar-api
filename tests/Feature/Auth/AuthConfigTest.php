<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class AuthConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_config_is_readable_by_guests(): void
    {
        $this->getJson(route('auth.config'))
            ->assertOk()
            ->assertExactJson([
                'password_rules' => Password::defaults()->toPasswordRulesString(),
                'can_reset_password' => true,
                'registration_enabled' => true,
                'must_verify_email' => true,
            ]);
    }

    public function test_auth_config_reports_disabled_features(): void
    {
        config(['fortify.features' => []]);

        $this->getJson(route('auth.config'))
            ->assertOk()
            ->assertJson([
                'can_reset_password' => false,
                'registration_enabled' => false,
                'must_verify_email' => false,
            ]);
    }
}

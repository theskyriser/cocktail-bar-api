<?php

namespace Tests\Feature\Concerns;

use App\Models\User;

trait ActingAsUser
{
    protected User $user;
    protected function setUpActingAsUser(): void
    {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
}

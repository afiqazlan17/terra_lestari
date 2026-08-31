<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_registration_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/user/passkeys/options');

        $response->assertOk();
        $response->assertJsonStructure(['options' => ['challenge', 'rp', 'user']]);
    }

    public function test_guest_can_fetch_login_options(): void
    {
        $response = $this->getJson('/passkeys/login/options');

        $response->assertOk();
        $response->assertJsonStructure(['options' => ['challenge']]);
    }

    public function test_guest_cannot_fetch_registration_options(): void
    {
        $response = $this->getJson('/user/passkeys/options');

        $response->assertUnauthorized();
    }
}

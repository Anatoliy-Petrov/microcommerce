<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Profile;
use Tests\TestCase;

final class ProfileControllerTest extends TestCase
{
    public function testShowReturnsPublicProfile(): void
    {
        $profile = Profile::factory()->create(['display_name' => 'Alice']);

        $response = $this->getJson("/users/{$profile->id}", ['X-User-Id' => $profile->id]);

        $response->assertOk()
            ->assertJsonPath('data.userId', $profile->id)
            ->assertJsonPath('data.displayName', 'Alice');
    }

    public function testShowRequiresUserId(): void
    {
        $profile = Profile::factory()->create();

        $this->getJson("/users/{$profile->id}")->assertUnauthorized();
    }

    public function testShowPrivateReturnsFullProfileForOwner(): void
    {
        $profile = Profile::factory()->create();

        $response = $this->getJson("/users/{$profile->id}/private", ['X-User-Id' => $profile->id]);

        $response->assertOk()
            ->assertJsonPath('data.userId', $profile->id)
            ->assertJsonStructure(['data' => ['userId', 'displayName', 'bio', 'avatarUrl', 'createdAt', 'updatedAt']]);
    }

    public function testShowPrivateForbiddenForOtherUser(): void
    {
        $profile = Profile::factory()->create();

        $this->getJson("/users/{$profile->id}/private", ['X-User-Id' => 'other-user'])
            ->assertForbidden();
    }

    public function testUpdateChangesDisplayName(): void
    {
        $profile = Profile::factory()->create();

        $response = $this->putJson("/users/{$profile->id}", ['display_name' => 'Bob'], ['X-User-Id' => $profile->id]);

        $response->assertOk()
            ->assertJsonPath('data.displayName', 'Bob');
    }

    public function testUpdateForbiddenForOtherUser(): void
    {
        $profile = Profile::factory()->create();

        $this->putJson("/users/{$profile->id}", ['display_name' => 'Eve'], ['X-User-Id' => 'attacker'])
            ->assertForbidden();
    }

    public function testUpdateValidatesDisplayNameLength(): void
    {
        $profile = Profile::factory()->create();

        $this->putJson("/users/{$profile->id}", ['display_name' => str_repeat('x', 101)], ['X-User-Id' => $profile->id])
            ->assertUnprocessable();
    }
}
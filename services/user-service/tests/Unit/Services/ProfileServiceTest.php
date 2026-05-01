<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Profile;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProfileService();
    }

    public function testGetPublicReturnsProfile(): void
    {
        $profile = Profile::factory()->create();

        $result = $this->service->getPublic($profile->id);

        $this->assertSame($profile->id, $result->id);
    }

    public function testGetPrivateThrowsWhenNotOwner(): void
    {
        $profile = Profile::factory()->create();

        $this->expectException(\DomainException::class);

        $this->service->getPrivate($profile->id, 'other-user-id');
    }

    public function testGetPrivateReturnsProfileForOwner(): void
    {
        $profile = Profile::factory()->create();

        $result = $this->service->getPrivate($profile->id, $profile->id);

        $this->assertSame($profile->id, $result->id);
    }

    public function testUpdateThrowsWhenNotOwner(): void
    {
        $profile = Profile::factory()->create();

        $this->expectException(\DomainException::class);

        $this->service->update($profile->id, 'other-user-id', ['display_name' => 'Alice']);
    }

    public function testUpdateChangesDisplayName(): void
    {
        $profile = Profile::factory()->create();

        $updated = $this->service->update($profile->id, $profile->id, ['display_name' => 'Alice']);

        $this->assertSame('Alice', $updated->display_name);
    }

    public function testCreateFromEventCreatesProfile(): void
    {
        $userId = 'abc-123-def';

        $profile = $this->service->createFromEvent($userId);

        $this->assertSame($userId, $profile->id);
        $this->assertDatabaseHas('profiles', ['id' => $userId]);
    }

    public function testCreateFromEventIsIdempotent(): void
    {
        $userId = 'abc-123-def';

        $this->service->createFromEvent($userId);
        $this->service->createFromEvent($userId);

        $this->assertSame(1, Profile::where('id', $userId)->count());
    }
}
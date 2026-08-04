<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesTestUsers;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestUsers;

    public function test_user_can_upload_a_profile_picture(): void
    {
        Storage::fake('public');
        $user = $this->actingAsRole('Employee');

        $response = $this->postJson('/api/profile/upload', [
            'profile_picture' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'path']);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $user->fresh()->profile_picture);
    }

    public function test_uploading_a_new_picture_deletes_the_previous_one(): void
    {
        Storage::fake('public');
        $user = $this->actingAsRole('Employee');

        $firstResponse = $this->postJson('/api/profile/upload', [
            'profile_picture' => UploadedFile::fake()->image('first.jpg'),
        ]);
        $firstPath = $firstResponse->json('path');
        Storage::disk('public')->assertExists($firstPath);

        $secondResponse = $this->postJson('/api/profile/upload', [
            'profile_picture' => UploadedFile::fake()->image('second.jpg'),
        ]);
        $secondPath = $secondResponse->json('path');

        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertSame($secondPath, $user->fresh()->profile_picture);
    }

    public function test_upload_fails_validation_without_a_file(): void
    {
        $this->actingAsRole('Employee');

        $response = $this->postJson('/api/profile/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_picture']);
    }

    public function test_upload_rejects_a_non_image_file(): void
    {
        Storage::fake('public');
        $this->actingAsRole('Employee');

        $response = $this->postJson('/api/profile/upload', [
            'profile_picture' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_picture']);
    }

    public function test_unauthenticated_user_cannot_upload_a_profile_picture(): void
    {
        $response = $this->postJson('/api/profile/upload', [
            'profile_picture' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertStatus(401);
    }
}

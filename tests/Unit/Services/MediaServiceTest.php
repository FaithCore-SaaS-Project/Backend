<?php

namespace Tests\Unit\Services;

use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    protected MediaService $mediaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediaService = new MediaService();
    }

    public function test_it_can_upload_a_file()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');
        $path = $this->mediaService->upload($file, 'avatars');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_it_returns_null_when_uploading_null()
    {
        $path = $this->mediaService->upload(null, 'avatars');
        $this->assertNull($path);
    }

    public function test_it_can_delete_an_existing_file()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');
        $path = $file->store('avatars', 'public');

        Storage::disk('public')->assertExists($path);

        $result = $this->mediaService->delete($path);

        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_it_returns_false_when_deleting_non_existent_file()
    {
        Storage::fake('public');
        $result = $this->mediaService->delete('non-existent-path.jpg');
        $this->assertFalse($result);
    }
}

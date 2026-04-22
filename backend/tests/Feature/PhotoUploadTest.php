<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_photo(): void
    {
        Storage::fake('public');

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/uploads/photos', [
            'photo' => new UploadedFile(
                $this->minimalJpegPath(),
                'lavanda.jpg',
                'image/jpeg',
                null,
                true
            ),
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['path', 'url'],
            ]);

        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    private function minimalJpegPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'plantaria-photo-');

        file_put_contents(
            $path,
            base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/ASP/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/ASP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EFBQRAQAAAAAAAAAAAAAAAAAAARD/2gAIAQMBAT8QH//EFBQRAQAAAAAAAAAAAAAAAAAAARD/2gAIAQIBAT8QH//EFBABAQAAAAAAAAAAAAAAAAAAARD/2gAIAQEAAT8QH//Z')
        );

        return $path;
    }
}

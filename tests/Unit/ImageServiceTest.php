<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    public function test_jpeg_resized_to_800px_preserving_ratio(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('ternak.jpg', 1600, 900);

        $path = ImageService::storeResized($file, 'animals/photos');

        Storage::disk('public')->assertExists($path);

        $image = imagecreatefromstring(Storage::disk('public')->get($path));
        $this->assertEquals(800, imagesx($image));
        $this->assertEquals(450, imagesy($image));
        imagedestroy($image);
    }

    public function test_small_image_is_not_upscaled(): void
    {
        Storage::fake('public');

        // GD membuat gambar PNG; dipaksa ekstensi jpg untuk menguji resize tetap.
        $file = UploadedFile::fake()->image('kecil.jpg', 100, 50);

        $path = ImageService::storeResized($file, 'animals/photos');

        $image = imagecreatefromstring(Storage::disk('public')->get($path));
        $this->assertEquals(100, imagesx($image));
        $this->assertEquals(50, imagesy($image));
        imagedestroy($image);
    }

    public function test_png_keeps_transparency_and_extension(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 400, 300);

        $path = ImageService::storeResized($file, 'animals/photos');

        $this->assertStringEndsWith('.png', $path);
        $this->assertSame('image/png', mime_content_type(Storage::disk('public')->path($path)));
    }

    public function test_invalid_file_throws_runtime_exception(): void
    {
        Storage::fake('public');

        $this->expectException(RuntimeException::class);

        ImageService::storeResized(UploadedFile::fake()->create('teks.txt', 10));
    }
}

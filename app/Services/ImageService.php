<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ImageService
 *
 * Menyimpan foto ke disk public dengan resize otomatis memakai GD
 * (tanpa dependency tambahan). Sisi terpanjang diperkecil hingga
 * maxSize px, rasio aspek dipertahankan.
 */
class ImageService
{
    /**
     * Simpan file gambar, resize otomatis, dan kembalikan path relatif
     * (relatif ke disk "public", mis. animals/photos/<nama>.jpg).
     *
     * @param  string  $directory  Direktori tujuan tanpa leading slash.
     * @param  int  $maxSize  Panjang sisi terbesar (px) setelah resize.
     */
    public static function storeResized(UploadedFile $file, string $directory = 'animals/photos', int $maxSize = 800): string
    {
        $source = @file_get_contents($file->getRealPath());

        if ($source === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $src = @imagecreatefromstring($source);

        if ($src === false) {
            throw new RuntimeException('File bukan gambar yang valid (jpg/png).');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Ratio seragam → tidak pernah membesar gambar kecil.
        $ratio = min(1.0, $maxSize / max($srcW, $srcH));
        $dstW = max(1, (int) round($srcW * $ratio));
        $dstH = max(1, (int) round($srcH * $ratio));

        $dst = imagecreatetruecolor($dstW, $dstH);

        // Pertahankan transparansi PNG.
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        // Encode ke buffer.
        ob_start();

        if (strtolower($file->getClientOriginalExtension()) === 'png') {
            $extension = 'png';
            imagepng($dst);
        } else {
            $extension = 'jpg';
            imagejpeg($dst, null, 85);
        }

        $contents = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        $name = Str::random(40).'.'.$extension;
        $relativePath = trim($directory, '/').'/'.$name;

        Storage::disk('public')->put($relativePath, $contents);

        return $relativePath;
    }
}

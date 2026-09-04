<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public const MAX_WIDTH = 1280;

    public const MAX_HEIGHT = 1280;

    public const THUMB_WIDTH = 400;

    public const THUMB_HEIGHT = 300;

    public const DISK = 'public';

    public const QUALITY = 85;

    public const MAX_DOWNLOAD_BYTES = 10 * 1024 * 1024;

    public const DOWNLOAD_TIMEOUT = 15;

    public function upload(UploadedFile $file, string $directory = 'posts'): array
    {
        $filename = Str::uuid()->toString();
        $ext = 'jpg';

        $imageData = $this->resizeGd($file->getRealPath(), self::MAX_WIDTH, self::MAX_HEIGHT);
        $thumbData = $this->resizeGd($file->getRealPath(), self::THUMB_WIDTH, self::THUMB_HEIGHT);

        $imagePath = "{$directory}/{$filename}.{$ext}";
        $thumbPath = "{$directory}/{$filename}_thumb.{$ext}";

        Storage::disk(self::DISK)->put($imagePath, $imageData);
        Storage::disk(self::DISK)->put($thumbPath, $thumbData);

        return $this->paths($imagePath, $thumbPath);
    }

    /**
     * Store resized main image + thumbnail from raw image bytes (e.g. remote download).
     *
     * @return array{image_url: string, thumbnail_url: string}|null null on any failure
     */
    public function storeFromBinary(string $binary, string $directory = 'posts'): ?array
    {
        try {
            // Try Cloudinary first if enabled
            $cloudinary = app(CloudinaryService::class);
            if ($cloudinary->isEnabled()) {
                $publicId = $directory . '/' . Str::uuid()->toString();
                $cloudUrl = $cloudinary->uploadBinary($binary, $publicId);
                if ($cloudUrl) {
                    return [
                        'image_url' => $cloudUrl,
                        'thumbnail_url' => $cloudUrl,
                    ];
                }
                Log::warning('[ImageService] Cloudinary upload failed, falling back to local');
            }

            $tempPath = $this->writeTemp($binary);

            return $this->storeFromPath($tempPath, $directory);
        } catch (\Throwable $e) {
            Log::warning('[ImageService] storeFromBinary failed', [
                'error' => $e->getMessage(),
                'directory' => $directory,
            ]);

            return null;
        }
    }

    /**
     * Download a remote image and store a resized main image + thumbnail locally.
     *
     * @return array{image_url: string, thumbnail_url: string}|null null on any failure
     */
    public function downloadRemote(string $url, string $directory = 'posts'): ?array
    {
        try {
            if (!$this->isAllowedUrl($url)) {
                Log::warning('[ImageService] download rejected', [
                    'url' => $url,
                    'error' => 'Scheme must be http/https',
                ]);

                return null;
            }

            $response = Http::timeout(self::DOWNLOAD_TIMEOUT)
                ->withOptions(['verify' => true])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('[ImageService] download failed', [
                    'url' => $url,
                    'error' => "HTTP {$response->status()}",
                ]);

                return null;
            }

            $body = $response->body();

            if (strlen($body) > self::MAX_DOWNLOAD_BYTES) {
                Log::warning('[ImageService] download failed', [
                    'url' => $url,
                    'error' => 'Response exceeds max download size',
                ]);

                return null;
            }

            $contentType = $response->header('Content-Type');

            if (!$contentType || !str_starts_with(strtolower($contentType), 'image/')) {
                Log::warning('[ImageService] download failed', [
                    'url' => $url,
                    'error' => "Unexpected Content-Type: {$contentType}",
                ]);

                return null;
            }

            $info = @getimagesizefromstring($body);
            if ($info === false) {
                Log::warning('[ImageService] download failed', [
                    'url' => $url,
                    'error' => 'Body is not a valid image',
                ]);

                return null;
            }

            // Try Cloudinary first if enabled
            $cloudinary = app(CloudinaryService::class);
            if ($cloudinary->isEnabled()) {
                $publicId = 'posts/' . Str::uuid()->toString();
                $cloudUrl = $cloudinary->uploadBinary($body, $publicId);
                if ($cloudUrl) {
                    return [
                        'image_url' => $cloudUrl,
                        'thumbnail_url' => $cloudUrl,
                    ];
                }
                Log::warning('[ImageService] Cloudinary upload failed, falling back to local');
            }

            return $this->storeFromBinary($body, $directory);
        } catch (\Throwable $e) {
            Log::warning('[ImageService] download failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteByUrls(string $imageUrl, string $thumbnailUrl): void
    {
        $this->deleteUrl($imageUrl);
        $this->deleteUrl($thumbnailUrl);
    }

    public function deleteUrl(string $url): void
    {
        $path = str_replace(Storage::disk(self::DISK)->url(''), '', $url);
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function resizeGd(string $sourcePath, int $maxWidth, int $maxHeight): string
    {
        $info = getimagesize($sourcePath);
        $mime = $info['mime'];
        $srcW = $info[0];
        $srcH = $info[1];

        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/gif' => imagecreatefromgif($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => throw new \InvalidArgumentException("Unsupported image type: {$mime}"),
        };

        [$newW, $newH] = $this->calculateDimensions($srcW, $srcH, $maxWidth, $maxHeight);

        $dst = imagecreatetruecolor($newW, $newH);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        ob_start();
        imagejpeg($dst, null, self::QUALITY);
        $output = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $output;
    }

    private function calculateDimensions(int $srcW, int $srcH, int $maxW, int $maxH): array
    {
        if ($srcW <= $maxW && $srcH <= $maxH) {
            return [$srcW, $srcH];
        }

        $ratio = min($maxW / $srcW, $maxH / $srcH);

        return [(int) ($srcW * $ratio), (int) ($srcH * $ratio)];
    }

    private function isAllowedUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? '';

        return in_array(strtolower($scheme), ['http', 'https'], true);
    }

    private function writeTemp(string $binary): string
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('img_', true);
        file_put_contents($tempPath, $binary);

        return $tempPath;
    }

    private function storeFromPath(string $sourcePath, string $directory): array
    {
        $filename = Str::uuid()->toString();
        $ext = 'jpg';

        $imageData = $this->resizeGd($sourcePath, self::MAX_WIDTH, self::MAX_HEIGHT);
        $thumbData = $this->resizeGd($sourcePath, self::THUMB_WIDTH, self::THUMB_HEIGHT);

        $imagePath = "{$directory}/{$filename}.{$ext}";
        $thumbPath = "{$directory}/{$filename}_thumb.{$ext}";

        Storage::disk(self::DISK)->put($imagePath, $imageData);
        Storage::disk(self::DISK)->put($thumbPath, $thumbData);

        return $this->relativePaths($imagePath, $thumbPath);
    }

    private function paths(string $imagePath, string $thumbPath): array
    {
        return [
            'image_url' => Storage::disk(self::DISK)->url($imagePath),
            'thumbnail_url' => Storage::disk(self::DISK)->url($thumbPath),
        ];
    }

    /**
     * Return local relative paths (e.g. /storage/posts/{uuid}.jpg) for seeder/sync use.
     */
    private function relativePaths(string $imagePath, string $thumbPath): array
    {
        $disk = Storage::disk(self::DISK);
        return [
            'image_url' => $disk->url($imagePath),
            'thumbnail_url' => $disk->url($thumbPath),
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected ?string $cloudName;

    protected ?string $apiKey;

    protected ?string $apiSecret;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
    }

    public function isEnabled(): bool
    {
        return filled($this->cloudName) && filled($this->apiKey) && filled($this->apiSecret);
    }

    /**
     * Upload raw image bytes to Cloudinary.
     *
     * @return string|null Secure URL on success, null on failure.
     */
    public function uploadBinary(string $binary, string $publicId, string $folder = 'laranews'): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $timestamp = (string) time();
            $signature = $this->signParams([
                'folder' => $folder,
                'public_id' => $publicId,
                'timestamp' => $timestamp,
            ]);

            $response = Http::timeout(30)
                ->asMultipart()
                ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", [
                    ['name' => 'file', 'contents' => $binary, 'filename' => "{$publicId}.jpg"],
                    ['name' => 'api_key', 'contents' => $this->apiKey],
                    ['name' => 'timestamp', 'contents' => $timestamp],
                    ['name' => 'folder', 'contents' => $folder],
                    ['name' => 'public_id', 'contents' => $publicId],
                    ['name' => 'signature', 'contents' => $signature],
                ]);

            if ($response->successful() && filled($response->json('secure_url'))) {
                return $response->json('secure_url');
            }

            Log::warning('[CloudinaryService] upload failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('[CloudinaryService] upload exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sign Cloudinary params (alphabetically sorted, joined with &, appended api_secret, sha1).
     */
    protected function signParams(array $params): string
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "{$key}={$value}";
        }

        return sha1(implode('&', $pairs).$this->apiSecret);
    }
}

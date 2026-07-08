<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai-api.url'), '/');
        $this->apiKey = config('ai-api.key');
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout(10);
    }

    public function getConfig(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/api/config");

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch chatbot config: ' . $response->status());
        }

        return $response->json();
    }

    public function setConfig(string $key, string $value, string $updatedBy): array
    {
        $response = $this->client()->put("{$this->baseUrl}/api/config", [
            'key'        => $key,
            'value'      => $value,
            'updated_by' => $updatedBy,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Failed to update config key '{$key}': " . $response->status());
        }

        return $response->json();
    }
}

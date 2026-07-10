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

    public function getChatLogs(array $params = []): array
    {
        $response = $this->client()->get("{$this->baseUrl}/api/chat-logs", array_filter($params));

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException("Failed to fetch chat logs [{$response->status()}]: {$detail}");
        }

        return $response->json();
    }

    public function getChatLogDetail(string $sessionId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/api/chat-logs/{$sessionId}");

        if ($response->status() === 404) {
            throw new RuntimeException("Session not found: {$sessionId}");
        }

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch chat log detail: ' . $response->status());
        }

        return $response->json();
    }

    public function getKnowledgeBaseDocuments(): array
    {
        $response = $this->client()->timeout(30)->get("{$this->baseUrl}/api/knowledge-base/documents");

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch documents: ' . $response->status());
        }

        return $response->json();
    }

    public function ingestDocument(\Illuminate\Http\UploadedFile $file): array
    {
        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout(60)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("{$this->baseUrl}/api/knowledge-base/ingest");

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException("Upload failed: {$detail}");
        }

        return $response->json();
    }

    public function rollbackDocument(int $id): array
    {
        $response = $this->client()->timeout(30)->post("{$this->baseUrl}/api/knowledge-base/{$id}/rollback");

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException("Rollback failed: {$detail}");
        }

        return $response->json();
    }

    public function retryDocument(int $id): array
    {
        $response = $this->client()->timeout(30)->post("{$this->baseUrl}/api/knowledge-base/{$id}/retry");

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException("Retry failed: {$detail}");
        }

        return $response->json();
    }

    public function getAnalytics(): array
    {
        $response = $this->client()->timeout(30)->get("{$this->baseUrl}/api/analytics");

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch analytics: ' . $response->status());
        }

        return $response->json();
    }
}

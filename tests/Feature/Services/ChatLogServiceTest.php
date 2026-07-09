<?php

namespace Tests\Feature\Services;

use App\Services\AiApiService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ChatLogServiceTest extends TestCase
{
    private function service(): AiApiService
    {
        config(['ai-api.url' => 'http://localhost:8000', 'ai-api.key' => 'test-key']);
        return new AiApiService();
    }

    public function test_get_chat_logs_returns_paginated_data(): void
    {
        Http::fake([
            'localhost:8000/api/chat-logs*' => Http::response([
                'data' => [
                    [
                        'session_id'    => 'abc123',
                        'first_message' => 'Halo, saya ingin tahu produk',
                        'msg_count'     => 5,
                        'lead_id'       => 1,
                        'lead_name'     => 'Budi',
                        'started_at'    => '2026-07-08T10:00:00',
                    ],
                ],
                'pagination' => ['page' => 1, 'per_page' => 20, 'total' => 1, 'total_pages' => 1],
            ], 200),
        ]);

        $result = $this->service()->getChatLogs(['page' => 1, 'per_page' => 20]);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('abc123', $result['data'][0]['session_id']);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/chat-logs')
            && $req->hasHeader('X-API-Key', 'test-key'));
    }

    public function test_get_chat_logs_with_filters(): void
    {
        Http::fake(['localhost:8000/api/chat-logs*' => Http::response(['data' => [], 'pagination' => []], 200)]);

        $this->service()->getChatLogs([
            'search'      => 'produk',
            'lead_status' => 'lead_captured',
            'date_from'   => '2026-07-01',
        ]);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'search=produk')
            && str_contains($req->url(), 'lead_status=lead_captured'));
    }

    public function test_get_chat_logs_throws_on_server_error(): void
    {
        Http::fake(['localhost:8000/api/chat-logs*' => Http::response([], 500)]);

        $this->expectException(RuntimeException::class);
        $this->service()->getChatLogs();
    }

    public function test_get_chat_log_detail_returns_messages_and_lead(): void
    {
        Http::fake([
            'localhost:8000/api/chat-logs/abc123' => Http::response([
                'session_id'    => 'abc123',
                'messages'      => [
                    ['id' => 1, 'role' => 'user', 'content' => 'Halo', 'created_at' => '2026-07-08T10:00:00'],
                    ['id' => 2, 'role' => 'oliv', 'content' => 'Halo! Ada yang bisa dibantu?', 'created_at' => '2026-07-08T10:00:05'],
                ],
                'lead'          => ['id' => 1, 'name' => 'Budi', 'phone' => '08123456789'],
                'message_count' => 2,
            ], 200),
        ]);

        $result = $this->service()->getChatLogDetail('abc123');

        $this->assertEquals('abc123', $result['session_id']);
        $this->assertCount(2, $result['messages']);
        $this->assertEquals('Budi', $result['lead']['name']);
    }

    public function test_get_chat_log_detail_throws_on_not_found(): void
    {
        Http::fake(['localhost:8000/api/chat-logs/unknown' => Http::response(['detail' => 'Session not found'], 404)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Session not found/');
        $this->service()->getChatLogDetail('unknown');
    }
}

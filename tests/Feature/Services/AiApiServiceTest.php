<?php

namespace Tests\Feature\Services;

use App\Services\AiApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AiApiServiceTest extends TestCase
{
    private function configResponse(): array
    {
        return [
            'system_prompt' => [
                'value'      => 'Kamu adalah OLIV',
                'updated_by' => 'system',
                'updated_at' => '2026-07-08T00:00:00+00:00',
            ],
            'welcome_message' => [
                'value'      => 'Halo!',
                'updated_by' => 'system',
                'updated_at' => '2026-07-08T00:00:00+00:00',
            ],
            'lead_trigger_keywords' => [
                'value'      => 'beli,order',
                'updated_by' => 'system',
                'updated_at' => '2026-07-08T00:00:00+00:00',
            ],
        ];
    }

    public function test_get_config_returns_all_keys(): void
    {
        Http::fake([
            '*/api/config' => Http::response($this->configResponse(), 200),
        ]);

        $service = new AiApiService();
        $result = $service->getConfig();

        $this->assertArrayHasKey('system_prompt', $result);
        $this->assertArrayHasKey('welcome_message', $result);
        $this->assertArrayHasKey('lead_trigger_keywords', $result);
        $this->assertEquals('Kamu adalah OLIV', $result['system_prompt']['value']);
    }

    public function test_set_config_sends_put_request_with_api_key(): void
    {
        Http::fake([
            '*/api/config' => Http::response([
                'key'        => 'system_prompt',
                'value'      => 'New prompt',
                'updated_by' => 'hasryl',
                'updated_at' => '2026-07-08T12:00:00+00:00',
            ], 200),
        ]);

        $service = new AiApiService();
        $result = $service->setConfig('system_prompt', 'New prompt', 'hasryl');

        Http::assertSent(function (Request $request) {
            return $request->method() === 'PUT'
                && str_contains($request->url(), '/api/config')
                && $request->hasHeader('X-API-Key')
                && $request['key'] === 'system_prompt';
        });

        $this->assertEquals('New prompt', $result['value']);
    }

    public function test_get_config_throws_on_error_response(): void
    {
        Http::fake([
            '*/api/config' => Http::response(['detail' => 'Unauthorized'], 403),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/403/');

        $service = new AiApiService();
        $service->getConfig();
    }
}

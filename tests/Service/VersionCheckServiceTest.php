<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\VersionCheckService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class VersionCheckServiceTest extends TestCase
{
    public function testCheckDetectsUpdate(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"tag_name":"v1.2.0","html_url":"https://github.com/ricktg/my-invoice/releases/tag/v1.2.0"}', ['http_code' => 200]),
        ]);

        $service = new VersionCheckService($client, 'v1.1.0', 'ricktg/my-invoice');
        $result = $service->check();

        self::assertSame('v1.1.0', $result['current_version']);
        self::assertSame('v1.2.0', $result['latest_version']);
        self::assertTrue($result['has_update']);
        self::assertSame('https://github.com/ricktg/my-invoice/releases/tag/v1.2.0', $result['release_url']);
        self::assertNull($result['error']);
    }

    public function testCheckHandlesApiFailure(): void
    {
        $client = new MockHttpClient([
            new MockResponse('rate limited', ['http_code' => 403]),
        ]);

        $service = new VersionCheckService($client, 'v1.2.0', 'ricktg/my-invoice');
        $result = $service->check();

        self::assertSame('v1.2.0', $result['current_version']);
        self::assertNull($result['latest_version']);
        self::assertFalse($result['has_update']);
        self::assertNotNull($result['error']);
    }
}

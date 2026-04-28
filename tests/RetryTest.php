<?php

namespace Http\Tests;


use GuzzleHttp\Psr7\Response;
use Http\Http;
use PHPUnit\Framework\Attributes\Test;
use Http\Actions\HttpRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;


#[CoversClass(Http::class)]
#[CoversClass(HttpRequest::class)]
#[CoversMethod(HttpRequest::class, 'retry')]
final class RetryTest extends TestCase
{
    #[Test]
    public function it_retries_failed_requests(): void
    {
        $this->mockResponse([
            new Response(500),
            new Response(500),
            new Response(200, [], 'Success'),
        ]);

        $response = Http::retry(3, 10)->get('https://example.com');

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Success', $response->body());
        $this->assertCount(3, $this->container);
    }

    #[Test]
    public function it_stops_retrying_when_callback_returns_false(): void
    {
        $this->mockResponse([
            new Response(500),
            new Response(500),
            new Response(200, [], 'Success'),
        ]);

        // Only retry once
        $response = Http::retry(3, 10, function ($response) {
            return false;
        })->get('https://example.com');

        $this->assertEquals(500, $response->status());
        $this->assertCount(1, $this->container);
    }

    #[Test]
    public function it_retries_on_connection_exceptions(): void
    {
        $this->mockResponse([
            new \GuzzleHttp\Exception\ConnectException('Connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'https://example.com')),
            new Response(200, [], 'Success'),
        ]);

        $response = Http::retry(3, 10)->get('https://example.com');

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Success', $response->body());
        $this->assertCount(2, $this->container);
    }

    #[Test]
    public function it_throws_exception_when_all_retries_fail_with_exception(): void
    {
        $this->mockResponse([
            new \GuzzleHttp\Exception\ConnectException('Connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'https://example.com')),
            new \GuzzleHttp\Exception\ConnectException('Connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'https://example.com')),
        ]);

        $this->expectException(\Http\Exceptions\HandleRequestException::class);
        Http::retry(2, 10)->get('https://example.com');
    }
}

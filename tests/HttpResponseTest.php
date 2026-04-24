<?php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Http;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    private function mockResponse(int $status, array $headers = [], string $body = ''): void
    {
        $mock = new MockHandler([
            new Response($status, $headers, $body),
        ]);
        $handlerStack = HandlerStack::create($mock);

        Http::swap(new Client(['handler' => $handlerStack]));
    }

    #[Test]
    public function isSuccess_returns_true_for_200_range(): void
    {
        $this->mockResponse(200);
        $this->assertTrue(Http::get('https://example.com')->isSuccess());

        $this->mockResponse(201);
        $this->assertTrue(Http::get('https://example.com')->isSuccess());

        $this->mockResponse(299);
        $this->assertTrue(Http::get('https://example.com')->isSuccess());

        $this->mockResponse(300);
        $this->assertFalse(Http::get('https://example.com')->isSuccess());
    }

    #[Test]
    public function isOk_is_alias_for_isSuccess(): void
    {
        $this->mockResponse(200);
        $this->assertTrue(Http::get('https://example.com')->isOk());

        $this->mockResponse(400);
        $this->assertFalse(Http::get('https://example.com')->isOk());
    }

    #[Test]
    public function isRedirect_returns_true_for_300_range(): void
    {
        $this->mockResponse(300);
        $this->assertTrue(Http::get('https://example.com')->isRedirect());

        $this->mockResponse(302);
        $this->assertTrue(Http::get('https://example.com')->isRedirect());

        $this->mockResponse(200);
        $this->assertFalse(Http::get('https://example.com')->isRedirect());
    }

    #[Test]
    public function isClientError_returns_true_for_400_range(): void
    {
        $this->mockResponse(400);
        $this->assertTrue(Http::get('https://example.com')->isClientError());

        $this->mockResponse(404);
        $this->assertTrue(Http::get('https://example.com')->isClientError());

        $this->mockResponse(500);
        $this->assertFalse(Http::get('https://example.com')->isClientError());
    }

    #[Test]
    public function isServerError_returns_true_for_500_plus(): void
    {
        $this->mockResponse(500);
        $this->assertTrue(Http::get('https://example.com')->isServerError());

        $this->mockResponse(503);
        $this->assertTrue(Http::get('https://example.com')->isServerError());

        $this->mockResponse(499);
        $this->assertFalse(Http::get('https://example.com')->isServerError());
    }

    #[Test]
    public function body_returns_string_content(): void
    {
        $this->mockResponse(200, [], 'hello world');
        $response = Http::get('https://example.com');
        $this->assertEquals('hello world', $response->body());
        $this->assertEquals('hello world', (string) $response);
    }

    #[Test]
    public function json_decodes_content(): void
    {
        $this->mockResponse(200, [], json_encode(['foo' => 'bar']));
        $this->assertEquals(['foo' => 'bar'], Http::get('https://example.com')->json());
    }

    #[Test]
    public function header_returns_header_line(): void
    {
        $this->mockResponse(200, ['X-Foo' => 'Bar']);
        $this->assertEquals('Bar', Http::get('https://example.com')->header('X-Foo'));
    }

    #[Test]
    public function headers_returns_mapped_headers(): void
    {
        $this->mockResponse(200, ['X-Foo' => 'Bar', 'X-Bar' => 'Baz']);
        $this->assertEquals([
            'X-Foo' => 'Bar',
            'X-Bar' => 'Baz',
        ], Http::get('https://example.com')->headers());
    }
}

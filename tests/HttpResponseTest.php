<?php

namespace Http\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\TransferStats;
use Http\Actions\HttpRequest;
use Http\Actions\HttpResponse;
use Http\Exceptions\HandleRequestException;
use Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Http::class)]
#[CoversClass(HttpResponse::class)]
#[CoversMethod(Http::class, 'swap')]
#[CoversMethod(HttpRequest::class, 'get')]
#[CoversMethod(HttpRequest::class, 'post')]
#[CoversMethod(HttpRequest::class, 'put')]
#[CoversMethod(HttpRequest::class, 'patch')]
#[CoversMethod(HttpRequest::class, 'delete')]
#[CoversMethod(HttpResponse::class, 'body')]
#[CoversMethod(HttpResponse::class, 'headers')]
#[CoversMethod(HttpResponse::class, 'header')]
#[CoversMethod(HttpResponse::class, 'status')]
#[CoversMethod(HttpResponse::class, 'cookies')]
#[CoversMethod(HttpResponse::class, 'effectiveUri')]
#[CoversMethod(HttpResponse::class, 'isSuccess')]
#[CoversMethod(HttpResponse::class, 'isOk')]
#[CoversMethod(HttpResponse::class, 'isRedirect')]
#[CoversMethod(HttpResponse::class, 'isClientError')]
#[CoversMethod(HttpResponse::class, 'isServerError')]
#[CoversMethod(HttpResponse::class, 'successful')]
#[CoversMethod(HttpResponse::class, 'failed')]
#[CoversMethod(HttpResponse::class, 'clientError')]
#[CoversMethod(HttpResponse::class, 'serverError')]
#[CoversMethod(HttpResponse::class, 'onError')]
#[CoversMethod(HttpResponse::class, 'throw')]
#[CoversMethod(HttpResponse::class, 'throwIf')]
#[CoversMethod(HttpResponse::class, '__toString')]
#[CoversMethod(HttpResponse::class, '__call')]
final class HttpResponseTest extends TestCase
{
    protected function setUp(): void
    {
        Http::clearCookieJar();
    }

    private function mockResponse(int $status, array $headers = [], string $body = ''): void
    {
        $mock = new MockHandler([
            new Response($status, $headers, $body),
        ]);
        $handlerStack = HandlerStack::create($mock);

        Http::swap(new Client(['handler' => $handlerStack]));
    }

    #[Test]
    public function status_returns_status_code(): void
    {
        $this->mockResponse(200);
        $this->assertEquals(200, Http::get('https://example.com')->status());

        $this->mockResponse(404);
        $this->assertEquals(404, Http::get('https://example.com')->status());
    }

    #[Test]
    public function successful_returns_true_for_200_range(): void
    {
        $this->mockResponse(200);
        $this->assertTrue(Http::get('https://example.com')->successful());

        $this->mockResponse(400);
        $this->assertFalse(Http::get('https://example.com')->successful());
    }

    #[Test]
    public function failed_returns_true_for_400_plus(): void
    {
        $this->mockResponse(400);
        $this->assertTrue(Http::get('https://example.com')->failed());

        $this->mockResponse(500);
        $this->assertTrue(Http::get('https://example.com')->failed());

        $this->mockResponse(200);
        $this->assertFalse(Http::get('https://example.com')->failed());
    }

    #[Test]
    public function clientError_returns_true_for_400_range(): void
    {
        $this->mockResponse(404);
        $this->assertTrue(Http::get('https://example.com')->clientError());

        $this->mockResponse(500);
        $this->assertFalse(Http::get('https://example.com')->clientError());
    }

    #[Test]
    public function serverError_returns_true_for_500_plus(): void
    {
        $this->mockResponse(500);
        $this->assertTrue(Http::get('https://example.com')->serverError());

        $this->mockResponse(404);
        $this->assertFalse(Http::get('https://example.com')->serverError());
    }

    #[Test]
    public function onError_executes_callback_on_failure(): void
    {
        $this->mockResponse(400);
        $called = false;
        Http::get('https://example.com')->onError(function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);

        $this->mockResponse(200);
        $called = false;
        Http::get('https://example.com')->onError(function () use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);
    }

    #[Test]
    public function throw_throws_exception_on_failure(): void
    {
        $this->mockResponse(400);
        $this->expectException(HandleRequestException::class);
        $this->expectExceptionMessage('HTTP request returned status code 400');
        Http::get('https://example.com')->throw();
    }

    #[Test]
    public function throw_does_not_throw_on_success(): void
    {
        $this->mockResponse(200);
        $response = Http::get('https://example.com')->throw();
        $this->assertInstanceOf(HttpResponse::class, $response);
    }

    #[Test]
    public function throwIf_throws_exception_on_failure_when_condition_is_true(): void
    {
        $this->mockResponse(400);
        $this->expectException(HandleRequestException::class);
        Http::get('https://example.com')->throwIf(true);
    }

    #[Test]
    public function throwIf_does_not_throw_on_failure_when_condition_is_false(): void
    {
        $this->mockResponse(400);
        $response = Http::get('https://example.com')->throwIf(false);
        $this->assertInstanceOf(HttpResponse::class, $response);
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
        $this->assertEquals(['foo' => 'bar'], Http::get('https://example.com')->array());
    }

    #[Test]
    public function header_returns_header_line(): void
    {
        $this->mockResponse(200, ['X-Foo' => 'Bar']);
        $this->assertEquals('Bar', Http::get('https://example.com')->header('X-Foo'));
    }

    #[Test]
    public function it_can_return_effective_uri(): void
    {
        $response = new HttpResponse(new Response(200));
        $response->transferStats = new TransferStats(
            new Request('GET', 'https://example.com/final'),
            new Response(200),
            0.01,
            null,
        );

        $this->assertEquals('https://example.com/final', (string) $response->effectiveUri());
    }

    #[Test]
    public function to_string_returns_body_content(): void
    {
        $response = new HttpResponse(new Response(200, [], 'hello world'));

        $this->assertSame('hello world', (string) $response);
    }

    #[Test]
    public function call_proxies_methods_to_the_underlying_response(): void
    {
        $response = new HttpResponse(new Response(200, [], null, '1.1'));

        $this->assertSame('1.1', $response->getProtocolVersion());
    }

    #[Test]
    public function it_can_return_cookies(): void
    {
        $this->mockResponse(200);
        $response = Http::get('https://example.com');
        $this->assertNull($response->cookies());
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

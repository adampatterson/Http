<?php

namespace Http\Tests;


use GuzzleHttp\Psr7\Response;
use Http\Actions\HttpRequest;
use Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Http::class)]
#[CoversClass(HttpRequest::class)]
#[CoversMethod(HttpRequest::class, 'withToken')]
#[CoversMethod(HttpRequest::class, 'withHeaders')]
#[CoversMethod(HttpRequest::class, 'withBasicAuth')]
#[CoversMethod(HttpRequest::class, 'timeout')]

final class HeaderTest extends TestCase
{
    #[Test]
    public function setsAuthorizationHeader(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::withToken('my-secret-token')
            ->get('https://example.com');

        $this->assertEquals(200, $response->status());

        $sentRequest = $this->container[0]['request'];
        $this->assertEquals('Bearer my-secret-token', $sentRequest->getHeaderLine('Authorization'));
    }

    #[Test]
    public function addsCustomHeaders(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::withHeaders(['X-Custom' => 'value'])
            ->get('https://example.com');

        $this->assertEquals(200, $response->status());

        $sentRequest = $this->container[0]['request'];
        $this->assertEquals('value', $sentRequest->getHeaderLine('X-Custom'));
    }

    #[Test]
    public function it_sets_basic_auth_credentials(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        Http::withBasicAuth('adam', 'secret')
            ->get('https://example.com');

        $this->assertSame(['adam', 'secret'], $this->container[0]['options']['auth']);
    }

    #[Test]
    public function it_sets_request_timeout(): void
    {
        $this->mockResponse();

        Http::timeout(3)->get('https://example.com');

        $this->assertEquals(3, $this->container[0]['options']['timeout']);
    }

    #[Test]
    public function it_throws_an_exception_on_timeout(): void
    {
        $this->mockResponse([
            new \GuzzleHttp\Exception\ConnectException(
                'cURL error 28: Operation timed out',
                new \GuzzleHttp\Psr7\Request('GET', 'test')
            ),
        ]);

        $this->expectException(\Http\Exceptions\HandleRequestException::class);
        $this->expectExceptionMessage('cURL error 28: Operation timed out');

        Http::timeout(1)->get('https://example.com');
    }
}

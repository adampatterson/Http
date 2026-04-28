<?php

namespace Http\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Http\Actions\HttpRequest;
use Http\Actions\HttpResponse;
use Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Http::class)]
#[CoversClass(HttpRequest::class)]
#[CoversMethod(HttpRequest::class, 'patch')]
#[CoversMethod(HttpRequest::class, 'put')]
#[CoversMethod(HttpRequest::class, 'get')]
#[CoversMethod(HttpRequest::class, 'post')]
#[CoversMethod(HttpRequest::class, 'delete')]
#[CoversMethod(HttpRequest::class, 'status')]
#[CoversMethod(HttpRequest::class, 'asJson')]
#[CoversMethod(HttpRequest::class, 'asFormParams')]
#[CoversMethod(HttpRequest::class, 'asMultipart')]
#[CoversMethod(HttpResponse::class, 'isSuccess')]
#[CoversMethod(HttpResponse::class, 'isOk')]
#[CoversMethod(HttpResponse::class, 'isRedirect')]
#[CoversMethod(HttpResponse::class, 'isClientError')]
final class HttpTest extends TestCase
{
    #[Test]
    public function setsJsonBodyFormat(): void
    {
        $this->mockResponse([
            new Response(201, []),
        ]);

        $response = Http::asJson()
            ->post('https://example.com', ['key' => 'value']);

        $this->assertEquals(201, $response->status());

        $sentRequest = $this->container[0]['request'];
        $this->assertEquals('application/json', $sentRequest->getHeaderLine('Content-Type'));
        $this->assertEquals(json_encode(['key' => 'value']), (string) $sentRequest->getBody());
    }

    #[Test]
    public function setsFormParamsBodyFormat(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::asFormParams()
            ->post('https://example.com', ['field' => 'value']);

        $this->assertEquals(200, $response->status());

        $sentRequest = $this->container[0]['request'];
        $this->assertEquals('application/x-www-form-urlencoded', $sentRequest->getHeaderLine('Content-Type'));
        $this->assertEquals('field=value', (string) $sentRequest->getBody());
    }

    #[Test]
    public function proxies_method_calls_to_the_underlying_response(): void
    {
        $this->mockResponse([
            new Response(200, [], null, '1.1'),
        ]);

        $response = Http::get('https://example.com');

        $this->assertEquals('1.1', $response->getProtocolVersion());
    }

    #[Test]
    public function it_can_determine_if_the_response_is_success(): void
    {
        $this->mockResponse([
            new Response(200),
            new Response(200),
        ]);

        $this->assertTrue(Http::get('https://example.com')->isSuccess());
        $this->assertTrue(Http::get('https://example.com')->isOk());
    }

    #[Test]
    public function it_can_determine_if_the_response_is_redirect(): void
    {
        $this->mockResponse([
            new Response(302),
        ]);

        $this->assertTrue(Http::get('https://example.com')->isRedirect());
    }

    #[Test]
    public function it_can_determine_if_the_response_is_client_error(): void
    {
        $this->mockResponse([
            new Response(400),
        ]);

        $this->assertTrue(Http::get('https://example.com')->isClientError());
    }

    #[Test]
    public function it_sets_multipart_body_format(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::asMultipart()
            ->post('https://example.com', [
                [
                    'name'     => 'foo',
                    'contents' => 'bar',
                ],
            ]);

        $this->assertEquals(200, $response->status());

        // Validates what was sent
        $sentRequest = $this->container[0]['request'];
        $this->assertStringContainsString('multipart/form-data', $sentRequest->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function it_performs_patch_request(): void
    {
        $this->mockResponse();
        Http::patch('https://example.com', ['foo' => 'bar']);
        $this->assertEquals('PATCH', $this->container[0]['request']->getMethod());
    }

    #[Test]
    public function it_performs_put_request(): void
    {
        $this->mockResponse();
        Http::put('https://example.com', ['foo' => 'bar']);
        $this->assertEquals('PUT', $this->container[0]['request']->getMethod());
    }

    #[Test]
    public function it_performs_delete_request(): void
    {
        $this->mockResponse();
        Http::delete('https://example.com', ['foo' => 'bar']);
        $this->assertEquals('DELETE', $this->container[0]['request']->getMethod());
    }

    #[Test]
    public function it_parses_query_params_from_url(): void
    {
        $this->mockResponse();
        Http::get('https://example.com?foo=bar&baz=qux');

        $sentRequest = $this->container[0]['request'];
        $this->assertEquals('foo=bar&baz=qux', $sentRequest->getUri()->getQuery());
    }

    #[Test]
    public function it_uses_default_client_if_none_provided(): void
    {
        $request = new \Http\Actions\HttpRequest();
        $this->assertInstanceOf(Client::class, $request->client());
    }

    #[Test]
    public function it_throws_handle_request_exception_on_connect_error(): void
    {
        $this->mockResponse([
            new \GuzzleHttp\Exception\ConnectException('Connection failed',
                new \GuzzleHttp\Psr7\Request('GET', 'test')),
        ]);

        $this->expectException(\Http\Exceptions\HandleRequestException::class);
        $this->expectExceptionMessage('Connection failed');

        Http::get('https://example.com');
    }
}

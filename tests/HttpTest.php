<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Http;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    private array $container = [];

    protected function setUp(): void
    {
        $this->container = [];
    }

    private function mockResponse(array $responses = []): void
    {
        if (empty($responses)) {
            $responses = [new Response(200)];
        }

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->container));

        Http::swap(new Client(['handler' => $handlerStack]));
    }

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
    public function setsMultipartBodyFormat(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::asMultipart()
            ->post('https://example.com', [
                [
                    'name'     => 'foo',
                    'contents' => 'bar'
                ]
            ]);

        $this->assertEquals(200, $response->status());

        $sentRequest = $this->container[0]['request'];
        $this->assertStringContainsString('multipart/form-data', $sentRequest->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function performsPatchRequest(): void
    {
        $this->mockResponse();
        Http::patch('https://example.com', ['foo' => 'bar']);
        $this->assertEquals('PATCH', $this->container[0]['request']->getMethod());
    }

    #[Test]
    public function performsPutRequest(): void
    {
        $this->mockResponse();
        Http::put('https://example.com', ['foo' => 'bar']);
        $this->assertEquals('PUT', $this->container[0]['request']->getMethod());
    }

    #[Test]
    public function performsDeleteRequest(): void
    {
        $this->mockResponse();
        Http::delete('https://example.com', ['foo' => 'bar']);
        $this->assertEquals('DELETE', $this->container[0]['request']->getMethod());
    }

    #[Test]
    public function parsesQueryParamsFromUrl(): void
    {
        $this->mockResponse();
        Http::get('https://example.com?foo=bar&baz=qux');

        $sentRequest = $this->container[0]['request'];
        $this->assertEquals('foo=bar&baz=qux', $sentRequest->getUri()->getQuery());
    }

    #[Test]
    public function it_uses_default_client_if_none_provided(): void
    {
        // This test doesn't use Http::swap(), so MakeHttpRequest will instantiate its own Client.
        // We can't easily mock the response without swap(), but we can at least verify it doesn't crash
        // and covers the default branch in constructor.

        $request = new \Http\Actions\MakeHttpRequest();
        $this->assertInstanceOf(Client::class, $request->client());
    }

    #[Test]
    public function throwsHandleRequestExceptionOnConnectError(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new \GuzzleHttp\Psr7\Request('GET', 'test')),
        ]);
        $handlerStack = HandlerStack::create($mock);
        Http::swap(new Client(['handler' => $handlerStack]));

        $this->expectException(\Http\Exceptions\HandleRequestException::class);
        $this->expectExceptionMessage('Connection failed');

        Http::get('https://example.com');
    }
}

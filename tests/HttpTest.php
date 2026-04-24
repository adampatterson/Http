<?php

use GuzzleHttp\Client;
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
    public function returnsResponseWithStatusCode(): void
    {
        $this->mockResponse([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $this->assertEquals(200, Http::get('https://example.com')->status());
    }

    #[Test]
    public function returnsResponseBody(): void
    {
        $body = json_encode(['id' => 1, 'name' => 'Test']);
        $this->mockResponse([
            new Response(200, [], $body),
        ]);

        $response = Http::get('https://example.com');
        $this->assertEquals($body, $response->body());
    }

    #[Test]
    public function decodesJsonResponse(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $this->mockResponse([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($data)),
        ]);

        $response = Http::get('https://example.com');
        $this->assertEquals($data, $response->json());
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
    public function checksSuccessfulResponse(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::get('https://example.com');
        $this->assertTrue($response->isSuccess());
    }

    #[Test]
    public function checksOkResponse(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::get('https://example.com');
        $this->assertTrue($response->isOk());
    }

    #[Test]
    public function checksClientErrorResponse(): void
    {
        $this->mockResponse([
            new Response(400, []),
        ]);

        $response = Http::get('https://example.com');
        $this->assertTrue($response->isClientError());
    }

    #[Test]
    public function checksServerErrorResponse(): void
    {
        $this->mockResponse([
            new Response(500, []),
        ]);

        $response = Http::get('https://example.com');
        $this->assertTrue($response->isServerError());
    }

    #[Test]
    public function checksRedirectResponse(): void
    {
        $this->mockResponse([
            new Response(301, []),
        ]);

        $response = Http::get('https://example.com');
        $this->assertTrue($response->isRedirect());
    }

    #[Test]
    public function returnsResponseHeaders(): void
    {
        $this->mockResponse([
            new Response(200, ['X-Custom-Header' => 'test-value']),
        ]);

        $response = Http::get('https://example.com');
        $this->assertEquals('test-value', $response->header('X-Custom-Header'));
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
}

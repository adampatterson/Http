<?php

use Http\Http;
use Http\MakeHttpRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    #[Test]
    public function returnsResponseWithStatusCode(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->assertEquals(200, $this->makeRequestWithMockClient($client)->status());
    }

    #[Test]
    public function returnsResponseBody(): void
    {
        $body = json_encode(['id' => 1, 'name' => 'Test']);
        $mock = new MockHandler([
            new Response(200, [], $body),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertEquals($body, $response->body());
    }

    #[Test]
    public function decodesJsonResponse(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($data)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertEquals($data, $response->json());
    }

    #[Test]
    public function setsJsonBodyFormat(): void
    {
        $mock = new MockHandler([
            new Response(201, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $request = new class extends MakeHttpRequest {
            private Client $mockClient;

            public function setMockClient(Client $client): static
            {
                $this->mockClient = $client;
                return $this;
            }

            public function client(): Client
            {
                return $this->mockClient;
            }
        };

        $response = $request->setMockClient($client)->asJson()->post('https://example.com', ['key' => 'value']);
        $this->assertEquals(201, $response->status());
    }

    #[Test]
    public function setsFormParamsBodyFormat(): void
    {
        $mock = new MockHandler([
            new Response(200, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $request = new class extends MakeHttpRequest {
            private Client $mockClient;

            public function setMockClient(Client $client): static
            {
                $this->mockClient = $client;
                return $this;
            }

            public function client(): Client
            {
                return $this->mockClient;
            }
        };

        $response = $request->setMockClient($client)->asFormParams()->post('https://example.com', ['field' => 'value']);
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function setsAuthorizationHeader(): void
    {
        $mock = new MockHandler([
            new Response(200, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $request = new class extends MakeHttpRequest {
            private Client $mockClient;

            public function setMockClient(Client $client): static
            {
                $this->mockClient = $client;
                return $this;
            }

            public function client(): Client
            {
                return $this->mockClient;
            }
        };

        $response = $request->setMockClient($client)->withToken('my-secret-token')->get('https://example.com');
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function addsCustomHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $request = new class extends MakeHttpRequest {
            private Client $mockClient;

            public function setMockClient(Client $client): static
            {
                $this->mockClient = $client;
                return $this;
            }

            public function client(): Client
            {
                return $this->mockClient;
            }
        };

        $response = $request->setMockClient($client)->withHeaders(['X-Custom' => 'value'])->get('https://example.com');
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function checksSuccessfulResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertTrue($response->isSuccess());
    }

    #[Test]
    public function checksOkResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertTrue($response->isOk());
    }

    #[Test]
    public function checksClientErrorResponse(): void
    {
        $mock = new MockHandler([
            new Response(400, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertTrue($response->isClientError());
    }

    #[Test]
    public function checksServerErrorResponse(): void
    {
        $mock = new MockHandler([
            new Response(500, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertTrue($response->isServerError());
    }

    #[Test]
    public function checksRedirectResponse(): void
    {
        $mock = new MockHandler([
            new Response(301, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertTrue($response->isRedirect());
    }

    #[Test]
    public function returnsResponseHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, ['X-Custom-Header' => 'test-value']),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);
        $this->assertEquals('test-value', $response->header('X-Custom-Header'));
    }

    #[Test]
    public function proxies_method_calls_to_the_underlying_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], null, '1.1'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $this->makeRequestWithMockClient($client);

        $this->assertEquals('1.1', $response->getProtocolVersion());
    }

    private function makeRequestWithMockClient(Client $client): mixed
    {
        $request = new class extends MakeHttpRequest {
            private Client $mockClient;

            public function setMockClient(Client $client): static
            {
                $this->mockClient = $client;
                return $this;
            }

            public function client(): Client
            {
                return $this->mockClient;
            }
        };

        return $request->setMockClient($client)->get('https://example.com');
    }
}

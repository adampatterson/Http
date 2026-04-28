<?php

namespace Http\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Http\Http;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected array $container = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = [];
        Http::clearCookieJar();
    }

    protected function mockResponse(array $responses = []): void
    {
        if (empty($responses)) {
            $responses = [new Response(200)];
        }

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->container));

        Http::swap(new Client(['handler' => $handlerStack]));
    }
}

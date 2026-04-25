<?php

namespace Http\Tests;

use GuzzleHttp\Client;
use Http\Http;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HttpFacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the static client after each test to ensure isolation
        $reflection = new ReflectionClass(Http::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    #[Test]
    public function it_can_swap_the_client(): void
    {
        $client = new Client();
        Http::swap($client);

        $reflection = new ReflectionClass(Http::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        
        $this->assertSame($client, $property->getValue());
    }

    #[Test]
    public function it_lazily_instantiates_a_client(): void
    {
        $reflection = new ReflectionClass(Http::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);

        $this->assertNull($property->getValue());

        // Trigger __callStatic
        try {
            // We expect a Guzzle exception or similar because we're making a real request to a non-existent URL,
            // but the goal is to trigger the client instantiation.
            Http::get('http://localhost:1');
        } catch (\Exception $e) {
            // Silence exceptions
        }

        $this->assertInstanceOf(Client::class, $property->getValue());
    }
}

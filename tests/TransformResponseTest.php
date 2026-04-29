<?php

declare(strict_types=1);

namespace Http\Tests;

use GuzzleHttp\Psr7\Response;
use Http\Traits\TransformResponse;
use JsonException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversTrait(TransformResponse::class)]
#[CoversMethod(TransformResponse::class, 'collect')]
#[CoversMethod(TransformResponse::class, 'array')]
#[CoversMethod(TransformResponse::class, 'object')]
#[CoversMethod(TransformResponse::class, 'toJson')]
final class TransformResponseTest extends TestCase
{
    private function makeSubject(string $body): object
    {
        return new class (new Response(200, [], $body)) {
            use TransformResponse;

            private Response $response;

            public function __construct(Response $response)
            {
                $this->response = $response;
            }
        };
    }

    #[Test]
    public function collect_returns_collection_of_decoded_payload(): void
    {
        $subject = $this->makeSubject('{"foo":"bar"}');

        $collection = $subject->collect();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(['foo' => 'bar'], $collection->toArray());
    }

    #[Test]
    public function array_returns_decoded_payload_as_array(): void
    {
        $subject = $this->makeSubject('{"foo":"bar","count":1}');

        $this->assertSame(['foo' => 'bar', 'count' => 1], $subject->array());
    }

    #[Test]
    public function object_decodes_json_to_associative_array(): void
    {
        $subject = $this->makeSubject('{"foo":"bar"}');

        $this->assertSame(['foo' => 'bar'], $subject->array());
    }

    #[Test]
    public function to_json_returns_json_encoded_payload(): void
    {
        $subject = $this->makeSubject('{"foo":"bar","count":1}');

        $this->assertSame('{"foo":"bar","count":1}', $subject->toJson());
    }

    #[Test]
    public function object_throws_json_exception_for_invalid_json(): void
    {
        $subject = $this->makeSubject('not-json');

        $this->expectException(JsonException::class);

        $subject->object();
    }
}

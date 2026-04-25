<?php

namespace Http;

use GuzzleHttp\Client;
use Http\Actions\MakeHttpRequest;

/**
 * Class Http
 *
 * @package Http
 * @author Adam Patterson <http://github.com/adampatterson>
 * @link  https://github.com/adampatterson/Http
 *
 * @mixin MakeHttpRequest
 */
class Http
{
    /**
     * @var Client|null
     */
    protected static ?Client $client = null;

    /**
     * Swap the client instance.
     *
     * This is primarily useful for testing, allowing a mock or fake Guzzle
     * client to be injected so that HTTP calls can be simulated without
     * making real network requests.
     *
     * @param  Client  $client  A custom or mock Guzzle client instance.
     * @return void
     */
    public static function swap(Client $client): void
    {
        static::$client = $client;
    }

    /**
     * Handles static calls to the MakeHttpRequest instance.
     *
     * @param  string  $method
     * @param  array  $args
     *
     * @return mixed
     *
     * @mixin MakeHttpRequest
     */
    public static function __callStatic(string $method, array $args)
    {
        if (static::$client === null) {
            static::$client = new Client();
        }

        return MakeHttpRequest::new(static::$client)->{$method}(...$args);
    }
}

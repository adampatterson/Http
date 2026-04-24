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
     * @param  Client  $client
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
        return MakeHttpRequest::new(static::$client)->{$method}(...$args);
    }
}

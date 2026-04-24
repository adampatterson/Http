<?php

namespace Http;

use GuzzleHttp\Client;

/**
 * Class Http
 *
 * @package Http
 * @author Adam Patterson <http://github.com/adampatterson>
 * @link  https://github.com/adampatterson/Http
 */
class Http
{
    /**
     * Handles static calls to the MakeHttpRequest instance.
     *
     * @param  string  $method
     * @param  array  $args
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return MakeHttpRequest::new()->{$method}(...$args);
    }
}

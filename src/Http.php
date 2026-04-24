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
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return MakeHttpRequest::new()->{$method}(...$args);
    }
}

<?php

namespace Http;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use Http\Actions\HttpRequest;
use Http\Actions\HttpResponse;

/**
 * Class Http
 *
 * @package Http
 * @author Adam Patterson <http://github.com/adampatterson>
 * @link  https://github.com/adampatterson/http
 *
 * @mixin HttpRequest
 * @mixin HttpResponse
 */
class Http
{
    /**
     * @var Client|null
     */
    protected static ?Client $client = null;

    /**
     * Shared cookie jar used as a default across facade requests.
     *
     * @var CookieJarInterface|bool|null
     */
    protected static CookieJarInterface|bool|null $defaultCookieJar = null;

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
     * Configure the default cookie jar for all subsequent facade requests.
     *
     * Passing true creates a new in-memory jar.
     * Passing false disables cookie handling by default.
     *
     * @param  CookieJarInterface|bool  $cookieJar
     * @return void
     */
    public static function useCookieJar(CookieJarInterface|bool $cookieJar = true): void
    {
        static::$defaultCookieJar = $cookieJar === true ? new CookieJar() : $cookieJar;
    }

    /**
     * Remove the shared facade cookie jar default.
     */
    public static function clearCookieJar(): void
    {
        static::$defaultCookieJar = null;
    }

    /**
     * Get the currently configured shared facade cookie jar.
     */
    public static function cookieJar(): ?CookieJarInterface
    {
        return static::$defaultCookieJar instanceof CookieJarInterface
            ? static::$defaultCookieJar
            : null;
    }

    /**
     * Handles static calls to the MakeHttpRequest instance.
     *
     * @param  string  $method
     * @param  array  $args
     *
     *
     * @return HttpResponse|HttpRequest
     *
     * @method static HttpRequest asJson()
     * @method static HttpRequest asFormParams()
     * @method static HttpRequest asMultipart()
     * @method static HttpRequest withToken($token, $type = 'Bearer')
     * @method static HttpRequest withHeaders($headers)
     * @method static HttpRequest withCookies(array $cookies, ?string $domain = null)
     * @method static HttpRequest withCookieJar(\GuzzleHttp\Cookie\CookieJarInterface|bool $cookieJar = true)
     * @method static HttpRequest timeout(int $seconds)
     * @method static HttpRequest retry(int $times, int $sleepMilliseconds = 0, ?callable $when = null)
     * @method static HttpRequest get(string $url, mixed $query = null)
     * @method static HttpRequest post(string $url, mixed $params = null)
     * @method static HttpRequest patch(string $url, mixed $params = null)
     * @method static HttpRequest put(string $url, mixed $params = null)
     * @method static HttpRequest delete(string $url, mixed $params = null)
     *
     * @method static string body()
     * @method static mixed object()
     * @method static string header($header)
     * @method static array headers()
     * @method static int status()
     * @method static \Psr\Http\Message\UriInterface effectiveUri()
     * @method static bool isSuccess()
     * @method static bool isOk()
     * @method static bool successful()
     * @method static bool failed()
     * @method static bool isRedirect()
     * @method static bool isClientError()
     * @method static bool clientError()
     * @method static bool isServerError()
     * @method static bool serverError()
     * @method static HttpResponse onError(callable $callback)
     * @method static HttpResponse throw()
     * @method static HttpResponse throwIf(bool $condition)
     * @method static mixed cookies()
     */
    public static function __callStatic(string $method, array $args): HttpResponse|HttpRequest
    {
        if (static::$client === null) {
            static::$client = new Client();
        }

        return HttpRequest::new(static::$client, static::$defaultCookieJar)->{$method}(...$args);
    }
}

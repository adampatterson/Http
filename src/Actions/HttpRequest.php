<?php

namespace Http\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\TransferStats;
use Http\Enums\BodyFormat;
use Http\Enums\ContentType;
use Http\Enums\PostMethod;
use Http\Enums\TokenType;
use Http\Exceptions\HandleRequestException;
use Throwable;

/**
 * Builds and executes HTTP requests, then enriches the response wrapper.
 *
 * Flow:
 * - Collect request defaults and fluent configuration (headers, auth, body format).
 * - Normalize verb-specific payload options and merge runtime overrides.
 * - Execute the Guzzle request, capture transfer stats, and wrap the response.
 * - Pass request metadata (cookies and stats) to HttpResponse helpers.
 *
 * @package Http
 */
class HttpRequest
{

    /**
     * @var string
     */
    private string $bodyFormat;

    /**
     * @var array
     */
    private array $options;

    /**
     * @var mixed
     */
    private mixed $cookies;

    /**
     * @var string|null
     */
    private ?string $cookieDomain = null;

    /**
     * @var TransferStats
     */
    private TransferStats $transferStats;

    /**
     * @var Client|null
     */
    private ?Client $client;

    /**
     * @var int
     */
    private int $tries = 1;

    /**
     * @var int
     */
    private int $retryDelay = 0;

    /**
     * @var callable|null
     */
    private $retryWhenCallback;

    /**
     * MakeHttpRequest constructor.
     *
     * @param  Client|null  $client
     * @param  CookieJarInterface|bool|null  $defaultCookieJar
     */
    public function __construct(?Client $client = null, CookieJarInterface|bool|null $defaultCookieJar = null)
    {
        $this->client = $client ?: new Client();

        $this->bodyFormat = BodyFormat::JSON->value;
        $this->cookies = $defaultCookieJar;

        // @todo configure options
        $this->options = [
            'headers'     => [],
            'http_errors' => false,
        ];

        if ($defaultCookieJar !== null) {
            $this->options['cookies'] = $defaultCookieJar;
        }
    }

    /**
     * @param  mixed  ...$args
     *
     * @return HttpRequest
     */
    public static function new(...$args): self
    {
        return new self(...$args);
    }

    /**
     * Configure JSON payload flow by setting body format and Content-Type.
     *
     * @return $this
     */
    public function asJson()
    {
        return $this->bodyFormat(BodyFormat::JSON)
            ->contentType(ContentType::JSON);
    }

    /**
     * Configure form-data payload flow by setting body format and Content-Type.
     *
     * @return $this
     */
    public function asFormParams()
    {
        return $this->bodyFormat(BodyFormat::FORM_DATA)
            ->contentType(ContentType::FORM_DATA);
    }

    /**
     * Configure multipart payload flow for file and mixed-part uploads.
     *
     * @return $this
     */
    public function asMultipart()
    {
        return $this->bodyFormat(BodyFormat::MULTIPART);
    }

    /**
     * @param  BodyFormat  $format
     *
     * @return $this
     */
    private function bodyFormat(BodyFormat $format): static
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format->value;
        });
    }

    /**
     * @param  ContentType  $contentType
     *
     * @return $this
     */
    private function contentType(ContentType $contentType): static
    {
        return $this->withHeaders(['Content-Type' => $contentType->value]);
    }

    /**
     * @param $token
     * @param  TokenType  $type
     *
     * @return $this
     */
    public function withToken($token, TokenType $type = TokenType::BEARER): static
    {
        $this->options['headers']['Authorization'] = trim($type->value.' '.$token);

        return $this;
    }

    /**
     * @param  string  $username
     * @param  string  $password
     * @return $this
     */
    public function withBasicAuth(string $username, string $password): mixed
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password],
            ]);
        });
    }

    /**
     * Configure request cookies using an associative array.
     *
     * Guzzle expects the `cookies` option to be a CookieJarInterface (or bool),
     * so array values are normalized to a CookieJar during send().
     *
     * @param  array<string, string>  $cookies
     * @param  string|null  $domain
     *
     * @return $this
     */
    public function withCookies(array $cookies, ?string $domain = null): static
    {
        $this->cookies = $cookies;
        $this->cookieDomain = $domain;

        return $this;
    }

    /**
     * Configure request cookies using an existing cookie jar.
     *
     * Passing true creates a fresh in-memory jar for the request chain.
     * Passing false disables cookie handling.
     *
     * @param  CookieJarInterface|bool  $cookieJar
     *
     * @return $this
     */
    public function withCookieJar(CookieJarInterface|bool $cookieJar = true): static
    {
        $this->cookies = $cookieJar === true ? new CookieJar() : $cookieJar;
        $this->cookieDomain = null;

        return tap($this, function () {
            return $this->options = array_merge_recursive($this->options, [
                'cookies' => $this->cookies,
            ]);
        });
    }

    /**
     * @param $headers
     *
     * @return $this
     */
    public function withHeaders($headers): mixed
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Set the timeout for the request in seconds.
     *
     * @param  int  $seconds
     * @return $this
     */
    public function timeout(int $seconds): static
    {
        $this->options['timeout'] = $seconds;

        return $this;
    }

    /**
     * Specify the number of times the request should be attempted.
     *
     * @param  int  $times
     * @param  int  $sleepMilliseconds
     * @param  callable|null  $when
     * @return $this
     */
    public function retry(int $times, int $sleepMilliseconds = 0, ?callable $when = null): static
    {
        $this->tries = $times;
        $this->retryDelay = $sleepMilliseconds;
        $this->retryWhenCallback = $when;

        return $this;
    }

    /**
     * @param  string  $url
     * @param  mixed  $query
     *
     * @return HttpResponse
     * @throws HandleRequestException
     */
    public function get(string $url, mixed $query = null): HttpResponse
    {
        return $this->send(method: PostMethod::GET, url: $url, options: [
            'query' => $query,
        ]);
    }

    /**
     * @param  string  $url
     * @param  mixed  $params
     *
     * @return HttpResponse
     * @throws HandleRequestException
     */
    public function post(string $url, mixed $params = null): HttpResponse
    {
        return $this->send(method: PostMethod::POST, url: $url, options: [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param  string  $url
     * @param  mixed  $params
     *
     * @return HttpResponse
     * @throws HandleRequestException
     */
    public function patch(string $url, mixed $params = null): HttpResponse
    {
        return $this->send(method: PostMethod::PATCH, url: $url, options: [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param  string  $url
     * @param  mixed  $params
     *
     * @return HttpResponse
     * @throws HandleRequestException
     */
    public function put(string $url, mixed $params = null): HttpResponse
    {
        return $this->send(method: PostMethod::PUT, url: $url, options: [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param  string  $url
     * @param  mixed  $params
     *
     * @return HttpResponse
     * @throws HandleRequestException
     */
    public function delete(string $url, mixed $params = null): HttpResponse
    {
        return $this->send(method: PostMethod::DELETE, url: $url, options: [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * Execute the request lifecycle and return a wrapped response.
     *
     * Flow:
     * - Merge instance defaults with call-specific options.
     * - Parse query parameters already present in the URL.
     * - Capture transfer stats via Guzzle's on_stats callback.
     * - Wrap the PSR-7 response in HttpResponse and attach metadata.
     *
     * @param  PostMethod  $method
     * @param  string  $url
     * @param  array  $options
     *
     * @return HttpResponse
     *
     * @throws HandleRequestException
     */
    public function send(PostMethod $method, string $url, array $options = []): HttpResponse
    {
        $requestOptions = $this->mergeOptions([
            'query'    => $this->parseQueryParams($url),
            'on_stats' => function ($transferStats) {
                $this->transferStats = $transferStats;
            },
        ], $options);

        $resolvedCookies = $this->resolveCookiesOption($url);

        if ($resolvedCookies !== null) {
            $this->cookies = $resolvedCookies;
            $requestOptions['cookies'] = $resolvedCookies;
        }

        return $this->retryRequest(function () use ($method, $url, $requestOptions) {
            try {
                return tap(new HttpResponse($this->client()
                    ->request($method->value,
                        $url,
                        $requestOptions)),
                    function ($response) {
                        $response->cookies = $this->cookies;
                        $response->transferStats = $this->transferStats;
                    });
            } catch (ConnectException $exception) {
                throw new HandleRequestException($exception->getMessage(), 0, $exception);
            }
        });
    }

    /**
     * Retry the given callback the configured number of times.
     *
     * @param  callable  $callback
     * @return HttpResponse
     *
     * @throws HandleRequestException
     */
    private function retryRequest(callable $callback): HttpResponse
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                $response = $callback();

                if ($attempts >= $this->tries || ! $response->failed()) {
                    return $response;
                }

                if ($this->retryWhenCallback && ! ($this->retryWhenCallback)($response->toPsrResponse() ?? $response, $this)) {
                    return $response;
                }
            } catch (Throwable $e) {
                if ($attempts >= $this->tries) {
                    throw $e;
                }

                if ($this->retryWhenCallback && ! ($this->retryWhenCallback)($e, $this)) {
                    throw $e;
                }
            }

            if ($this->retryDelay > 0) {
                usleep($this->retryDelay * 1000);
            }
        }
    }

    /**
     * Resolve configured cookies into a Guzzle-compatible request option.
     *
     * @param  string  $url
     *
     * @return CookieJarInterface|bool|null
     */
    private function resolveCookiesOption(string $url): CookieJarInterface|bool|null
    {
        if ($this->cookies === null) {
            return null;
        }

        if ($this->cookies instanceof CookieJarInterface || is_bool($this->cookies)) {
            return $this->cookies;
        }

        if (! is_array($this->cookies)) {
            return null;
        }

        $domain = $this->cookieDomain ?: (string) parse_url($url, PHP_URL_HOST);

        if ($domain === '') {
            $domain = 'localhost';
        }

        return CookieJar::fromArray($this->cookies, $domain);
    }

    /**
     * Merge base request options with one or more runtime option arrays.
     *
     * @param ...$options
     *
     * @return array
     */
    public function mergeOptions(...$options): array
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * Extract query parameters from a URL into an associative array.
     *
     * @param $url
     *
     * @return mixed
     */
    public function parseQueryParams($url): mixed
    {
        return tap([], static function (&$query) use ($url) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        });
    }

    /**
     * @return Client
     */
    public function client(): Client
    {
        return $this->client;
    }
}


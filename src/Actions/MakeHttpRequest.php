<?php

namespace Http\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Http\Exceptions\HandleRequestException;
use Http\Response\HttpResponse;
use Illuminate\Support\Collection;

/**
 * Class MakeHttpRequest
 * @package Http
 */
class MakeHttpRequest
{

    /**
     * @var string
     */
    private string $bodyFormat = 'json';

    /**
     * @var array
     */
    private array $options;

    /**
     * @var \GuzzleHttp\Psr7\Response
     */
    private $response;

    /**
     * @var mixed
     */
    private $cookies;

    /**
     * @var \GuzzleHttp\TransferStats
     */
    private $transferStats;

    /**
     * @var Client|null
     */
    private ?Client $client;

    /**
     * MakeHttpRequest constructor.
     * @param  Client|null  $client
     */
    public function __construct(?Client $client = null)
    {
        $this->client = $client;

        $this->options = [
            'headers'     => [],
            'http_errors' => false,
        ];
    }

    /**
     * @param  mixed  ...$args
     *
     * @return MakeHttpRequest
     */
    public static function new(...$args): self
    {
        return new self(...$args);
    }

    /**
     * @return $this
     */
    public function asJson()
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    /**
     * @return $this
     */
    public function asFormParams()
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    /**
     * @return $this
     */
    public function asMultipart()
    {
        return $this->bodyFormat('multipart');
    }

    /**
     * @param $format
     *
     * @return $this
     */
    public function bodyFormat($format)
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    /**
     * @param $contentType
     *
     * @return $this
     */
    public function contentType($contentType)
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    /**
     * @param $token
     * @param  string  $type
     *
     * @return $this
     */
    public function withToken($token, $type = 'Bearer')
    {
        $this->options['headers']['Authorization'] = trim($type.' '.$token);

        return $this;
    }

    /**
     * @param $headers
     *
     * @return $this
     */
    public function withHeaders($headers)
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
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
        return $this->send('GET', $url, [
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
        return $this->send('POST', $url, [
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
        return $this->send('PATCH', $url, [
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
        return $this->send('PUT', $url, [
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
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * Send the request to the given URL.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     *
     * @return HttpResponse
     *
     * @throws HandleRequestException
     */
    public function send(string $method, string $url, array $options = []): HttpResponse
    {
        try {
            return tap(new HttpResponse($this->client()->request($method, $url, $this->mergeOptions([
                'query'    => $this->parseQueryParams($url),
                'on_stats' => function ($transferStats) {
                    $this->transferStats = $transferStats;
                },
            ], $options))), function ($response) {
                $response->cookies = $this->cookies;
                $response->transferStats = $this->transferStats;
            });
        } catch (ConnectException $e) {
            throw new HandleRequestException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param ...$options
     *
     * @return array
     */
    public function mergeOptions(...$options): array
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function parseQueryParams($url): mixed
    {
        return tap([], static function (&$query) use ($url) {
            parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        });
    }

    /**
     * @return Client
     */
    public function client(): Client
    {
        return $this->client ?: new Client();
    }
}


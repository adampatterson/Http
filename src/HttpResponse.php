<?php

namespace Http;


use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Override;

/**
 * Class HttpResponse
 * @package Http
 */
class HttpResponse
{
    /**
     * @var Response
     */
    private Response $response;

    /**
     * @var mixed
     */
    public mixed $cookies;

    /**
     * @var TransferStats
     */
    public TransferStats $transferStats;

    /**
     * HttpResponse constructor.
     * @param $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return (string)$this->response->getBody();
    }

    /**
     * @return mixed
     * @throws JsonException
     * @throws \JsonException
     */
    public function json(): mixed
    {
        return json_decode(
            $this->response->getBody(),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param $header
     *
     * @return string
     */
    public function header($header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * @return array
     */
    public function headers(): array
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    /**
     * @return int
     */
    public function status()
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return \Psr\Http\Message\UriInterface
     */
    public function effectiveUri()
    {
        return $this->transferStats->getEffectiveUri();
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->isSuccess();
    }

    /**
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * @return mixed
     */
    public function cookies(): mixed
    {
        return $this->cookies;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * Proxy unknown method calls to the underlying PSR-7 response instance.
     *
     * @param string $method
     * @param array<int, mixed> $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->response->{$method}(...$args);
    }
}

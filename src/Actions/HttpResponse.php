<?php

namespace Http\Actions;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Http\Traits\TransformResponse;
use Psr\Http\Message\UriInterface;

/**
 * Wraps a PSR-7 response with convenience helpers used by this package.
 *
 * Flow role:
 * - Receives the raw response from MakeHttpRequest::send().
 * - Exposes common helpers (body/json/status/headers) for callers.
 * - Stores metadata copied from the request phase (cookies, transfer stats).
 * - Proxies unknown methods to the underlying Guzzle response.
 *
 * @package Http
 *
 * @mixin Response
 */
class HttpResponse
{
    use TransformResponse;

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
        return (string) $this->response->getBody();
    }

    /**
     * @param $header
     *
     * @return string
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * @return array
     */
    public function headers(): array
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($value, $key) {
            return [$key => $value[0]];
        })->all();
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Return the final effective URI reported by Guzzle transfer stats.
     */
    public function effectiveUri(): UriInterface
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
     * @return bool
     */
    public function successful(): bool
    {
        return $this->isSuccess();
    }

    /**
     * @return bool
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * @return bool
     */
    public function clientError(): bool
    {
        return $this->isClientError();
    }

    /**
     * @return bool
     */
    public function serverError(): bool
    {
        return $this->isServerError();
    }

    /**
     * Execute the given callback if there was a client or server error.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function onError(callable $callback): static
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Throw an exception if a client or server error occurred.
     *
     * @return $this
     *
     * @throws \Http\Exceptions\HandleRequestException
     */
    public function throw(): static
    {
        if ($this->failed()) {
            throw new \Http\Exceptions\HandleRequestException(
                "HTTP request returned status code {$this->status()}",
                $this->status()
            );
        }

        return $this;
    }

    /**
     * Throw an exception if a client or server error occurred and the given condition is true.
     *
     * @param  bool  $condition
     * @return $this
     *
     * @throws \Http\Exceptions\HandleRequestException
     */
    public function throwIf(bool $condition): static
    {
        if ($condition && $this->failed()) {
            return $this->throw();
        }

        return $this;
    }

    /**
     * Return cookies captured during the request flow.
     *
     * @return mixed
     */
    public function cookies(): mixed
    {
        return $this->cookies;
    }

    /**
     * Get the underlying PSR-7 response.
     *
     * @return Response
     */
    public function toPsrResponse(): Response
    {
        return $this->response;
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
     * @param  string  $method
     * @param  array<int, mixed>  $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return $this->response->{$method}(...$args);
    }
}

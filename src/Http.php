<?php

namespace Http;

use GuzzleHttp\Client;

/**
 * Class Http
 * @package Http
 * @author Adam Patterson <http://github.com/adampatterson>
 * @link  https://github.com/adampatterson/Http
 */
// https://github.com/kitetail/zttp/blob/master/src/Zttp.php
// https://github.com/kitetail/zttp/blob/master/tests/ZttpTest.php
// https://medium.com/@taylorotwell/tap-tap-tap-1fc6fc1f93a6
class Http
{

    public static function __callStatic($method, $args)
    {
        return MakeHttpRequest::new()->{$method}(...$args);
    }
}

class MakeHttpRequest
{

    protected $json;

    private $bodyFormat;
    private $options;
    private $cookies;
    private $beforeSendingCallbacks;

    private function __construct()
    {
        $this->beforeSendingCallbacks = collect(function ($request, $options) {
            $this->cookies = $options['cookies'];
        });

        $this->bodyFormat = 'json';
        $this->options    = [
            'headers'     => [],
            'http_errors' => false,
        ];
    }

    /**
     * Returns a static self instance
     *
     * @param  mixed  ...$args
     *
     * @return HttpRequest
     */
    static function new(...$args)
    {
        return new self(...$args);
    }

    /**
     * @param $token
     * @param  string  $type
     *
     * @return $this
     */
    public function withToken($token, $type = 'Bearer')
    {
        $this->withHeaders(['Authorization' => trim($type.' '.$token)]);

        return $this;
    }

    /**
     * @param $contentType
     *
     * @return mixed
     */
    function contentType($contentType)
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    /**
     * @param $header
     *
     * @return mixed
     */
    function accept($header)
    {
        return $this->withHeaders(['Accept' => $header]);
    }

    /**
     * @param $headers
     *
     * @return mixed
     */
    function withHeaders($headers)
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * @param $username
     * @param $password
     *
     * @return mixed
     */
    function withBasicAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [
                    $username,
                    $password
                ],
            ]);
        });
    }

    /**
     * @param $cookies
     *
     * @return mixed
     */
    function withCookies($cookies)
    {
        return tap($this, function ($request) use ($cookies) {
            return $this->options = array_merge_recursive($this->options, [
                'cookies' => $cookies,
            ]);
        });
    }

    /**
     * @param  string  $url
     * @param  null  $query
     *
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    public function get(string $url, $query = null)
    {
        return $this->send('GET', $url, [
            'query' => $query,
        ]);
    }

    /**
     * @param  string  $url
     * @param  null  $params
     *
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    public function post(string $url, $params = null)
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param  string  $url
     * @param  null  $params
     *
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    public function patch(string $url, $params = null)
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param  string  $url
     * @param  null  $params
     *
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    public function put(string $url, $params = null)
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * @param  string  $url
     * @param  null  $params
     *
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    public function delete(string $url, $params = null)
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params,
        ]);
    }


    /**
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     *
     * @return mixed
     * @throws HandleRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(string $method, string $url, array $options = [])
    {
        try {
            return tap(new HttpResponse($this->client()->request($method, $url, $this->mergeOptions([
                'query'    => $this->parseQueryParams($url),
                'on_stats' => function ($transferStats) {
                    $this->transferStats = $transferStats;
                }
            ], $options))), function ($response) {
                $response->cookies       = $this->cookies;
                $response->transferStats = $this->transferStats;
            });

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new HandleRequestException($e->getMessage(), 0, $e);
        }
    }

    /**
     * https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html
     *
     * @return Client
     */
    public function client()
    {
        return new \GuzzleHttp\Client([
            'cookies' => true
        ]);
    }

    /**
     * @param  mixed  ...$options
     *
     * @return array
     */
    public function mergeOptions(...$options)
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function parseQueryParams($url)
    {
        return tap([], function (&$query) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
        });
    }
}

class HttpResponse
{

    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function body()
    {
        return (string) $this->response->getBody();
    }

    /**
     * @return mixed
     */
    public function json()
    {
        if ( ! isset($this->json)) {
            $this->json = (array) json_decode($this->response->getBody(), true);
        }

        return $this->json;
    }

    public function header($header)
    {
        return $this->response->getHeaderLine($header);
    }

    public function headers()
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values[0]];
        })->all();
    }

    public function status()
    {
        return $this->response->getStatusCode();
    }

    public function effectiveUri()
    {
        return $this->transferStats->getEffectiveUri();
    }

    public function isSuccess()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function isOk()
    {
        return $this->isSuccess();
    }

    public function isRedirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function isClientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function isServerError()
    {
        return $this->status() >= 500;
    }

    public function cookies()
    {
        return $this->cookies;
    }
}

class HttpRequest
{

    function __construct($request)
    {
        $this->request = $request;
    }

    function url()
    {
        return (string) $this->request->getUri();
    }

    function method()
    {
        return $this->request->getMethod();
    }
}

class HandleRequestException extends \Exception { }

// https://medium.com/@taylorotwell/tap-tap-tap-1fc6fc1f93a6
function tap($value, $callback)
{
    $callback($value);
    return $value;
}
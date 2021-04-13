<?php

namespace Http;

use GuzzleHttp\Client;

/**
 * Class Http
 * @package Http
 * @author Adam Patterson <http://github.com/adampatterson>
 * @link  https://github.com/adampatterson/Http
 */
class Http
{

    public static function __callStatic($method, $args)
    {
        return MakeHttpRequest::new()->{$method}(...$args);
    }
}

class MakeHttpRequest
{

    private $bodyFormat = 'json';
    private $options = [];
    private $response;

    private function __construct()
    {
        $this->options = [
            'headers'     => [],
            'http_errors' => false,
        ];
    }

    /**
     * @param  mixed  ...$args
     *
     * @return HttpRequest
     */
    static function new(...$args)
    {
        return new self(...$args);
    }

        function asJson()
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    function asFormParams()
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    function asMultipart()
    {
        return $this->bodyFormat('multipart');
    }
    
    function bodyFormat($format)
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
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
     * Send the request to the given URL.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     *
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Exception
     */
    public function send(string $method, string $url, array $options = [])
    {
        try {
            $this->response = $this->client()->request($method, $url, [
                'headers' => $this->options['headers']
            ]);

            return $this;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new HandleRequestException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return Client
     */
    public function client()
    {
        return new \GuzzleHttp\Client();
    }

    /**
     * @return mixed
     */
    function status()
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return string
     */
    function body()
    {
        return (string) $this->response->getBody();
    }

    /**
     * @return mixed
     */
    public function json()
    {
        return json_decode($this->response->getBody()->getContents());
    }
}

class HandleRequestException extends \Exception { }

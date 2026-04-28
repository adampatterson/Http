# HTTP PHP

![PHP Composer](https://github.com/adampatterson/http/workflows/run-tests/badge.svg?branch=main)
[![Packagist Version](https://img.shields.io/packagist/v/adampatterson/http)](https://packagist.org/packages/adampatterson/http)
[![Packagist Downloads](https://img.shields.io/packagist/dt/adampatterson/http)](https://packagist.org/packages/adampatterson/http/stats)

> [!NOTE]
> This script is still under development.

## Install from [Packagist](https://packagist.org/packages/adampatterson/http)

```shell
composer require adampatterson/http
```

## Usage

### Basic Usage

```php
use Http\Http;
use GuzzleHttp\Cookie\CookieJar;

// GET request
$response = Http::get('https://example.com/api/users');

// POST request with JSON
$response = Http::asJson()->post('https://example.com/api/users', ['name' => 'John']);

// POST request with form parameters
$response = Http::asFormParams()->post('https://example.com/api/users', ['name' => 'John']);

// Request with custom headers
$response = Http::withHeaders(['X-Custom' => 'value'])->get('https://example.com/api/users');

// Request with Bearer token
$response = Http::withToken('your-token')->get('https://example.com/api/users');

// Request with simple cookie array (domain required for direct array conversion)
$response = Http::withCookies(['session_id' => 'abc123'], 'example.com')
    ->get('https://example.com/api/users');

// Request with a reusable Guzzle cookie jar
$jar = CookieJar::fromArray(['session_id' => 'abc123'], 'example.com');
$response = Http::withCookieJar($jar)->get('https://example.com/api/users');
```

### Response Helpers

The `HttpResponse` object provides several helpers to inspect the response:

```php
use Http\Http;

// GET request
$response = Http::get('https://example.com/api/users');

$response->status();  // Get status code (int)
$response->body();    // Get raw body (string)
$response->array();   // Get JSON decoded body (array)
$response->object();  // Get JSON decoded body (object)
$response->collect(); // Get JSON decoded body (Collection)
$response->header('Content-Type'); // Get specific header
$response->headers(); // Get all headers

$response->isSuccess();     // 200-299
$response->isOk();          // Alias for isSuccess
$response->isRedirect();    // 300-399
$response->isClientError(); // 400-499
$response->isServerError(); // 500+
```

### Method Proxying

If you need to access a method on the underlying Guzzle response that is not explicitly defined in `HttpResponse`, it will be automatically proxied:

```php
// getProtocolVersion() is not defined in HttpResponse, 
// so it is proxied to GuzzleHttp\Psr7\Response
$version = $response->getProtocolVersion(); 
```

### Request/Response Flow

At a high level, the package separates request building from response consumption:

1. The `Http` facade forwards static calls to `MakeHttpRequest`.
2. `MakeHttpRequest` collects fluent configuration (`asJson`, `withHeaders`, `withToken`, `withCookies`, etc.) and verb payload options.
3. `send()` merges options, parses query params from the URL, executes the Guzzle request, and captures transfer stats.
4. The raw PSR-7 response is wrapped in `HttpResponse`.
5. `HttpResponse` exposes helper methods (`body`, `json`, `status`, etc.) and proxies unknown methods to Guzzle.

This flow keeps transport concerns in `MakeHttpRequest` and read helpers in `HttpResponse`.

## Tests

```shell
composer install
composer test
```

### Code Coverage

To run tests with code coverage, ensure you have Xdebug installed and run:

```shell
composer test-coverage
```

## Local Dev

Without needing to modify the `composer.json` file. Run from the theme root, this will symlink the package into the theme's vendor directory.

```shell
ln -s ~/Sites/packages/http/ ./vendor/adampatterson/http
```

Otherwise, you can add the local package to your `composer.json` file.

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "/Sites/packages/http"
    }
  ]
}
```

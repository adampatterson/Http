<?php
/**
 * Requests for PHP, an HTTP library.
 *
 * @package   Requests\Examples
 * @copyright 2012-2023 Requests Contributors
 * @license   https://github.com/WordPress/Requests/blob/stable/LICENSE ISC
 * @link      https://github.com/WordPress/Requests
 */

// For composer dependencies
file_exists($composer = __DIR__.'/../vendor/autoload.php') or die("Run <code>composer install</code> from ".__DIR__);

require_once $composer;

// Now let's make a request via a proxy.
$options = [
	'proxy' => '127.0.0.1:8080', // Syntax: host:port, eg 12.13.14.14:8080 or someproxy.com:3128
	// If you need to authenticate, use the following syntax:
	// 'proxy' => array( '127.0.0.1:8080', 'username', 'password' ),
];
$request = Http\Http::get('http://httpbin.org/ip', [], $options);

// See result
var_dump($request->body);

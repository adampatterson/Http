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

// Set up our session
$session                    = new WpOrg\Requests\Session('http://httpbin.org/');
$session->headers['Accept'] = 'application/json';
$session->useragent         = 'Awesomesauce';

// Now let's make a request!
$request = $session->get('/get');

// Check what we received
dump($request);

// Let's check our user agent!
$request = $session->get('/user-agent');

// And check again
dump($request);

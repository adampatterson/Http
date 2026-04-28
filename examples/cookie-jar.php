<?php

use GuzzleHttp\Cookie\CookieJar;
use Http\Http;

include 'setup.php';

// Say you need to fake a login cookie
$cookieJar = CookieJar::fromArray([
    'session_id' => 'abc123',
], 'httpbin.org');

// Now let's make a request!
$request = Http::withCookieJar($cookieJar)
    ->get('http://httpbin.org/cookies');

// Check what we received
dump([
    'status'     => $request->status(),
    'collection' => $request->array(),
]);

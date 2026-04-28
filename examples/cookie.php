<?php

use Http\Http;

include 'setup.php';

// Say you need to fake a login cookie
$cookies = [
    'session_id' => 'abc123',
];

// Now let's make a request!
$request = Http::withCookies($cookies, 'httpbin.org')
    ->get('http://httpbin.org/cookies');

// Check what we received
dump([
    'status'     => $request->status(),
    'collection' => $request->array(),
]);

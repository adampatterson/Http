<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$token = 'my-secret-tokens';
$request = Http::withToken($token)->get(url: 'https://httpbin.org/bearer');

// Check what we received
dump([
    'status' => $request->status(),
    'body'   => $request->collect(),
]);


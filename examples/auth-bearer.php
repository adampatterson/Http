<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$tokepn = 'my-secret-tokens';
$request = Http::withToken($tokepn)->get(url: 'https://httpbin.org/bearer');

// Check what we received
dump([
    'status' => $request->status(),
    'body'   => $request->collect(),
]);


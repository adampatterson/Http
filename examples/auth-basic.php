<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$options = ['someuser', 'password'];

$request = Http::withBasicAuth('someuser', 'password')
    ->get(url: 'https://httpbin.org/basic-auth/someuser/password',
        query: $options);

// Check what we received
dump([
    'status' => $request->status(),
    // @todo handle the JSON validation exception
//    'body'   => $request->collect(),
]);

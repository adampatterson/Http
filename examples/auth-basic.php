<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::withBasicAuth('someuser', 'password')
    ->get(url: 'https://httpbin.org/basic-auth/someuser/password');

// Check what we received
dump([
    'status' => $request->status(),
    'body'   => $request->collect(),
]);

<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::withBasicAuth('someuser', 'password')
    ->get(url: 'https://httpbin.org/basic-auth/someuser/password');

// Check what we received
render_example('Auth Basic', $request);

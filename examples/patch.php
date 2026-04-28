<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::patch(
    url: 'https://httpbin.org/patch',
    params: ['mydata' => 'something']
);

render_example('Patch Request', $request);


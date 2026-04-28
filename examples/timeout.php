<?php

use Http\Http;
use Http\Exceptions\HandleRequestException;

include "setup.php";

$passOrFail = $argv[1] ?? 'pass';

// Now let's make a request to a page that will delay its response by 2 seconds
if ($passOrFail === 'pass') {
    // We set a timeout of 1 second, so this request will pass.
    $response = Http::timeout(2)->get('https://httpbin.org/delay/1');

    dump([
        'success' => $response->successful(),
    ]);
} else {
    // We set a timeout of 1 second, so this request will fail.
    try {
        $response = Http::timeout(1)->get('https://httpbin.org/delay/2');
        // This will never be reached!
    } catch (HandleRequestException $exception) {
        // An exception will be thrown, stating a timeout of the request!
        dump([
            'error'   => 'The request timed out.',
            'message' => $exception->getMessage(),
        ]);
    }
}


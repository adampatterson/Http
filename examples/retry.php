<?php
global $passOrFail;

use Http\Http;
use Http\Exceptions\HandleRequestException;

include "setup.php";

$retriesTriggered = 0;

// Configure retries with a short delay between attempts (in milliseconds).
if ($passOrFail) {
    // This request succeeds, even with retry behavior enabled.
    $response = Http::retry(3, 250)
        ->timeout(2)
        ->get('https://httpbin.org/delay/1');

    dump([
        'success'           => $response->successful(),
        'status'            => $response->status(),
        'retries_triggered' => $retriesTriggered,
        'attempts_total'    => $retriesTriggered + 1,
    ]);
} else {
    // This request times out on each attempt and eventually throws.
    try {
        $response = Http::retry(3, 250, function ($result) use (&$retriesTriggered) {
            $retriesTriggered++;

            dump([
                'retrying'        => true,
                'retry_number'    => $retriesTriggered,
                'failure_type'    => $result instanceof Throwable ? 'exception' : 'response',
                'failure_message' => $result instanceof Throwable ? $result->getMessage() : null,
            ]);

            return true;
        })->timeout(1)->get('https://httpbin.org/delay/2');
        // This will never be reached!
    } catch (HandleRequestException $exception) {
        dump([
            'error'             => 'The request failed after retrying.',
            'message'           => $exception->getMessage(),
            'retries_triggered' => $retriesTriggered,
            'attempts_total'    => $retriesTriggered + 1,
        ]);
    }
}

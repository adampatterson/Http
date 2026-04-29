<?php

declare(strict_types=1);

namespace Http\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversFunction('tap')]
final class HelpersTest extends TestCase
{
    #[Test]
    public function tap_executes_callback_and_returns_original_value(): void
    {
        $value = ['count' => 1];

        $result = tap($value, function (&$payload): void {
            $payload['count'] = 2;
        });

        $this->assertSame(['count' => 2], $result);
    }

    #[Test]
    public function tap_function_exists(): void
    {
        require __DIR__.'/../src/helpers.php';

        $this->assertTrue(function_exists('tap'));
    }
}

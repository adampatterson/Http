<?php

use Numeral\Http;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{

    public function testNumberFormatWhole()
    {
        $this->assertEquals(1234, 1234);
    }
}
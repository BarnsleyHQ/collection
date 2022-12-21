<?php

use BarnsleyHQ\Collection;
use PHPUnit\Framework\TestCase;

class helperTest extends TestCase
{
    public function testCollectMethod()
    {
        $this->assertTrue(function_exists('collect'));
        $this->assertEquals(['test1', 'test2'], collect(['test1', 'test2'])->toArray());
        $this->assertEquals(['test1'], collect('test1')->toArray());
    }
}

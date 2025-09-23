<?php

use UnityWebPortal\lib\exceptions\ArrayKeyException;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testArrayGetReturnsValueWhenKeyExists()
    {
        $array = [
            "a" => [
                "b" => [
                    "c" => 123
                ]
            ]
        ];
        $result = \arrayGet($array, "a", "b", "c");
        $this->assertSame(123, $result);
    }

    public function testArrayGetReturnsArrayWhenTraversingPartially()
    {
        $array = [
            "foo" => [
                "bar" => "baz"
            ]
        ];
        $result = \arrayGet($array, "foo");
        $this->assertSame(["bar" => "baz"], $result);
    }

    public function testArrayGetThrowsOnMissingKeyFirstLevel()
    {
        $array = ["x" => 1];
        $this->expectException(ArrayKeyException::class);
        $this->expectExceptionMessage('$array["y"]');
        \arrayGet($array, "y");
    }

    public function testArrayGetThrowsOnMissingKeyNested()
    {
        $array = ["a" => []];
        $this->expectException(ArrayKeyException::class);
        // Should include both levels
        $this->expectExceptionMessage('$array["a"]["b"]');
        \arrayGet($array, "a", "b");
    }

    public function testArrayGetThrowsWhenValueIsNullButKeyNotSet()
    {
        $array = ["a" => null];
        $this->expectException(ArrayKeyException::class);
        $this->expectExceptionMessage('$array["a"]');
        \arrayGet($array, "a");
    }

    public function testArrayGetReturnsValueWhenValueIsFalsyButSet()
    {
        $array = ["a" => 0];
        $result = \arrayGet($array, "a");
        $this->assertSame(0, $result);
    }
}

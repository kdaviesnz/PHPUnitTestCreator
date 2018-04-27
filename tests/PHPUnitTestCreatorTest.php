<?php

require_once("vendor/autoload.php");
require_once("src/PHPUnitTestCreator.php");

class PHPUnitTestCreatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSayHello()
    {
        $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
        $result = $PHPUnitTestCreator->sayHello();
        $this->assertTrue(
            $result=="hello",
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->sayHello() failed"
        );
    }
}
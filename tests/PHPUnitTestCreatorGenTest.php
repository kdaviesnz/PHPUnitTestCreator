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


    public function testSayGoodbye()
    {
        $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
        $result = $PHPUnitTestCreator->sayGoodbye();
        $this->assertTrue(
            $result=="Goodbye",
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->sayGoodbye() failed"
        );
    }


    public function testSayGreeting()
    {
        $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
        $result = $PHPUnitTestCreator->sayGreeting("Hi there");
        $this->assertTrue(
            $result=="Hi there",
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->sayGreeting() failed"
        );
    }


    public function testMultiply()
    {
        $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
        $result = $PHPUnitTestCreator->multiply(10,20);
        $this->assertTrue(
            $result==200,
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->multiply() failed"
        );
    }


}
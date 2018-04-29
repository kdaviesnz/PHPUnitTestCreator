<?php

require_once("vendor/autoload.php");
        require_once("src/PHPUnitTestCreator.php");

        class PHPUnitTestCreatorTest extends \PHPUnit_Framework_TestCase
        {
        
            public function testGetMethodParameterValue()
            {
            $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
            $result = $PHPUnitTestCreator->getMethodParameterValue(["value"=>10,"type"=>"int"]);
            $this->assertTrue(
            $result==10,
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->getMethodParameterValue() failed"
            );
            }

            
            public function testGetParametersAsString()
            {
            $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
            $result = $PHPUnitTestCreator->getParametersAsString("10,",["value"=>"20","type"=>"int"]);
            $this->assertTrue(
            $result=="10,20,",
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->getParametersAsString() failed"
            );
            }

            
            public function testWrapTestCode()
            {
            $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
            $result = $PHPUnitTestCreator->wrapTestCode("filename","PHPUnitTestCreator","This is the body for the test");
            $this->assertTrue(
            $result=="<?php

require_once(\"vendor/autoload.php\");
        require_once(\"filename\");

        class PHPUnitTestCreatorTest extends \PHPUnit_Framework_TestCase
        {
        This is the body for the test
}",
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->wrapTestCode() failed"
            );
            }

            
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

            
            public function testArrayIt()
            {
            $PHPUnitTestCreator = new kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();
            $result = $PHPUnitTestCreator->arrayIt(10,20);
            $this->assertTrue(
            $result==[10,20],
            "kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator->arrayIt() failed"
            );
            }

            
}
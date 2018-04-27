<?php

require ("vendor/autoload.php");
require("src/PHPUnitTestCreator.php");

$creator = new \kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();

$creator->createTestFiles("src", "tests");


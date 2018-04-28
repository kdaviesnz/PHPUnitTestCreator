<?php

require ("vendor/autoload.php");
require("src/PHPUnitTestCreator.php");

$creator = new \kdaviesnz\PHPUnitTestCreator\PHPUnitTestCreator();

$source_directory = "src";
$destination_directory = "tests";

$creator->createTestFiles($source_directory, $destination_directory);


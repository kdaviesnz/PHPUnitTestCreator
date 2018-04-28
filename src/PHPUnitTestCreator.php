<?php
declare(strict_types = 1);

namespace kdaviesnz\PHPUnitTestCreator;

use kdaviesnz\callbackfileiterator\CallbackFileIterator;
use kdaviesnz\functional\FunctionalModel;

// Checked PSR2 28.4.2018

/**
 * Class PHPUnitTestCreator
 * @package kdaviesnz\PHPUnitTestCreator
 */
class PHPUnitTestCreator
{
    /**
     * PHPUnitTestCreator constructor.
     */
    public function __construct()
    {
        // constructor body
    }

    /**
     * @param string $sourceDirectory
     * @param string $target_directory
     */
    public function createTestFiles(string $sourceDirectory, string $target_directory)
    {
        // Create callback to get and write the test code for each class file.
        $callback = function (string $filename) use ($target_directory) {
                $functional = new FunctionalModel();
                // This only gets public functions by default.
                $functions = $functional->getFunctions(file_get_contents($filename));
                if (!empty($functions)) {
                    $pure_functions = array_values(array_filter(
                        $functions,
                        function ($f) use ($functional, $filename) {
                            return $functional->checkForFunctionsThatArePure($f, $filename);
                        }
                    ));

                    $config_json = $this->getConfig($target_directory);

                    $code_for_test_file = $this->getCodeForTestFile(
                        $pure_functions[0]["className"],
                        $pure_functions,
                        $filename,
                        $config_json,
                        $target_directory
                    );
                    if (!empty($code_for_test_file)) {
                        $this->writeCodeForTestFile(
                            $pure_functions[0]["className"],
                            $code_for_test_file,
                            $target_directory
                        );
                    }
                }
            };

        $iterator = new CallbackFileIterator();
        $iterator->run($sourceDirectory, $callback, true, false);

    }

    /**
     * @param string $class_name
     * @param array $pureMethods
     * @param string $filename
     *
     * @return bool|string
     */
    public function getCodeForTestFile(string $class_name, array $pureMethods, string $filename, array $config_json, string $target_directory)
    {

        if (!class_exists($class_name)) {
            return false;
        }

        $pure_methods_validated = array_filter(
            $pureMethods,
            function ($pureMethod) use ($class_name) {
                if (method_exists($class_name, $pureMethod["name"])) {
                    // For now only methods that have no parameters
                   // $r = new \ReflectionMethod($class_name, $pureMethod["name"]);
                   // $params = $r->getParameters();
                   // return count($params)==0;
                    return true;
                } else {
                    return false;
                }
            }
        );

        $base_class_name =  basename(str_replace("\\", "/", $class_name));

        // Get the code for our test file.
        $body_code_for_test_file = array_reduce(
            $pure_methods_validated,
            function ($code, $pureMethod) use ($class_name, $base_class_name, $config_json, $target_directory) {

                $method = $pureMethod["name"];
                // For now only classes that can take no parameters

                $class = new $class_name();

                // Method parameters
                if (!empty($config_json[$class_name][$method]["parameters"])) {

                    $parameter_values = array_map(
                        function($parameter) {
                            return $parameter["value"];
                        },
                        $config_json[$class_name][$method]["parameters"]
                    );

                    $parameters_as_string = rtrim(array_reduce(
                        $config_json[$class_name][$method]["parameters"],
                        function($carry, $parameter) {
                            if ($parameter["type"]=="string") {
                                return $carry .  '"' . $parameter["value"] . '"' . ",";

                            } else {
                                return $carry . $parameter["value"] . ",";
                            }
                        },
                        ""
                    ), ",");
                }

                // Check config file for result
                if (isset($config_json[$class_name][$method]["result"])) {
                    $result = $config_json[$class_name][$method]["result"];
                } else {

                    // Call the method.
                    if (isset($parameter_values)) {
                        $result = call_user_func(array($class_name, $method), $parameter_values);
                    } else {
                        // For now only methods that have no parameters
                        $r = new \ReflectionMethod($class_name, $pureMethod["name"]);
                        $params = $r->getParameters();
                        if (count($params) > 0) {
                            $result = "";
                        } else {
                            $result = $class->$method();
                        }
                    }

                    // Save the result to the config file
                    if (!isset($config_json[$class_name])) {
                        $config_json = array(
                            $class_name => array(
                                $method => array(
                                    "result" => $result
                                )
                            )
                        );
                    } elseif (!isset($config_json[$class_name]["$method"])) {
                        $config_json[$class_name]["$method"] = array(
                            "result" => $result
                        );
                    } else {
                        $config_json[$class_name][$method]["result"] = $result;
                    }

                    $this->saveConfig($target_directory, $config_json);

                }

                ob_start();
                $result_maybe_wrapped = is_string($result)?'"' . $result . '"':$result;
                ?>

                public function test<?php echo ucfirst($method); ?>()
                {
                $<?php echo $base_class_name; ?> = new <?php echo $class_name; ?>();
                $result = $<?php echo $base_class_name; ?>-><?php echo $method; ?>(<?php echo isset($parameters_as_string)?$parameters_as_string:""; ?>);
                $this->assertTrue(
                $result==<?php echo $result_maybe_wrapped; ?>,
                "<?php echo $class_name; ?>-><?php echo $method; ?>() failed"
                );
                }

                <?php
                return ($code . ob_get_clean());
            },
            ""
        );

        // Wrap the test code up and send it back.
        if (!empty($body_code_for_test_file)) {
            ob_start();
            echo is_file("vendor/autoload.php")?"require_once(\"vendor/autoload.php\");\n":"";
            ?>
            require_once("<?php echo $filename; ?>");

            class <?php echo $base_class_name; ?>Test extends \PHPUnit_Framework_TestCase
            {
            <?php
            return "<?php\n\n" . ob_get_clean() . $body_code_for_test_file . "\n}";
        }

        return false;
    }

    /**
     * @param string $class_name
     * @param string $code_for_test_file
     * @param string $target_directory
     *
     * @return bool|int
     * @throws \Exception
     */
    public function writeCodeForTestFile(string $class_name, string $code_for_test_file, string $target_directory)
    {
        if (!is_dir($target_directory) && !mkdir($target_directory)) {
            throw new \Exception("Target directory could not be created.");
        }

        if (!is_writable($target_directory)) {
            throw new \Exception("Target directory is not writable.");
        }

        return file_put_contents(
            rtrim($target_directory, "/") . "/" .  basename(str_replace("\\", "/", $class_name)) . "GenTest.php",
            $code_for_test_file
        );

    }

    private function getConfig(string $target_directory)
    {
        $file = rtrim($target_directory, "/") . "/" . "PHPUnitTestCreator.json";
        return json_decode(file_exists($file)?
            trim(file_get_contents($file)):
            "[]",
            true
        );
    }

    private function saveConfig(string $target_directory, array $config_json)
    {
        if (!is_dir($target_directory) && !mkdir($target_directory)) {
            throw new \Exception("Target directory could not be created.");
        }

        if (!is_writable($target_directory)) {
            throw new \Exception("Target directory is not writable.");
        }

        $file = rtrim($target_directory, "/") . "/" . "PHPUnitTestCreator.json";
        file_put_contents($file, json_encode($config_json, JSON_PRETTY_PRINT));

    }

    /**
     * @return string
     */
    public function sayHello()
    {
        return "hello";
    }

    /**
     * @return string
     */
    public function sayGoodbye()
    {
        return "Goodbye";
    }

    /**
     * @return string
     */
    public function sayGreeting(string $greeting)
    {
        return $greeting;
    }

    public function multiply(int $x, int $y)
    {
        return $x * $y;
    }

}

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
                        return isset($f["className"]) && $functional->checkForFunctionsThatArePure($f, $filename);
                    }
                ));

                if (!empty($pure_functions)) {

                    $config_json = $this->getConfig( $target_directory );

                    $code_for_test_file = $this->getCodeForTestFile(
                        $pure_functions[0]["className"],
                        $pure_functions,
                        $filename,
                        $config_json,
                        $target_directory
                    );
                    if ( ! empty( $code_for_test_file ) ) {
                        $this->writeCodeForTestFile(
                            $pure_functions[0]["className"],
                            $code_for_test_file,
                            $target_directory
                        );
                    }
                }
            }
        };

        $iterator = new CallbackFileIterator();
        $iterator->run($sourceDirectory, $callback, true, false);

    }

    public function getMethodParameterValue(array $parameter)
    {
        return $parameter["value"];
    }

    public function getParametersAsString(string $carry, array $parameter):string
    {
        if ($parameter["type"]=="string") {
            return $carry .  '"' . $parameter["value"] . '"' . ",";
        }

        if (is_array($parameter["value"])) {
            // @todo refactor
            $value = $parameter["value"][$parameter["value"]["key"]];
            $value_maybe_wrapped = is_string($value)?'"' . str_replace('"', '\\"', $value) . '"':$value;
            $result = "[\"" .  $parameter["value"]["key"] . "\"=>" . $value_maybe_wrapped;
            if (isset($parameter["value"]["type"])) {
                $result_maybe_with_type = $result . "," . "\"type\"=>\"" . $parameter["value"]["type"] . "\"" . "]";
            } else{
                $result_maybe_with_type = $result . "]";
            }
        } else {
            $result_maybe_with_type = $parameter["value"];
        }

        return $carry . $result_maybe_with_type . ",";
    }

    /**
     * @param string $class_name
     * @param array $pureMethods
     * @param string $filename
     *
     * @return bool|string
     */
    private function getCodeForTestFile(string $class_name, array $pureMethods, string $filename, array $config_json, string $target_directory)
    {

        if (!class_exists($class_name)) {
            return false;
        }

        $pure_methods_validated = array_filter(
            $pureMethods,
            function ($pureMethod) use ($class_name) {
                if (method_exists($class_name, $pureMethod["name"])) {
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

                // Config parameters
                $config_parameter_values = isset($config_json[$class_name]["__construct"])?
                    array_map(
                        array($this, 'getMethodParameterValue'),
                        $config_json[$class_name]["__construct"]["parameters"]
                    ):
                    [];

                // Instantiate the class we're creating a test for.
                $class = !empty($config_parameter_values)?
                    new $class_name(...$config_parameter_values):
                    new $class_name;

                // Method parameters.
                $method_parameter_values = !empty($config_json[$class_name][$pureMethod["name"]]["parameters"])?
                    array_map(
                        array($this,'getMethodParameterValue'),
                        $config_json[$class_name][$pureMethod["name"]]["parameters"]
                    )
                    :[];

                // Get result.
                $result = $this->getResult($class_name, $pureMethod, $config_json, $class,$method_parameter_values, $target_directory);

                $method_parameters_as_string = !empty($config_json[$class_name][$pureMethod["name"]]["parameters"])?
                    rtrim(array_reduce(
                        $config_json[$class_name][$pureMethod["name"]]["parameters"],
                        array($this, 'getParametersAsString'),
                        ""
                    ), ",")
                    :"";

                return $this->getMethodTestCode(
                    $result, $pureMethod["name"], $base_class_name,
                    $class_name, $code, $method_parameters_as_string
                );
            },
            ""
        );

        // Wrap the test code up and send it back.
        return !empty($body_code_for_test_file)?
            $this->wrapTestCode($filename, $base_class_name, $body_code_for_test_file)
            :false;
    }

    private function getResult(string $class_name, array $pureMethod, array $config_json, $class ,array $method_parameter_values, string $target_directory)
    {
        // Check config file for result
        if (isset($config_json[$class_name][$pureMethod["name"]]["result"])) {
            $result = $config_json[$class_name][$pureMethod["name"]]["result"];
        } else {

            // Call the method.
            $result = $this->getTestResultByCallingMethod(
                $class, $pureMethod["name"], $method_parameter_values,
                $class_name,  $pureMethod
            );

            // Save the result to the config file
            $this->saveConfig($target_directory, $config_json,  $class_name,  $pureMethod["name"], $result);

        }
        return $result;
    }

    private function getTestResultByCallingMethod($class, string $method, array $method_parameter_values, string $class_name, array $pureMethod)
    {
        if (!empty($method_parameter_values)) {
            $result = $class->$method(...$method_parameter_values);
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
        return $result;
    }

    public function getMethodTestCode($result, string $method, string $base_class_name, string $class_name, string $code, string $method_parameters_as_string):string
    {
        ob_start();

        // @todo refactor
        if (is_string($result)) {
            $result_maybe_wrapped = '"' . str_replace('"', '\\"', $result) . '"';
        } elseif (is_bool($result)) {
            $result_maybe_wrapped = $result == false ? "false" : "true";
        } elseif (is_array($result)){
            $result_maybe_wrapped = rtrim(array_reduce(
                $result,
                function($carry, $item) {
                    return $carry . $item . ",";
                },
                "["
            ),',') . "]";
        } else {
            $result_maybe_wrapped = $result;
        }

        try {
            ?>

            public function test<?php echo ucfirst( $method ); ?>()
            {
            $<?php echo $base_class_name; ?> = new <?php echo $class_name; ?>();
            $result = $<?php echo $base_class_name; ?>-><?php echo $method; ?>(<?php echo ! empty( $method_parameters_as_string ) ? $method_parameters_as_string : ""; ?>);
            $this->assertTrue(
            $result==<?php echo $result_maybe_wrapped; ?>,
            "<?php echo $class_name; ?>-><?php echo $method; ?>() failed"
            );
            }

            <?php
        } catch(\Exception $e) {
            var_dump($e);
            die();
        }

        return $code . ob_get_clean();
    }

    public function wrapTestCode(string $filename, string $base_class_name, string $body_code_for_test_file):string
    {
        ob_start();
        echo is_file("vendor/autoload.php")?"require_once(\"vendor/autoload.php\");\n":"";
        ?>
        require_once("<?php echo $filename; ?>");

        class <?php echo $base_class_name; ?>Test extends \PHPUnit_Framework_TestCase
        {
        <?php
        return "<?php\n\n" . ob_get_clean() . $body_code_for_test_file . "\n}";
    }

    /**
     * @param string $class_name
     * @param string $code_for_test_file
     * @param string $target_directory
     *
     * @return bool|int
     * @throws \Exception
     */
    private function writeCodeForTestFile(string $class_name, string $code_for_test_file, string $target_directory)
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

    private function saveConfig(string $target_directory, array $config_json, string $class_name, string $method, $result)
    {
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

    public function arrayIt(int $x, int $y)
    {
        return [$x, $y];
    }

}

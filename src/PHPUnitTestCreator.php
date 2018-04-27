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
        $callback = function () use ($target_directory) {
            return function (string $filename) use ($target_directory) {
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
                    $code_for_test_file = $this->getCodeForTestFile(
                        $pure_functions[0]["className"],
                        $pure_functions,
                        $filename
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
        };

        new CallbackFileIterator($sourceDirectory, $callback(), true);

    }

    /**
     * @param string $class_name
     * @param array $pureMethods
     * @param string $filename
     *
     * @return bool|string
     */
    public function getCodeForTestFile(string $class_name, array $pureMethods, string $filename)
    {
        if (!class_exists($class_name)) {
            return false;
        }

        $pure_methods_validated = array_filter(
            $pureMethods,
            function ($pureMethod) use ($class_name) {
                if (method_exists($class_name, $pureMethod["name"])) {
                    // For now only methods that have no parameters
                    $r = new \ReflectionMethod($class_name, $pureMethod["name"]);
                    $params = $r->getParameters();
                    return count($params)==0;
                } else {
                    return false;
                }
            }
        );

        $base_class_name =  basename(str_replace("\\", "/", $class_name));

        // Get the code for our test file.
        $body_code_for_test_file = array_reduce(
            $pure_methods_validated,
            function ($code, $pureMethod) use ($class_name, $base_class_name) {
                $method = $pureMethod["name"];
                // For now only classes that can take no parameters
                $class = new $class_name();
                // Call the method.
                // @todo method parameters
                $result = $class->$method();
                ob_start();
                ?>
                public function test<?php echo ucfirst($method); ?>()
                {
                $<?php echo $base_class_name; ?> = new <?php echo $class_name; ?>();
                $result = $<?php echo $base_class_name; ?>-><?php echo $method; ?>();
                $this->assertTrue(
                $result=="<?php echo $result; ?>",
                "<?php echo $class_name; ?>-><?php echo $method; ?>() failed"
                );
                }
                <?php
                return trim($code . ob_get_clean());
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
            rtrim($target_directory, "/") . "/" .  basename(str_replace("\\", "/", $class_name)) . "Test.php",
            $code_for_test_file
        );

    }

    /**
     * @return string
     */
    public function sayHello()
    {
        return "hello";
    }
}

<?php
if (!defined('PHP_UNIT_INCLUDED')) 
{

define('PHP_UNIT_INCLUDED', true);

class Assert 
{
	static function assert_($bool, $message = '') 
	{
		if (!$bool) 
		{
			if ($message == '') 
			{
				$message = "Assertion failed.";
			}
			trigger_error($message, E_USER_ERROR);
		}
		else
		{
			TestRunnerFactory::get()->onTestSuccess();

		}
	}

	static function equals($value1, $value2, $message = '') 
	{
		if ($value1 != $value2) 
		{
			if ($message == '') 
			{
				$message = "Assertion failed: <b>'$value1' != '$value2'</b>";
			}
			trigger_error($message, E_USER_WARNING);
		}
		else
		{
			TestRunnerFactory::get()->onTestSuccess();

		}
	}
    
	static function equalsTrue($bool, $message = '') 
	{
		Assert::equals($bool, true, $message);
	}

	static function equalsFalse($bool, $message = '') 
	{
		Assert::equals($bool, false, $message);
	}
}

class Test 
{
}


function errorHandler($errno, $errstr, $errfile, $errline) 
{
	return TestRunnerFactory::get()->onTestError($errno, $errstr, $errfile, $errline);
}

class TestRunnerFactory
{
	static function &get($directory = null, $writer = 'TextualWriter')
	{
		static $instance;
		if(!isset($instance))
		{
			$instance = new TestRunner($directory);
			$instance->setWriter(new $writer);
		}
		return $instance;
	}
}

class TestRunner
{
	var $ERROR_FOUND;
	var $ERROR_CRITICAL;
	var $TESTS_COMPLETED;
	var $TESTS_ERROR;
	var $TESTS_FAILED;
	var $TESTS_SKIPPED;
	var $TESTS_TOTAL;

	var $directory;
	var $writer;
	var $abnormals;
	var $hereClass;
	var $hereMethod;

	function TestRunner($directory)
	{
		$this->directory = $directory;
	}

	function setWriter($writer)
	{
		$this->writer = $writer;
	}

	function testMain() {
		$this->onStartTestMain();
		$errorHandler = set_error_handler("errorHandler");
		error_reporting (E_ALL);
		$this->TESTS_COMPLETED = 0;
		$this->TESTS_ERROR = 0;
		$this->TESTS_FAILED = 0;
        	$this->TESTS_SKIPPED = 0;
		$this->TESTS_TOTAL = 0;
		$handle = opendir($this->directory);
		while (($file = readdir($handle)) !== false) 
		{
	        if (strlen($file) > 5 && substr($file, -3) == 'php' && $file != 'index.php') 
		{
	        	include_once($this->directory . '/' . $file);
	        	$class = substr($file, 0, strlen($file) - 4);
			if (class_exists($class)) 
			{
				if (is_subclass_of($class, 'Test')) 
				{
					$this->runTest($class);
				}
	        	}
		}
		}
		closedir($handle);
		set_error_handler("errorHandler");
		$this->onEndTestMain();
	}
	
	/***
	 * Call all methods starting with 'test'. $class is the name of the class.
	 * Although not strictly necessary, it makes the output look better 
	 * (properly capitalized).
	 ***/
	function runTest($class) 
	{
		$this->onStartRunTest($class);
		$this->ERROR_FOUND = false;
		$this->ERROR_CRITICAL = false;
		$methods        = $this->testMethods($class);
		$left = count($methods);
		foreach ($methods as $method) {
		// Don't continue running tests if something really bad happened.
		// That is, if Assert::assert_ evaluated to true.
			if ($this->ERROR_CRITICAL) {
				$this->TESTS_SKIPPED += $left;
				$this->TESTS_ERROR++;
				break;
			}
			$this->TESTS_TOTAL++;
			$left--;
			$this->ERROR_FOUND = false;
			$inst = new $class;
			$this->onStartTestMethod($class, $method);
			$inst->$method();
			if (!$this->ERROR_FOUND) {
				$this->TESTS_COMPLETED++;
			} else {
				$this->TESTS_FAILED++;
			}
			$this->onEndTestMethod($class, $method);
		}
		$this->onEndRunTest($class);
	}

	function testMethods ($class)
	{
		$methods = get_class_methods($class);
		$methods = array_filter($methods,
			create_function('$each',
				'return strlen($each) > 4 && substr($each, 0, 4) == "test";'
			));
		return $methods;
	}

	function onStartTestMain()
	{
		$this->writer->onStartTestMain($this);
		$this->abnormals = array();
	}

	function onEndTestMain()
	{
		$this->writer->onEndTestMain($this);
		$this->abnormals = null;
	}

	function onStartRunTest($class)
	{
		$this->writer->onStartRunTest($class);
		$this->abnormals[$class] = array();
		$this->hereClass = $class;
	}

	function onEndRunTest($class)
	{
		$this->writer->onEndRunTest($class);
		$this->hereClass = null;
	}

	function onStartTestMethod($class, $method)
	{
		$this->writer->onStartTestMethod($class, $method);
		$this->abnormals[$class][$method] = array();
		$this->hereMethod = $method;
	}

	function onEndTestMethod($class, $method)
	{
		$this->writer->onEndTestMethod($class, $method);
		$this->hereMethod = null;
	}

	function onTestSuccess()
	{
		$this->writer->onTestSuccess($this->hereClass, $this->hereMethod);
	}

	function onTestError($errno, $errstr, $errfile, $errline) 
	{
		$args = func_get_args();
		switch($errno) 
		{
	    	case E_USER_ERROR:
			$this->writer->onTestError($errno, $errstr, $errfile, $errline);
			$args[] = "Fatal Error";
			$this->ERROR_FOUND    = true;
			$this->ERROR_CRITICAL = true;
			break;
		case E_USER_WARNING:
			$this->writer->onTestError($errno, $errstr, $errfile, $errline);
			$args[] = "Warning";
			$this->ERROR_FOUND = true;
			break;
		case E_WARNING:
			$this->writer->onTestWarning($errno, $errstr, $errfile, $errline);
			$args[] = "PHP Warning";
			break;
		case E_NOTICE:
			$this->writer->onTestNotice($errno, $errstr, $errfile, $errline);
			$args[] = "PHP Notice";
			break;
		case E_ERROR:
			$this->writer->onTestError($errno, $errstr, $errfile, $errline);
			$args[] = "PHP Error";
			exit -1;
			break;
		default:
			$this->writer->onTestUnknwonError($errno, $errstr, $errfile, $errline);
			$args[] = "Unknown Error";
			break;
		}
		$this->abnormals[$this->hereClass][$this->hereMethod][] = $args;
	}
	
}

class TextualWriter
{
	function onStartTestMain ($runner)
	{
	}

	function onEndTestMain ($runner)
	{
		echo "\n";
		$sep = "---------------------------------------------------\n";
		foreach ($runner->abnormals as $class => $methods) {
			foreach ($methods as $method => $errors) {
				foreach ($errors as $error) {
					echo $sep;
					echo chunk_split($error[4] . ': ' . $class . '->' . $method, strlen($sep) - 1, "\n");
					echo $sep;
					echo $error[1]. "\n";
					echo 'In ' . $error[2] . ':' . $error[3] . "\n\n";
				}
			}
		}
		echo "Total:   $runner->TESTS_TOTAL\n";
		echo "Ok:      $runner->TESTS_COMPLETED\n";
		echo "Skipped: $runner->TESTS_SKIPPED\n";
		echo "Error:   $runner->TESTS_ERROR\n";
		echo "Fail:    $runner->TESTS_FAILED\n";
		echo "\n";
	}

	function onStartRunTest ($class)
	{
        	if (strlen($class) > 4 && strtolower(substr($class, -4)) == 'test')
		{
			$class = substr($class, 0, strlen($class) - 4);
		}
		echo "$class\n";
	}

	function onEndRunTest ($class)
	{
	}

	function onStartTestMethod ($class, $method)
	{
		echo '->' . substr($method, 4), ': ';
	}

	function onEndTestMethod ($class, $method)
	{
		echo "\n";
	}

	function onTestSuccess ($class, $method)
	{
		echo ".";
	}

	function onTestUserError($errno, $errstr, $errfile, $errline)
	{
                echo "E";
	}

	function onTestUserWarning($errno, $errstr, $errfile, $errline)
	{
		echo "F";
	}

	function onTestError($errno, $errstr, $errfile, $errline)
	{
		echo "E";
	}

	function onTestWarning($errno, $errstr, $errfile, $errline)
	{
		echo "W";
	}

	function onTestNotice($errno, $errstr, $errfile, $errline)
	{
		echo "N";
	}

	function onTestUnknownError($errno, $errstr, $errfile, $errline)
	{
		echo "E";
	}
}

if(basename($argv[0]) == basename(__FILE__))
{
	$directory = '.';
	if(isset($argv[1]))
	{
		$directory = $argv[1];
	}
	TestRunnerFactory::get($directory)->testMain();
}


} // !defined('PHP_UNIT_INCLUDED')
?>

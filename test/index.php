<?php
require_once( 'phpunit.php' );

class HtmlWriter
{
	function onStartTestMain ($runner)
	{
		echo "    <table align=center border=0 cellpadding=0 cellspacing=0 ";
		echo "width=100%>\n";
	}

	function onEndTestMain ($runner)
	{
		echo "    </table>\n";
		//echo "    <p>Successfully executed <b>$this->TESTS_COMPLETED</b> of ";
		echo "    <p>Successfully executed <b>$runner->TESTS_COMPLETED</b> of ";
//		echo "<b>$this->TESTS_TOTAL</b> tests</p>\n";
		echo "<b>$runner->TESTS_TOTAL</b> tests</p>\n";
	}

	function onStartRunTest ($class)
	{
        	if (strlen($class) > 4 && strtolower(substr($class, -4)) == 'test')
		{
			$class = substr($class, 0, strlen($class) - 4);
		}
		echo "      <tr>\n";
		echo "        <th colspan=2><b>$class</b></th>\n";
		echo "      </tr>\n";
	}

	function onEndRunTest ($class)
	{
	}

	function onStartTestMethod ($class, $method)
	{
//		$css         = $this->TESTS_TOTAL % 2 == 0 ? 'dark' : 'light';
		$css = 'light';
		echo "      <tr>\n";
		echo "        <td class=$css width=50 nowrap valign=top>";
		echo substr($method, 4), "</td>\n";
		echo "        <td class=$css valign=top>";
	}

	function onEndTestMethod ($class, $method)
	{
		echo "</td>\n";
		echo "      </tr>\n";
	}

	function onTestSuccess ($class, $method)
	{
		echo "<b>OK</b>";
	}

	function onTestUserError($errno, $errstr, $errfile, $errline)
	{
		echo "Fatal error: $errstr<br>";
		echo "Skipping remaining tests for this unit...";
	}

	function onTestUserWarning($errno, $errstr, $errfile, $errline)
	{
		echo $errstr . '<br>';
	}

	function onTestError($errno, $errstr, $errfile, $errline)
	{
		echo "PHP ERROR on line <b>$errline</b> of file <b>$errfile</b>";
		echo "<br><b>Aborting...</b>";
	}

	function onTestWarning($errno, $errstr, $errfile, $errline)
	{
		echo "PHP WARNING on line <b>$errline</b> of file <b>$errfile</b>:";
		echo "$errstr<br>";
	}

	function onTestNotice($errno, $errstr, $errfile, $errline)
	{
		echo "PHP NOTICE on line <b>$errline</b> of file <b>$errfile</b>:";
		echo "$errstr<br>";
	}

	function onTestUnknownError($errno, $errstr, $errfile, $errline)
	{
		echo "PHP Unkown error $errno: $errstr<br>\n";
	}
}


if (isset($GET['directory'])) {
	$directory = dirname($GET['directory']);
} elseif (isset($_SERVER['PATH_TRANSLATED'])) {
	$directory = dirname($_SERVER['PATH_TRANSLATED']);
} elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
	$directory = dirname($_SERVER['SCRIPT_FILENAME']);
}

$dirValid   = ($directory != '' && is_dir($directory));
$title = $dirValid ? ': Results' : ': Configuration'; 

?>
<html>
  <head>
    <title>PHP Unit Tester<?php echo $title ?></title>
    <style>
    body {
    	background-color: rgb(95%, 95%, 100%);
    	font-family: Verdana, Arial, Helvetica, sans-serif;
    	margin-left: 10%;
        margin-right: 10%;
    }
    
    h1, h2 {
    	text-align: center;
    }
    
    th {
    	background-color: rgb(0%, 0%, 0%);
        color: white;
        font-size: 15px;
        font-weight: bold;
    }
    
    td {
    	padding: 2px 10px 2px 10px;
        font-size: 13px;
    }

    table {
    	border: 1px solid black;
    }
    
    td.dark {
    	background-color: rgb(80%, 80%, 90%);
    }
    
    td.light {
    	background-color: rgb(90%, 90%, 100%);
    }
    
    a:link {
    	text-decoration: none;
        color: rgb(0%, 0%, 60%);
        font-weight: bold;
    }
    
    a:hover {
    	text-decoration: underline;
        color: rgb(0%, 0%, 70%);
    }
    
    a:visited {
    	color: rgb(0%, 0%, 80%);
    }
    </style>
  </head>
  
  <body>
    <h1>PHP Unit Tester<?php echo $title ?></h1>
<?php
if ($dirValid) 
{
	TestRunnerFactory::get($directory, HtmlWriter)->testMain();
}
else 
{
?>
  <h2>Set the Test Directory</h2>
  <form>
    <b>Directory</b>: <input type=text name="directory" value="<?php echo $directory ?>" 
    size=30> <input type=submit value="Run Tests">
  </form>
  <p>
    <b>Tip</b>: After setting the correct directory and clicking <b>Run Tests</b>, save 
    a bookmark to the resulting page. Opening that bookmark will immediately re-run all
	tests.
  </p>

<?php
} // !$dirValid
/* ?>
  <h2>About the PHP Unit Tester</h2>
  <p>
    This small application can be used to automatically test PHP units. Although
	(very!) distantly related to <a href="http://www.junit.org">jUnit</a>, it is by no
	means as advanced as that particular package, nor is it intended to be. By 
    the way, if you're not into Object-Oriented Programming, haven't heard of
    <i>Extreme Programming</i>, or the term <i>Refactoring</i> means nothing to you, 
    this application is probably not for you.
  </p>
  <p>
    How does it work? Well, first put this file somewhere on your development 
	server. Then open it in your favorite browser (through the server), type in the 
	local path to the test units (e.g. <b>/data/www/tests</b>), and press 
    <b>Run Tests</b>. That's all!
  </p>
  <p>
    Once a valid directory has been set, this application reads all PHP-files
	(<b>*.php</b>) in that directory, and runs the tests in them. Thus, to add a
	test to your suite, simply put it in mentioned directory.
  </p>
  <p>
    Every test must be implemented in a class with the same name as the file it
	is in. Thus, class <b>FileTest</b> should be in <b>FileTest.php</b>. Also,
	the class must be a subclass of class <b>Test</b>. There's no need to include
	this class, as this application takes care of that.
  </p>
  <p>
    A basic test-class looks as follows (note, class <b>File</b> doesn't actually exist):
  </p>
  <pre><code>    require_once('File.php'); // This is the class we're testing
  
    class FileTest extends Test 
    {
        var $file;

        function FileTest() 
        {
            unset($this->file);
            $this->file = new File();
        }
		
        function testCreate() 
        {
            Assert::assert_(isset($this->file), "File object could not be instantiated");
        }

        function testOpen() 
        {
            $this->file->open('test.dat');
            Assert::equalsTrue($this->file->isOpened(), "File couldn't be opened");
        }
        
        function testReadChar() 
        {
            $char = '0';
            $char = $this->file->readChar();
            Assert::equals('a', $char);
        }
		
        function testClose() 
        {
            $this->file->close();
            Assert::equalsFalse($this->file->isOpened(), "File couldn't be closed");
        }
    }</code></pre>
  <p>
    In short, the rules are as follows:
  </p>
  <ul>
    <li>There's no need to call the parent constructor; it doesn't even exist.</li>
    <li>A method that executes a test should have a name starting with <b>test</b>.</li>
    <li>There should be no method with the name <b>run</b>. This is the <i>only</i>
        method defined in class <b>Test</b>, and it isn't meant to be overridden.</li>
    <li>Test methods are called in the order they are defined in the class. This allows
        you - for example - to set up some variable in one test-method that can be 
        reused in a second.</li>
    <li><b>Assert:assert_</b> expects a boolean and an optional message string. If the 
        boolean evaluates to <b>false</b>, the test for that particular unit is 
        stopped, and the application continues with the next unit (if it exists).</li>
    <li><b>Assert::equals</b> expects two values and an optional message. If the two
        values aren't equal an error is printed, but all other tests for the unit will
        still be executed.</li>
    <li><b>Assert::equalsTrue</b> expects a value and an optional message. Its behavior 
        is like <b>Assert::equals</b>, with one of the values set to <b>true</b>.</li>
    <li><b>Assert::equalsFalse</b> is like <b>Assert::equalsTrue</b>, with <b>false</b>
        instead of <b>true</b>. (What a surprise...)
  </ul>
  <p>
    As mentioned earlier, it's all very simple. At the same time I find it very useful. 
    If you want the application to do more, you are free to modify this code for your
	own personal needs. 
  </p>
  <p>
    Final note: the code for this application is by no means representative for the
    code I normally write! I hacked this thing together in about half an hour, so there
    there you go. (Normally, I would <i>never</i> use global variables! I swear!)
  </p>
  <p>
     Vincent Oostindi&euml;, 
     <a href="mailto:vincent@sunlight.tmfweb.nl">vincent@sunlight.tmfweb.nl</a>,
     March 2002.
  </p>
  </body>
</html>
*/
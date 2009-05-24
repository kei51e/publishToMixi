<?php
require_once( '../publishToMixi.php' );

class TestHandlerHelpers extends Test {
	function doTest ( $testfunc, $input, $expected, $arg = NULL, $message = '' ) {
		$actual = $testfunc( $input, $arg );
		Assert::equals( $actual, $expected, $message );
	}

	function testReplaceHyperlinksExtractsUrls () {
		$in = '<br/><a href="http://www.example.com" class="external">www.example.com</a>\n<hr/><a href="http://www.google.com/q?search=publishtomixi"/>search me</a>';
		$out = '<br/>www.example.com(http://www.example.com)\n<hr/>search me(http://www.google.com/q?search=publishtomixi)';
		$this->doTest( 'replace_hyperlinks', $in, $out );
	}

	function testReplaceHyperlinksAcceptsEsotericUrls () {
		$url = 'http://www.flickr.com/people/id@trail?key=value&sess=ABC_123';
		$in = '<br/><a href="' . $url . '" class="external">should appear</a>';
		$out = '<br/>should appear(' . $url . ')';
		$this->doTest( 'replace_hyperlinks', $in, $out );
	}

	function testReplaceHyperlinksAcceptsSingleQuotedAttributes () {
		$url = 'http://www.flickr.com/people/id@trail';
		$src = 'http://farm1.static.flickr.com/upper/id.jpg';
		$in = '<br/><a href="' . $url . '" ><img src=\'' . $src . '\'></a>';
		$out = '<br/>' . $src . '(' . $url . ')';
		$this->doTest( 'replace_hyperlinks', $in, $out );

	}

	function testReplaceHyperlinksShouldIgnoreExcludes () {
		$ignore = 'http://www.example.com';
		$in = '<br/><a href="http://www.example.com" class="external">www.example.com</a>\n<hr/><img src="http://www.example.com"/>foo';
		$out = '<br/>www.example.com\n<hr/>foo';
		$this->doTest( 'replace_hyperlinks', $in, $out, array( $ignore ) );
	}
}
?>

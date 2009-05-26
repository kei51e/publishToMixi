<?php
require_once( '../publishToMixi.php' );

class TestJpegExtraction extends Test {
	function doTest ( $testfunc, $input, $expected, $arg = NULL, $message = '' ) {
		$actual = $testfunc( $input, $arg );
		Assert::equals( $actual, $expected, $message );
	}

	function testExtractJpegImages () {
		$html = '<html><body><img src="http://farm4.static.flickr.com/3409/3564522132_9f3f01c046.jpg?v=0"/></body></html>';
		$images = p2mixi_extract_jpeg_images ( $html, 1 );
		Assert::equals( count ($images), 1, '');
		Assert::equals( strlen ($images[0]), 39075, '');
	}
}
?>


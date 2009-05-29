<?php
require_once( '../publishToMixi.php' );

class TestJpegExtraction extends Test {
	function doTest ( $testfunc, $input, $expected, $arg = NULL, $message = '' ) {
		$actual = $testfunc( $input, $arg );
		Assert::equals( $actual, $expected, $message );
	}

	function testExtractJpegImages () {
		$html = '<html><body><img src="http://farm4.static.flickr.com/3408/3575435148_80e4a00b19.jpg?v=0"/></body></html>';
		$images = p2mixi_extract_jpeg_images ( $html, 1 );
//		Assert::equals( count ($images['images']), 1, '');
		Assert::equals( strlen ($images['images'][0]), 5221, '');
	}
	function testExtractJpegImagesWithRedirection () {
		$html = '<html><body><img src="http://ocafe.appspot.com/?redirect"/></body></html>';
		$images = p2mixi_extract_jpeg_images ( $html, 1 );
//		Assert::equals( count ($images['images']), 1, '');
		Assert::equals( strlen ($images['images'][0]), 5221, '');
	}
}
?>


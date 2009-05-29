<?php
require_once( '../publishToMixi.php' );

class TestHttp extends Test {
	
	
	function testGet () {
		$url = 'http://farm4.static.flickr.com/3408/3575435148_80e4a00b19.jpg?v=0';
		$response_headers = array();
		$response_body = '';
		p2mixi_http_get( $url, array(), $response_headers, $response_body );
		Assert::equals( strlen ($response_body), 5221, '');
	}
	function testGetRedirection () {
		// This URL redirects to 'http://farm4.static.flickr.com/3408/3575435148_80e4a00b19.jpg?v=0'
		$url = 'http://ocafe.appspot.com/?redirect';  
		$response_headers = array();
		$response_body = '';
		p2mixi_http_get( $url, array(), $response_headers, $response_body );
		Assert::equals( strlen ($response_body), 5221, '');
	}
	function testPost () {
		// This URL just returns the post request body as is.
		$url = 'http://ocafe.appspot.com/';
		$response_headers = array();
		$response_body = '';
		$request_body = 'myparam=myvalue';
		p2mixi_http_post( $url, array(), $request_body, $response_headers, $response_body );
		Assert::equals( $response_body, $request_body, $request_body );
	}
}
?>


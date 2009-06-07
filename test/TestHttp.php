<?php
require_once( '../publishToMixi.php' );

class TestHttp extends Test {
	
	
	function testGet () {
		$url = $this->getTestImageURL();
		$response_headers = array();
		$response_body = '';
		p2mixi_http_get( $url, array(), $response_headers, $response_body );
		Assert::equals( strlen ( $response_body ), 5221, '');
	}
	function testGetRedirection () {
		$url = $this->getTestServerURL() . '?redirect=true';  
		$response_headers = array();
		$response_body = '';
		p2mixi_http_get( $url, array(), $response_headers, $response_body );
		Assert::equals( strlen ( $response_body ), 5221, '');
	}
	function testPost () {
		$url = $this->getTestServerURL();  
		$request_headers = array( 'Content-type'=>'application/x-www-form-urlencoded');
		$response_headers = array();
		$response_body = '';
		$request_body = 'name=value123';
		p2mixi_http_post( $url, $request_headers, $request_body, $response_headers, $response_body );
		Assert::equals( $response_body, $request_body, $request_body );
	}
	
	function getTestServerPath()
	{
		$url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		$url = substr( $url, 0, strrpos( $url, '/') );
		$url = substr( $url, 0, strrpos( $url, '/') );
		$url = $url . '/test-server/';
		return $url;
	}
	function getTestServerURL()
	{
		return $this->getTestServerPath() . 'TestServer.php';
	}
	function getTestImageURL()
	{
		return $this->getTestServerPath() . 'test.jpg';
	}
}
?>


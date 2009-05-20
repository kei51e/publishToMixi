<?php
require_once( '../publishToMixi.php' );

class TestTinyHttpSocket extends Test {
	function testReturnsFalseIfConnectingToWrongHostOrPort () {
		$sock = new P2Mixi_TinyHttpSocket( "t.a.b.c.beq", 80 );
		Assert::equalsFalse( $sock->connect() );

		$sock = new P2Mixi_TinyHttpSocket( "www.example.com", 1 );
		Assert::equalsFalse( $sock->connect() );
	}
}
?>

<?php

	function getImageURL()
	{
		//$url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		$url = $_SERVER['REQUEST_URI'];
		$url = substr( $url, 0, strrpos( $url, '/') );
		$url .= '/test.jpg';
		return $url;
		
	}
	if ( $_GET['redirect'] )
	{
		header( 'Location:' . getImageURL() );
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		echo 'name='.$_POST['name'];
	}
	else
	{  
?>
<html><body>
  Post test : <form method="POST" action="TestServer.php"><input type="text" name="name"/></form>
</body></html>
<?php 
	}

?>
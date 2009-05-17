<?php
/*
 Plugin Name: publishToMixi
 Plugin URI: http://ksnn.com/diary/?page_id=2437
 Description: Publish the post to Mixi using AtomPub
 Author: Kei Saito
 Version: 2.1
 Author URI: http://ksnn.com/
 */

/*
 * A wordpress plugin to publish the post to Mixi
 * Copyright (C) 2008,2009 Kei Saito (http://ksnn.com/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Debug mode - for debug purpose only
$P2Mixi_debug = false;

// ----------------------------------------------------------------------------
/**
 * Register settings (2.7 compatibility)
 */
function P2Mixi_adminInit () {
	if ( function_exists( 'add_option_update_handler' ) ) {
		add_option_update_handler( 'P2Mixi', 'P2Mixi_username' );
		add_option_update_handler( 'P2Mixi', 'P2Mixi_password' );
		add_option_update_handler( 'P2Mixi', 'P2Mixi_id' );
		add_option_update_handler( 'P2Mixi', 'P2Mixi_headerDefault' );
		add_option_update_handler( 'P2Mixi', 'P2Mixi_footerDefault' );
		add_option_update_handler( 'P2Mixi', 'P2Mixi_default' );
	}
}

/**
 * Register settings (the old way)
 */
function P2Mixi_activate () {
	if ( function_exists( 'add_option' ) ) {
		add_option( 'P2Mixi_username' );
		add_option( 'P2Mixi_password' );
		add_option( 'P2Mixi_id' );
		add_option( 'P2Mixi_headerDefault' );
		add_option( 'P2Mixi_footerDefault' );
		add_option( 'P2Mixi_default', true );
	}
}

// ----------------------------------------------------------------------------
/**
 * Renders the option box in the "Write Post" page in the wordpress admin.
 */
function P2Mixi_renderOption () {
	// expects 'add_meta_box' function... wordpress 2.5 and and above.
	add_meta_box( 'myplugin_sectionid', __( 'Publish To Mixi', 'P2Mixi_textdomain' ), 
		'P2Mixi_renderOptionContent', 'post', 'advanced' );
	add_options_page( __( 'mixi autopost settings', 'P2Mixi_textdomain'), __( 'Mixi Autoposting', 'P2Mixi_textdomain' ), 8, __FILE__, 'P2Mixi_renderAdminOptionContent');
}

function P2Mixi_renderAdminOptionContent () {
	?>
	<div class="wrap">
	<h2><?php echo __('mixi autopost settings', 'P2Mixi_textdomain') ?></h2>
	<form method="post" action="options.php">
	<?php settings_fields('P2Mixi'); ?>
        <h3><?php echo __('Login info', 'P2Mixi_textdomain') ?></h3>
	<p></p>
	<table class="form-table">
	<tr valign="top">
	   <th scope="row"><?php echo __('Username:', 'P2Mixi_textdomain') ?></th>
		<td><input type="text" name="P2Mixi_username" value="<?php echo get_option('P2Mixi_username'); ?>" /><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('Password:', 'P2Mixi_textdomain') ?></th>
		<td><input type="password" name="P2Mixi_password" value="<?php echo get_option('P2Mixi_password'); ?>" /><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('ID:', 'P2Mixi_textdomain') ?></th>
		<td><input type="text" name="P2Mixi_id" value="<?php echo get_option('P2Mixi_id'); ?>" /><td>
        </tr>
	</table>
        <h3><?php echo __('Posting defaults', 'P2Mixi_textdomain') ?></h3>
	<p></p>
	<table class="form-table">
	<tr valign="top">
	   <th scope="row"><?php echo __('Header:', 'P2Mixi_textdomain') ?></th>
		<td><textarea name="P2Mixi_headerDefault" cols="60" rows="4"><?php echo get_option('P2Mixi_headerDefault'); ?></textarea><br/>
		<?php echo __('%%URL%% will be replaced with the post permalink.', '') ?><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('Footer:', 'P2Mixi_textdomain') ?></th>
		<td><textarea name="P2Mixi_footerDefault" cols="60" rows="4"><?php echo get_option('P2Mixi_footerDefault'); ?></textarea><br/>
		<?php echo __('%%URL%% will be replaced with the post permalink.', '') ?><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('Publish to mixi:', 'P2Mixi_textdomain') ?></th>
		<td><label for="P2Mixi_default">
		<input type="checkbox" name="P2Mixi_default" id="P2Mixi_default" <?php if ( get_option('P2Mixi_default') == true ) { echo 'checked="checked"'; } ?> />
		<?php echo __( 'Publish to mixi by default', 'P2Mixi_textdomain' ) ?>
		<td>
        </tr>
	</table>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	</form>
	</div>
	<?php
}

/**
 * Renders the option box content. This will be called by P2Mixi_renderOption().
 */
function P2Mixi_renderOptionContent () {
	$P2Mixi_default = get_option( 'P2Mixi_default' );
	$P2Mixi_headerDefault = get_option( 'P2Mixi_headerDefault' );
	$P2Mixi_footerDefault = get_option( 'P2Mixi_footerDefault' );

	?>
	
	<input type="hidden" name="P2Mixi_noncename" id="P2Mixi_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ) ?>" />
	<div>
		<input type="checkbox" name="P2Mixi_publishcheckbox" id="P2Mixi_publishcheckbox" <?php if ( $P2Mixi_default == true ) { echo 'checked="checked"'; } ?> />
		<label for="P2Mixi_publishbox"> <?php echo __("Publish To Mixi", 'P2Mixi_textdomain' ) ?> </label>
	</div>

	<div style="margin-top:6px">
		<label for="P2Mixi_headertext"> <?php echo __("Header Text", 'P2Mixi_textdomain' ) ?> </label>
	</div>
	<div>
		<textarea style="width:98%" name="P2Mixi_headertext" id="P2Mixi_headertext"><?php  echo $P2Mixi_headerDefault; ?></textarea>
	</div>
	<div style="margin-top:6px">
		<label for="P2Mixi_footertext"> <?php echo __("Footer Text", 'P2Mixi_textdomain' ) ?> </label>
	</div>
	<div>
		<textarea style="width:98%" name="P2Mixi_footertext" id="P2Mixi_footertext"><?php  echo $P2Mixi_footerDefault; ?></textarea>
	</div>
	
	<?php 
}

/**
 * Publishes the wordpress entry to mixi.
 *
 * @param number $postId
 * @return postId
 */
function P2Mixi_publishHandler ( $postId ) {
	global $P2Mixi_debug;
	$P2Mixi_username = get_option( 'P2Mixi_username' );
	$P2Mixi_password = get_option( 'P2Mixi_password' );
	$P2Mixi_id = get_option( 'P2Mixi_id' );
	$P2Mixi_default = get_option( 'P2Mixi_default' );
	$P2Mixi_headerDefault = get_option( 'P2Mixi_headerDefault' );
	$P2Mixi_footerDefault = get_option( 'P2Mixi_footerDefault' );
		
	$header = "";
	$footer = "";	
		
	// In case the entry was posted from the tool, not from the wp-admin page
	// by checking any of text input field was there or not.
	if ( $_POST['P2Mixi_footertext'] == null ) {
		if ( $P2Mixi_default == false ) {
			return $postId;
		}
		
		$header = $P2Mixi_headerDefault;
		$footer = $P2Mixi_footerDefault;
				
	} else {

		// verify this came from the our screen and with proper authorization,
		// because publish_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['P2Mixi_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		
		// In case the entry was posted from the wp-admin page.
		if ( $_POST['P2Mixi_publishcheckbox'] == null || $_POST['P2Mixi_publishcheckbox'] == 'false'  ) {
			return $postId;
		}
		$header = trim( $_POST['P2Mixi_headertext'] );
		$footer = trim( $_POST['P2Mixi_footertext'] );
	}
	
	// Get the post detail from wordpress.
	$post = get_post( $postId );
	if ( $post->post_status != 'publish' ) {
		return $postId;
	}
	// Extracting images from the post content.
	$extractor = new P2Mixi_JpegExtractor();
	$extractor->setDebugMode( $P2Mixi_debug );
	$images = $extractor->extract( $post->post_content );

	// Header text
	if ( $header != '' ) {
		$header = str_replace( '%%URL%%', $post->guid, $header );
		$header = $header . "\r\n\r\n";
	}

	// Body text
	$body = $post->post_content;
	// Extract the URL info from <a> tags in the post content
	// to keep the link information since all HTML tags will be stripped by mixi.
	// For example, The following string '<a href="http://mixi.jp">mixi</a>'
	// will be replaced like 'mixi (http://mixi.jp)'.  
	// TODO: It should be able to handle more complex links.  
	// The logic can handle only simple <a> tags now.
	$body = preg_replace('/<a\s*href\=\"([^\"]*)\"[^>]*>([^<]*)<\/a>/i', '${2}(${1})', $body);
		
	// Footer text	
	if ( $footer != '' ) {
		$footer = str_replace( '%%URL%%', $post->guid, $footer );
		$footer = "\r\n\r\n" . $footer;
	}
	// content = header + body + footer	
	$content = $header . $body . $footer;
	// strip html thingy
	$content = sanitize_content( $content );
	// Publish to Mixi
	P2Mixi_publishToMixi( $P2Mixi_username, $P2Mixi_password, $P2Mixi_id, $post->post_title, $content, $images );
	return $postId;
}

//function P2Mixi_publishToMixi ( $username, $password, $id, $title, $content, $images　) 
function P2Mixi_publishToMixi () {	
	global $P2Mixi_debug;
	
	// Using variable number of parameters. 
	$args = func_get_args();
	$username = $args[0];
	$password = $args[1];
	$id       = $args[2];
	$title    = $args[3];
	$content  = $args[4];
	if ( func_num_args() == 6 )
	{
		$images   = $args[5];
	}
	
	// WSSE Authentication
	$nonce       = ""; 
	if ( function_exists( 'posix_getpid' ) ) {
		$nonce   = pack('H*', sha1(md5(time().rand().posix_getpid())));
	} else {
		// Use uniqid() in case of windows.
		$nonce   = pack('H*', sha1(md5(time().rand().uniqid())));
	}
	
	$created     = date('Y-m-d\TH:i:s\Z');
	$digest      = base64_encode(pack('H*', sha1($nonce . $created . $password)));
	$wsse_text   = 'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"';
	$wsse_header = sprintf($wsse_text, $username, $digest, base64_encode($nonce), $created);
	
	// mixi POST URL
	$url = 'http://mixi.jp/atom/diary/member_id=' . $id;
	$client = new P2Mixi_TinyHttpClient();
	$client->setDebugMode($P2Mixi_debug);
	
	//------------------------------------------------------------
	// Post Image
	//------------------------------------------------------------
	if ( $P2Mixi_debug ) error_log ( "P2Mixi_publishToMixi(): # of images : " . sizeof( $images ) );
	if ( sizeof( $images ) > 0 )
	{
		if ( $P2Mixi_debug ) error_log ( "P2Mixi_publishToMixi(): Uploading images to Mixi." );
		$client->addRequestHeader('X-WSSE', $wsse_header);
		$client->addRequestHeader('Content-Type', 'image/jpeg');
		$client->post( $url, $images[0] );		
		$headers  = $client->getResponseHeaders();
		$location = $headers['Location'];
		if ( $P2Mixi_debug ) error_log ( "P2Mixi_publishToMixi(): Finished uploading images to Mixi." );
		if ( $P2Mixi_debug ) error_log ( "P2Mixi_publishToMixi(): location: $location" );
		
		if ( $location != '' )
		{
			$url = $location;
		}
	}
	
	//------------------------------------------------------------
	// Post Text
	//------------------------------------------------------------
	$body = "<?xml version='1.0' encoding='utf-8'?>"
    . "<entry xmlns='http://www.w3.org/2007/app'>"
	. "<title>$title</title>"
	. "<summary>$content</summary>"
	. "</entry>";
	
	$client->addRequestHeader('X-WSSE', $wsse_header);
	$client->addRequestHeader('Content-Type', 'application/atom+xml');
	if ( $P2Mixi_debug ) error_log ( "P2Mixi_publishToMixi(): Uploading text to Mixi." );
	$client->post( $url, $body );
	if ( $P2Mixi_debug ) error_log ( "P2Mixi_publishToMixi(): Finished uploading text to Mixi." );	
}

// Register actions to wordpress.
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_init', 'P2Mixi_adminInit' );
	add_action( 'admin_menu', 'P2Mixi_renderOption' );
	add_action( 'publish_post', 'P2Mixi_publishHandler' );
}

if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook( __FILE__, 'P2Mixi_activate' );
}

/**
 * Sanitize body text
 * - Strip html tags
 * - Decode html entities
 */
function sanitize_content ( $text ) {
	$ret = $text;
	$ret = preg_replace(
		array(
		// Remove invisible content
			'@<head[^>]*?>.*?</head>@siu',
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noframes[^>]*?.*?</noframes>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
		// Add line breaks before and after blocks
			'@</?((address)|(blockquote)|(center)|(del))@iu',
			'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
			'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
			'@</?((table)|(th)|(td)|(caption))@iu',
			'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
			'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
			'@</?((frameset)|(frame)|(iframe))@iu',
		// convert br to crlf
			'@<br */?>@i',
		),
		array(
			' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
			"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
			"\n\$0",
			"\n",
		),
		$ret );
	$ret = strip_tags( $ret );
	$ret = preg_replace(
		array(
			"@&nbsp;@i",
		),
		array(
			" ",
		),
		$ret );
	$ret = html_entity_decode( $ret, ENT_QUOTES, "utf-8" );
	return $ret;
}

/**
 * Jpeg Extractor
 *
 * Extracts jpeg image data from the IMG tags in the given HTML.
 *
 */
class P2Mixi_JpegExtractor {
	var $debug = false;
	// Mixi's max number of images per entry through AtomPub API is 1. 
	var $maxNumOfImages = 1;

	/**
	 * Constructor.
	 *
	 * @return P2Mixi_JpegExtractor
	 */
	function P2Mixi_JpegExtractor () {
	}
	
	function setDebugMode ( $debug )
	{
		$this->debug = $debug;
	}
		
	/**
	 * Extracts jpeg image data from the IMG tags in the given HTML.
	 *
	 * @param string $html
	 */
	function extract ( $html )	{
		$urls = $this->_extractUrls( $html );
		$cnt = 0;
		$images = array();

		for ($i=0, $j=count ( $urls ); $i<$j; $i++ )	{
			$image = $this->_getData( $urls[$i] );
			if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor.extract(): Image URL: $urls[$i], size : ". strlen( $image ) );
			if ( $this->_isJpeg( $image ) == true )	{
				if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor.extract(): It is jpeg data." );
				array_push( $images, $image );
				$cnt++;
				if ( $cnt == $this->maxNumOfImages ) {	
					break;
				}
			}
		}
		return $images;
	}

	/**
	 * Checking the data is really the jpeg data or not
	 * by checking the 7th - 11th byte of the data.
	 * If it is jpeg, the portion must be always 'JFIF' in string.
	 * http://en.wikipedia.org/wiki/JFIF
	 *
	 * Update : If the image has Exif info, the previous logic doesn't work.
	 * Changed the logic to look for the string 'JFIF' in the data.
	 * Ideally, it should understand the Exif headers.
	 */
	function _isJpeg ( $data ) {
		$idx = strpos( $data, 'JFIF' );
		if ( $idx == false ) {
			return false;
		} else {
			return true;
		}
	}
	/**
	 *
	 *
	 * @param unknown_type $url
	 * @return unknown
	 */
	function _getData ( $url ) {

		// Get the image data.
		$client = new P2Mixi_TinyHttpClient();
		$client->setDebugMode( $this->debug );
		$client->requestHeaders["Connection"] = "Keep-Alive";
		$contents = $client->get( $url );
		return $contents;
	}

	/**
	 *
	 *
	 * @param unknown_type $html
	 * @return unknown
	 */
	function _extractUrls ( $html ) {
		$res = array();
		preg_match_all( "/(<img[^>]*>)/i", $html, $matches, PREG_SET_ORDER );
		for ( $i = 0; $i < count( $matches ); $i++ ) {
			if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor._extractUrls(): >>>$matches[$i][1]<<<" );
			preg_match( "/src=\"([^\"]+)\"/i", $matches[$i][1], $url );
			if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor._extractUrls(): >>>$url[1]<<<" );
			array_push( $res, $url[1] );
		}
		return $res;
	}
}

/**
 * Http socket class who knows how to send and receive http headers and body.

 * Copyright (c) 2001, 2002 by Martin Tsachev. All rights reserved.
 * mailto:martin@f2o.org
 * http://martin.f2o.org
 * 
 * Redistribution and use in source and binary forms,
 * with or without modification, are permitted provided
 * that the conditions available at
 * http://www.opensource.org/licenses/bsd-license.html
 * are met.
*/
class P2Mixi_TinyHttpSocket {
	var $fp = null;
	var $host;
	var $port;
	var $getlen = 1024;
	var $debug = false;

	var $request;
	var $connection;

	/**
	 * constructor
	 */
	function P2Mixi_TinyHttpSocket ( $host, $port ) {
		$this->host = $host;
		$this->port = $port;
	}

	function setDebugMode ( $debug ) {
		$this->debug = $debug;
	}

	function connect ( ) {
		if ( $this->fp ) {
			return true;
		}
		$this->fp = fsockopen( $this->host, $this->port, $errno, $errstr, 30 );
		if ( !$this->fp ) {
			return false;
		}
		return true;
	}

	function close ( ) {
		fclose( $this->fp );
	}

	function _constructHeaderString ( $headers ) {
		$str = "";
		foreach ( $headers as $name=>$value ) {
			$str .= "$name: $value\r\n";
		}
		return $str;
	}

	function send ( $method, $url, $headers, $body='' ) {
		$out = "$method $url HTTP/1.1\r\n";
		$out .= $this->_constructHeaderString( $headers );
		$out .= "\r\n";
		if ( $body != '' ) {
			$out .= $body;
		}
		$this->request = $out;
		if ( isset( $headers["Connection"] ) ) {
			$this->connection = strtolower( $headers["Connection"] );
		}
		if ( $this->fp ) {
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpSocket.send():  $out " );
			fwrite( $this->fp, $out );
		}
	}

	function recv ( &$headers, &$body ) {
		$headers = array();
		$header = fgets( $this->fp, $this->getlen );
		if ( !$header ) { // if disconnected since send
			$this->connect();
			fputs( $this->fp, $this->request );
			$header = fgets( $this->fp, $this->getlen );
		}

		preg_match( '|^HTTP.+ (.+) |', $header, $matches );
		$headers["Status-Line"] = trim( $header );
		$headers["Status-Code"] = intval( $matches[1] );
		if ( $this->debug ) error_log( "P2Mixi_TinyHttpSocket.recv(): Status line: ".$header );

		if ( $header == "" ) return;

		$mime = '';
		$transfer = '';
		$connection = $this->connection;
		
		while ( $line = fgets( $this->fp, $this->getlen ) ) {
			if ( $line == "\r\n" ) { break; }
			$param = explode( ":", $line, 2 );
			$name = trim( $param[0] );
			$value = trim( $param[1] );
			$headers[$name] = $value;
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpSocket.recv(): $name = $value" );

			switch( $name ) {
				case 'Content-Length':
					$length = intval( $value );
					break;
				case 'Content-Type':
					$mime = strtolower( $value );
					break;
				case 'Connection':
					$connection = strtolower( $value );
					break;
				case 'Transfer-Encoding':
					$transfer = strtolower( $value );
				break;
			}
		 }

		$body = '';

		if ( $connection == 'close' ) {
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpSocket.recv(): looping for closed connection" );
			while ( !feof( $this->fp ) ) {
				$body .= fread( $this->fp, $this->getlen );
			}
			return ;
		}

		if ( isset( $length ) and strpos( $transfer, 'chunked' ) === false) {
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpSocket.recv(): looping unchunked keep-alive connection for $length" );
			while ( true ) {
				if ( $length <= 0 ) { break; }
				$read = fread( $this->fp, $length );
				$length -= strlen( $read );
				$body .= $read;
			}
			return ;
		}

		// chunked encoding
		$length = fgets( $this->fp, $this->getlen );
		$length = hexdec( $length );

		if ( $this->debug ) error_log( "P2Mixi_TinyHttpSocket.recv(): looping chunked keep-alive connection for $length" );
		while ( true ) {
			if ( $length == 0 ) { break; }
			$body .= fread( $this->fp, $length );
			fgets( $this->fp, $this->getlen );
			$length = fgets( $this->fp, $this->getlen );
			$length = hexdec( $length );
		}

		fgets( $this->fp, $this->getlen );
		return;
	}
}


/**
 * Http client class
 */
class P2Mixi_TinyHttpClient {
	var $cookies = array();
	var $debug = false;

	// Default request headers.
	var $requestHeaders = array();
	var $mimeBoundary = "---------------------------111111111111111111111111111";

	var $responseHeaders = null;
	
	/**
	 * constructor
	 */
	function P2Mixi_TinyHttpClient ( ) {
		# Init default http request headers.
		$this->requestHeaders["Accept"] = '*/*';
		$this->requestHeaders["Connection"] = "Close";
	}

	
	function setDebugMode ( $debug )
	{
		$this->debug = $debug;
	}
	
	function addRequestHeader ( $name, $value ) {
		$this->requestHeaders[$name] = $value;
	}
	
	function getResponseHeaders () {
		return $this->responseHeaders;
	}
	
	/**
	 * Run GET http request.
	 */
	function get ( $url = "", $retries = 0 ) {
		$res = null;
		if ( $retries > 10 ) {
			error_log( "P2Mixi_TinyHttpClient.get(): too many retries for $url" );
			return $res;
		}
		if ( $url == "" ) {
			error_log( 'P2Mixi_TinyHttpClient.get(): $url is empty.' );
		} else {
			// Parse the URL to get the host name and port number.
			$this->_parseUrl( $url, $host, $port, $trail);
			if ( !$port ) $port = 80;
			if ( $this->debug ) error_log ( "P2Mixi_TinyHttpClient.get(): Host >>>$host<<< Port >>>$port<<<" );

			$sock = new P2Mixi_TinyHttpSocket( $host, $port );
			$sock->setDebugMode( $this->debug );
			if ( !$sock->connect() ) {
				error_log( "P2Mixi_TinyHttpClient.get(): fsockopen failed: $errstr ( $errno )" );
			} else {
				$headers = $this->requestHeaders;
				$headers["Host"] = $host;
				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}

				$sock->send( "GET", $url, $headers );
				$sock->recv( $resp_headers, $resp_body );
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient.get():  socket recv end " );
				if ( $resp_headers["Status-Code"] == 403 && $this->debug ) error_log( "P2Mixi_TinyHttpClient.get():  Body: $resp_body " );

				$this->_setResponseHeaders( $resp_headers );
				$res = $resp_body;
				$sock->close();

				if ( isset( $this->responseHeaders["Status-Code"] ) ) {
					$code = $this->responseHeaders["Status-Code"];
					switch( $code ) {
					case ( (300 <= $code && $code <= 303) || $code == 307 ):
						if ( isset( $this->responseHeaders["Location"] ) ) {
							$location = $this->responseHeaders["Location"];
							if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient.get(): Redirecting($retries retries so far): $location" );
							return $this->get( $location, $retries + 1 );
						}
					break;
					}
				}
			}
		}
		return $res;
	}

	/**
	 * Run POST http request.
	 */
	function post ( $url = "", $body = "" ) {
		$res = "";
		if ( $url == "" ) {
			error_log( 'P2Mixi_TinyHttpClient.post(): $url is empty.' );
		} elseif ( $body == null ) {
			error_log( 'P2Mixi_TinyHttpClient.post(): $body is empty.' );
		} else {
			$this->_parseUrl( $url, $host, $port, $trail);
			if ( !$port ) $port = 80;
			$sock = new P2Mixi_TinyHttpSocket( $host, $port );
			$sock->setDebugMode( $this->debug );
			if ( !$sock->connect() ) {
				error_log( "P2Mixi_TinyHttpClient.post(): fsockopen failed: $errstr ( $errno )" );
			} else {
				$headers = $this->requestHeaders;
				$headers['Content-Length'] = strlen( $body );
				$headers['Host'] = $host;

				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}
				 
				$sock->send( "POST", $url, $headers, $body );
				$sock->recv( $resp_headers, $resp_body );
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient.post():  socket recv end " );
				$sock->close();
				$this->_setResponseHeaders( $resp_headers );
				$res = $resp_body;
			}
		}
		return $res;
	}
		
	/**
	 * Parse url and return host, port, trailing path
	 */
	function _parseUrl ( $url, &$host, &$port, &$trail ) {
		$comps = parse_url( $url );
		$path = $comps['path'];
		if ( $path == "" ) $path = '/';
		if ( isset( $comps['query'] ) ) {
			$path .= '?' . $comps['query'];
		}
		if ( isset( $comps['fragment'] ) ) {
			$path .= '#' . $comps['fragment'];
		}
		$host = $comps['host'];
		$port = $comps['port'];
		$trail = $path;
		return $comps;
	}
	/**
	 * Parse http response header
	 */
	function _setResponseHeaders ( $headers ) {
		$this->responseHeaders = $headers;
		if( isset( $headers['Set-Cookie'] ) ) {
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseResponseHeaders(): cookie found :  $line " );
			$this->_parseCookie($headers['Set-Cookie']);
		}
	}
	
	/**
	 * Parse the cookies from the http response
	 */
	function _parseCookie ( $line ) {
		// Sample set-cookie line is like following.
		// name=value; path=/; expires=Wednesday, 09-Nov-99 23:12:40 GMT
		
		// Get the "name=value" part
		$cookie = explode( ";", $line );
		// Split name and value.
		$cookie = explode ( "=", $cookie[1] );
		if ( count( $cookie ) == 2 )
		{
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseCookie(): $cookie[0] = $cookie[1]" );
			$this->cookies[trim( $cookie[0] )] = trim( $cookie[1] );
		}
	}

	function _constructCookieString () {
		$str = "";
		foreach ( $this->cookies as $name=>$value ) {
			$str .= "$name=$value;";
		}
		return $str;
	}

	function _constructPostData ( $params ) {
		$str = "";
		foreach ( $params as $name=>$value ) {
			$str .= "$name=$value&";
		}
		// Take off the last '&'
		$str = substr( $str, 0, strlen( $str ) - 1 );
		return $str;
	}

	function _constructMimeData ( $params ) {
		$str = "";
		foreach ( $params as $name=>$value ) {

			if ( is_string( $value ) ) {
				$str .= '--';
				$str .= $this->mimeBoundary;
				$str .= "\r\n";
				$str .= "Content-Disposition: form-data; name=\"$name\"\r\n\r\n";
				$str .= "$value\r\n";
			} elseif ( is_array( $value ) ) {
				$str .= '--' . $this->mimeBoundary;
				$str .= "\r\n";
				$str .= 'Content-Disposition: form-data; name="'.$value['name'].'"; ' ;
				$str .= 'filename="';
				$str .= $value['filename'];
				$str .= "\"\r\n";
				//        $str .= "Content-Transfer-Encoding: base64\r\n";
				$str .= 'Content-type:';
				$str .= $value['content-type'];
				$str .= "\r\n\r\n";
				//        $str .= chunk_split( base64_encode( $value['data'] ) ) ;
				$str .= $value['data'];
				$str .= "\r\n";
			}

		}
		$str .= '--' . $this->mimeBoundary . "--";
		return $str;
	}
}



?>
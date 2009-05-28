<?php
/*
 Plugin Name: publishToMixi
 Plugin URI: http://ksnn.com/diary/?page_id=2437
 Description: Publish the post to Mixi using AtomPub
 Author: Kei Saito
 Version: 2.1
 Author URI: http://ksnn.com/
 Contributors: ento
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
$p2mixi_debug = true;

// ----------------------------------------------------------------------------
/**
 * Register settings (2.7 compatibility)
 */
function p2mixi_admin_init () {
	if ( function_exists( 'add_option_update_handler' ) ) {
		add_option_update_handler( 'p2mixi', 'p2mixi_username' );
		add_option_update_handler( 'p2mixi', 'p2mixi_password' );
		add_option_update_handler( 'p2mixi', 'p2mixi_id' );
		add_option_update_handler( 'p2mixi', 'p2mixi_header_default' );
		add_option_update_handler( 'p2mixi', 'p2mixi_footer_default' );
		add_option_update_handler( 'p2mixi', 'p2mixi_default' );
	}
}

/**
 * Register settings (the old way)
 */
function p2mixi_activate () {
	if ( function_exists( 'add_option' ) ) {
		add_option( 'p2mixi_username' );
		add_option( 'p2mixi_password' );
		add_option( 'p2mixi_id' );
		add_option( 'p2mixi_header_default' );
		add_option( 'p2mixi_footer_default' );
		add_option( 'p2mixi_default', true );
	}
}

// ----------------------------------------------------------------------------
/**
 * Renders the option box in the "Write Post" page in the wordpress admin.
 */
function p2mixi_render_option () {
	// expects 'add_meta_box' function... wordpress 2.5 and and above.
	add_meta_box( 'myplugin_sectionid', __( 'Publish To Mixi', 'p2mixi_textdomain' ), 
		'p2mixi_render_option_content', 'post', 'advanced' );
//	add_options_page( __( 'mixi autopost settings', 'p2mixi_textdomain'), __( 'Mixi Autoposting', 'p2mixi_textdomain' ), 8, __FILE__, 'p2mixi_render_admin_option_content');
	add_options_page( __( 'Publish To Mixi', 'p2mixi_textdomain'), __( 'Publish To Mixi', 'p2mixi_textdomain' ), 8, __FILE__, 'p2mixi_render_admin_option_content');
}

function p2mixi_render_admin_option_content () {
	?>
	<div class="wrap">
	<h2><?php echo __('Publish To Mixi', 'p2mixi_textdomain') ?></h2>
	<form method="post" action="options.php">
	<?php settings_fields('p2mixi'); ?>
        <h3><?php echo __('Login info', 'p2mixi_textdomain') ?></h3>
	<p></p>
	<table class="form-table">
	<tr valign="top">
	   <th scope="row"><?php echo __('Username:', 'p2mixi_textdomain') ?></th>
		<td><input type="text" name="p2mixi_username" value="<?php echo get_option('p2mixi_username'); ?>" /><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('Password:', 'p2mixi_textdomain') ?></th>
		<td><input type="password" name="p2mixi_password" value="<?php echo get_option('p2mixi_password'); ?>" /><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('ID:', 'p2mixi_textdomain') ?></th>
		<td><input type="text" name="p2mixi_id" value="<?php echo get_option('p2mixi_id'); ?>" /><td>
        </tr>
	</table>
        <h3><?php echo __('Posting defaults', 'p2mixi_textdomain') ?></h3>
	<p></p>
	<table class="form-table">
	<tr valign="top">
	   <th scope="row"><?php echo __('Header:', 'p2mixi_textdomain') ?></th>
		<td><textarea name="p2mixi_header_default" cols="60" rows="4"><?php echo get_option('p2mixi_header_default'); ?></textarea><br/>
		<?php echo __('%%URL%% will be replaced with the post permalink.', '') ?><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('Footer:', 'p2mixi_textdomain') ?></th>
		<td><textarea name="p2mixi_footer_default" cols="60" rows="4"><?php echo get_option('p2mixi_footer_default'); ?></textarea><br/>
		<?php echo __('%%URL%% will be replaced with the post permalink.', '') ?><td>
        </tr>
	<tr valign="top">
	   <th scope="row"><?php echo __('Default:', 'p2mixi_textdomain') ?></th>
		<td><label for="p2mixi_default">
		<input type="checkbox" name="p2mixi_default" id="p2mixi_default" <?php if ( get_option('p2mixi_default') == true ) { echo 'checked="checked"'; } ?> />
		<?php echo __( 'Publish to mixi by default', 'p2mixi_textdomain' ) ?>
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
 * Renders the option box content. This will be called by p2mixi_renderOption().
 */
function p2mixi_render_option_content () {
	$p2mixi_default = get_option( 'p2mixi_default' );
	$p2mixi_header_default = get_option( 'p2mixi_header_default' );
	$p2mixi_footer_default = get_option( 'p2mixi_footer_default' );

	?>
	
	<input type="hidden" name="p2mixi_noncename" id="p2mixi_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ) ?>" />
	<div>
		<input type="checkbox" name="p2mixi_publishcheckbox" id="p2mixi_publishcheckbox" <?php if ( $p2mixi_default == true ) { echo 'checked="checked"'; } ?> />
		<label for="p2mixi_publishbox"> <?php echo __("Publish To Mixi", 'p2mixi_textdomain' ) ?> </label>
	</div>

	<div style="margin-top:6px">
		<label for="p2mixi_headertext"> <?php echo __("Header Text", 'p2mixi_textdomain' ) ?> </label>
	</div>
	<div>
		<textarea style="width:98%" name="p2mixi_headertext" id="p2mixi_headertext"><?php  echo $p2mixi_header_default; ?></textarea>
	</div>
	<div style="margin-top:6px">
		<label for="p2mixi_footertext"> <?php echo __("Footer Text", 'p2mixi_textdomain' ) ?> </label>
	</div>
	<div>
		<textarea style="width:98%" name="p2mixi_footertext" id="p2mixi_footertext"><?php  echo $p2mixi_footer_default; ?></textarea>
	</div>
	
	<?php 
}

/**
 * Publishes the wordpress entry to mixi.
 *
 * @param number $postId
 * @return postId
 */
function p2mixi_publish_handler ( $postId ) {
	global $p2mixi_debug;
	$p2mixi_username = get_option( 'p2mixi_username' );
	$p2mixi_password = get_option( 'p2mixi_password' );
	$p2mixi_id = get_option( 'p2mixi_id' );
	$p2mixi_default = get_option( 'p2mixi_default' );
	$p2mixi_header_default = get_option( 'p2mixi_header_default' );
	$p2mixi_footer_default = get_option( 'p2mixi_footer_default' );
		
	$header = "";
	$footer = "";	
		
	// In case the entry was posted from the tool, not from the wp-admin page
	// by checking any of text input field was there or not.
	if ( $_POST['p2mixi_footertext'] == null ) {
		if ( $p2mixi_default == false ) {
			return $postId;
		}
		
		$header = $p2mixi_header_default;
		$footer = $p2mixi_footer_default;
				
	} else {

		// verify this came from the our screen and with proper authorization,
		// because publish_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['p2mixi_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		
		// In case the entry was posted from the wp-admin page.
		if ( $_POST['p2mixi_publishcheckbox'] == null || $_POST['p2mixi_publishcheckbox'] == 'false'  ) {
			return $postId;
		}
		$header = trim( $_POST['p2mixi_headertext'] );
		$footer = trim( $_POST['p2mixi_footertext'] );
	}
	
	// Get the post detail from wordpress.
	$post = get_post( $postId );
	if ( $post->post_status != 'publish' ) {
		return $postId;
	}
	// Extracting images from the post content.
	$images = p2mixi_extract_jpeg_images( $post->post_content );
	
	// Header
	if ( $header != '' ) {
		$header = str_replace( '%%URL%%', $post->guid, $header );
		$header = p2mixi_sanitize_html ( $header . "\r\n\r\n" );		
	}

	// Body
	$body = $post->post_content;
	$body = p2mixi_replace_hyperlinks( $body, array( $images['urls'][0] ) );
	$body = p2mixi_sanitize_html( $body );

	// Footer	
	if ( $footer != '' ) {
		$footer = str_replace( '%%URL%%', $post->guid, $footer );
		$footer = p2mixi_sanitize_html( "\r\n\r\n" . $footer );
	}
	
	// Chop the body if it is too big.
	// The max length for the mixi diary body is 10k letters.
	// Actually it is 10k 'Japanese' letters (20k bytes) so it could be more 
	// if you use ASCII letters, but here just say 10k letters to make it safer.
	//
	// Not sure mb_ functions are available in any php env or not.
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		$max_body_len = 10000 - mb_strlen( $header, 'utf-8' ) - mb_strlen ( $footer, 'utf-8' );
		if ( $max_body_len < mb_strlen( $body )) {
			$body = mb_substr( $body, 0, $max_body_len, 'utf-8' );
		}
	}
	
	// content = header + body + footer	
	$content = $header . $body . $footer;

	// Publish to Mixi
	p2mixi_publish_to_mixi( $p2mixi_username, $p2mixi_password, $p2mixi_id, $post->post_title, $content, $images['images'] );
	return $postId;
}

function p2mixi_publish_to_mixi () {	
	global $p2mixi_debug;
	
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
	$client = new p2mixi_TinyHttpClient();
	$client->setDebugMode($p2mixi_debug);
	
	//------------------------------------------------------------
	// Post Image
	//------------------------------------------------------------
	if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi(): # of images : " . sizeof( $images ) );
	if ( sizeof( $images ) > 0 )
	{
		if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi(): Uploading images to Mixi." );
		$client->addRequestHeader('X-WSSE', $wsse_header);
		$client->addRequestHeader('Content-Type', 'image/jpeg');
		$client->post( $url, $images[0] );
		$headers  = $client->getResponseHeaders();
		$location = $headers['Location'];
		if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi(): Finished uploading images to Mixi." );
		if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi(): location: $location" );
		
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
	if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi(): Uploading text to Mixi." );
	$client->post( $url, $body );
	if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi(): Finished uploading text to Mixi." );	
}

// ----------------------------------------------------------------------------
// Register actions to wordpress.
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_init', 'p2mixi_admin_init' );
	add_action( 'admin_menu', 'p2mixi_render_option' );
	add_action( 'publish_post', 'p2mixi_publish_handler' );
}

if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook( __FILE__, 'p2mixi_activate' );
}

// ----------------------------------------------------------------------------
/**
 * Extract the URL info from <a> tags and <img> tags in the post content
 * to keep the link information since all HTML tags will be stripped before submitting to mixi.
 * For example, The following string '<a href="http://mixi.jp">mixi</a>'
 * will be replaced like 'mixi (http://mixi.jp)'.  
 * TODO: It should be able to handle more complex links.  
 * The logic can handle only simple <a> tags now.
 */
function p2mixi_replace_hyperlinks_callback_a_tag ( $m ) {
	// It's better to check if the URL is in $excludes here
	// but I couldn't find a way to refer to the $excludes variable
	// from inside this callback.
	// Hence the cleanup code afterwards.
	return $m[2] == $m[3] ? $m[2] : "$m[3]($m[2])";
}

function p2mixi_replace_hyperlinks_callback_img_tag ( $m ) {
	return $m[2];
}

function p2mixi_replace_hyperlinks ( $text, $excludes = array() ) {
	if ( $excludes == NULL ) $excludes = array();
	// First process <img> tags to process nested cases properly
	// i.e. <a><img/></a>
	$text = preg_replace_callback(
		'/<img\s*src\=(\"|\')([^\"\']*)\1[^\/]*\/?>/i',
		'p2mixi_replace_hyperlinks_callback_img_tag',
		$text);
	// Now the <a> tags
	$text = preg_replace_callback(
		'/<a\s*href\=(\"|\')([^\"\']*)\1[^>]*>([^<]*)<\/a>/i',
		'p2mixi_replace_hyperlinks_callback_a_tag',
		$text);

	// Remove $excludes
	foreach ( $excludes as $url ) {
		$url_re = str_replace( '/', '\/', $url );
		$url_re = str_replace( '?', '\?', $url_re );
		$text = preg_replace(
			"/(\($url_re\)|$url_re)/i",
			'',
			$text);
	}
	return $text;
}

/**
 * Sanitize body text
 * - Strip html tags
 * - Encode any special HTML characters to properly handle html entities
 */
function p2mixi_sanitize_html ( $text ) {
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
		// convert br to line break
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
	// &nbsp; -> &amp;nbsp; etc.
	$ret = htmlspecialchars( $ret, ENT_QUOTES, "utf-8" );
	return $ret;
}

// ----------------------------------------------------------------------------

function p2mixi_extract_jpeg_images ( $html, $max = 1 ) {
	global $p2mixi_debug;
	$cnt = 0;
	$images = array();
	$urls = array();
	
	// Matching all img tags
	preg_match_all( "/(<img[^>]*>)/i", $html, $matches, PREG_SET_ORDER );
	for ( $i = 0; $i < count( $matches ); $i++ ) {
		if ( $p2mixi_debug ) error_log ( "p2mixi_extract_jpeg_images: >>>$matches[$i][1]<<<" );

		// Download the image from the url
		preg_match( "/src=\"([^\"]+)\"/i", $matches[$i][1], $url );
		if ( $p2mixi_debug ) error_log ( "p2mixi_extract_jpeg_images: >>>$url[1]<<<" );

		$client = new p2mixi_TinyHttpClient();
		$client->setDebugMode( $p2mixi_debug );
		$client->requestHeaders["Connection"] = "Keep-Alive";
		$contents = $client->get( $url[1] );
		
		// Checking the data is really the jpeg data or not
		// by checking 'JFIF' string inside. 
		// http://en.wikipedia.org/wiki/JFIF
		//
		// Usually the string comes up in the 7th - 11th byte 
		// of the data, but it does not if the image contains exif data
		// because the exif headers comes before the JFIF appearance. 
		// Ideally, the logic should understand the exif structures.
		if ( strpos( $contents, 'JFIF' ) != false ) {
			array_push( $images, $contents );
			array_push( $urls, $url[1] );
			if ( ++$cnt == $max ) {	
				break;
			}
		}
	}
	return array( 'urls' => $urls, 'images' => $images );
}


// ----------------------------------------------------------------------------
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
class p2mixi_TinyHttpSocket {
	var $fp = null;
	var $host;
	var $port;
	var $getlen = 1024;
	var $timeout = 30;
	var $debug = false;

	var $request;
	var $connection;

	/**
	 * constructor
	 */
	function p2mixi_TinyHttpSocket ( $host, $port ) {
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
		$this->fp = @fsockopen( $this->host, $this->port, $errno, $errstr, $this->timeout );
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
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.send():  $out " );
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
		if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv(): Status line: ".$header );

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
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv(): $name = $value" );

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
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv(): looping for closed connection" );
			while ( !feof( $this->fp ) ) {
				$body .= fread( $this->fp, $this->getlen );
			}
			return ;
		}

		if ( isset( $length ) and strpos( $transfer, 'chunked' ) === false) {
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv(): looping unchunked keep-alive connection for $length" );
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

		if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv(): looping chunked keep-alive connection for $length" );
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
class p2mixi_TinyHttpClient {
	var $cookies = array();
	var $debug = false;

	// Default request headers.
	var $requestHeaders = array();
	var $mimeBoundary = "---------------------------111111111111111111111111111";

	var $responseHeaders = null;
	
	/**
	 * constructor
	 */
	function p2mixi_TinyHttpClient ( ) {
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
			error_log( "p2mixi_TinyHttpClient.get(): too many retries for $url" );
			return $res;
		}
		if ( $url == "" ) {
			error_log( 'p2mixi_TinyHttpClient.get(): $url is empty.' );
		} else {
			// Parse the URL to get the host name and port number.
			$this->_parseUrl( $url, $host, $port, $trail);
			if ( !$port ) $port = 80;
			if ( $this->debug ) error_log ( "p2mixi_TinyHttpClient.get(): Host >>>$host<<< Port >>>$port<<<" );

			$sock = new p2mixi_TinyHttpSocket( $host, $port );
			$sock->setDebugMode( $this->debug );
			if ( !$sock->connect() ) {
				error_log( "p2mixi_TinyHttpClient.get(): fsockopen failed: $errstr ( $errno )" );
			} else {
				$headers = $this->requestHeaders;
				$headers["Host"] = $host;
				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}

				$sock->send( "GET", $url, $headers );
				$sock->recv( $resp_headers, $resp_body );
				if ( $this->debug ) error_log( "p2mixi_TinyHttpClient.get():  socket recv end " );
				if ( $resp_headers["Status-Code"] == 403 && $this->debug ) error_log( "p2mixi_TinyHttpClient.get():  Body: $resp_body " );

				$this->_setResponseHeaders( $resp_headers );
				$res = $resp_body;
				$sock->close();

				if ( isset( $this->responseHeaders["Status-Code"] ) ) {
					$code = $this->responseHeaders["Status-Code"];
					switch( $code ) {
					case ( (300 <= $code && $code <= 303) || $code == 307 ):
						if ( isset( $this->responseHeaders["Location"] ) ) {
							$location = $this->responseHeaders["Location"];
							if ( $this->debug ) error_log( "p2mixi_TinyHttpClient.get(): Redirecting($retries retries so far): $location" );
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
			error_log( 'p2mixi_TinyHttpClient.post(): $url is empty.' );
		} elseif ( $body == null ) {
			error_log( 'p2mixi_TinyHttpClient.post(): $body is empty.' );
		} else {
			$this->_parseUrl( $url, $host, $port, $trail);
			if ( !$port ) $port = 80;
			$sock = new p2mixi_TinyHttpSocket( $host, $port );
			$sock->setDebugMode( $this->debug );
			if ( !$sock->connect() ) {
				error_log( "p2mixi_TinyHttpClient.post(): fsockopen failed: $errstr ( $errno )" );
			} else {
				$headers = $this->requestHeaders;
				$headers['Content-Length'] = strlen( $body );
				$headers['Host'] = $host;

				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}
				 
				$sock->send( "POST", $url, $headers, $body );
				$sock->recv( $resp_headers, $resp_body );
				if ( $this->debug ) error_log( "p2mixi_TinyHttpClient.post():  socket recv end " );
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
		$port = ( !isset( $comps['port'] ) || $comps['port'] == false ) ? 80 : $comps['port'];
		$trail = $path;
		return $comps;
	}
	/**
	 * Parse http response header
	 */
	function _setResponseHeaders ( $headers ) {
		$this->responseHeaders = $headers;
		if( isset( $headers['Set-Cookie'] ) ) {
			if ( $this->debug ) error_log( "p2mixi_TinyHttpClient._parseResponseHeaders(): cookie found :  $line " );
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
			if ( $this->debug ) error_log( "p2mixi_TinyHttpClient._parseCookie(): $cookie[0] = $cookie[1]" );
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

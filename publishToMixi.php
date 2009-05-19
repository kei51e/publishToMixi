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

// Mixi login credential
// Set your mixi login username (email addess) and password here.
$P2Mixi_username = "your_email@gmail.com";
$P2Mixi_password = "your_password";
// Your Mixi ID - numbers only.
$P2Mixi_id = "your_mixi_id";

// Publish to Mixi checkbox default value.
// If this is true, Publish to Mixi checkbox is checked by default. 
$P2Mixi_default = false;

// Header default string.
// '%%URL%%' will be replaced with the post permalink. 
$P2Mixi_headerDefault = '';
// Footer default string.
// '%%URL%%' will be replaced with the post permalink. 
$P2Mixi_footerDefault = '';

// Debug mode - for debug purpose only
$P2Mixi_debug = false;

// ----------------------------------------------------------------------------
/**
 * Renders the option box in the "Write Post" page in the wordpress admin.
 */
function P2Mixi_renderOption () {
	// expects 'add_meta_box' function... wordpress 2.5 and and above.
	add_meta_box( 'myplugin_sectionid', __( 'Publish To Mixi', 'P2Mixi_textdomain' ), 
		'P2Mixi_renderOptionContent', 'post', 'advanced' );
}
/**
 * Renders the option box content. This will be called by P2Mixi_renderOption().
 */
function P2Mixi_renderOptionContent () {
	global $P2Mixi_default, $P2Mixi_headerDefault, $P2Mixi_footerDefault;

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
	global $P2Mixi_username, $P2Mixi_password, $P2Mixi_id, 
		$P2Mixi_default, $P2Mixi_headerDefault, $P2Mixi_footerDefault;

		
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
	$client = new P2Mixi_TinyHttpClient( "mixi.jp", 80 );
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
	add_action( 'admin_menu', 'P2Mixi_renderOption' );
	add_action( 'publish_post', 'P2Mixi_publishHandler' );
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
		$host = "";
		$port = 80;
		// Parse the URL to get the host name and port number.
		preg_match ( "/\/\/([^\/]+)\//", $url, $regs );
		$arr = split( ':', $regs[1] );
		$host = $arr[0];
		if ( count( $arr ) == 2 )
			$port = intval( $arr[1] );

		if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor._getData(): Host >>>$host<<< Port >>>$port<<<" );

		// Get the image data.
		$client = new P2Mixi_TinyHttpClient( $host, $port );
		$client->setDebugMode($this->debug);
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
 * Http client class
 */
class P2Mixi_TinyHttpClient {
	var $cookies = array();
	var $host = "";
	var $port = 80;
	var $debug = false;

	// Default request headers.
	var $requestHeaders = array();
	var $mimeBoundary = "---------------------------111111111111111111111111111";

	var $responseHeaders = null;
	
	/**
	 * constructor
	 */
	function P2Mixi_TinyHttpClient ( $host = "", $port = 80 ) {
		$this->host = $host;
		$this->port = $port;

		# Init default http request headers.
		$this->requestHeaders["Host"] = $this->host;
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
	function get ( $url = "" ) {
		$res = null;
		if ( $url == "" ) {
			error_log( 'P2Mixi_TinyHttpClient.get(): $url is empty.' );
		} else {
			$fp = fsockopen( $this->host, $this->port, $errno, $errstr, 30 );
			if ( !$fp ) {
				error_log( "P2Mixi_TinyHttpClient.get(): fsockopen failed: $errstr ( $errno )" );
			} else {
				$headers = $this->requestHeaders;
				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}

				$out = "GET $url HTTP/1.1\r\n";
				$out .= $this->_constructHeaderString( $headers );
				$out .= "\r\n";
				fwrite( $fp, $out );
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient.get():  $out " );

				while ( !feof( $fp ) ) {
					$res .=  fgets( $fp, 128 );
				}
				fclose( $fp );
			}
			$this->_parseResponseHeaders( $res );
		}
		// cut off the response headers
		$res = substr( $res, strpos( $res, "\r\n\r\n" ) + 4 );
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
			$fp = fsockopen( $this->host, $this->port, $errno, $errstr, 30 );
			if ( !$fp ) {
				error_log( "P2Mixi_TinyHttpClient.post(): fsockopen failed: $errstr ( $errno )" );
			} else {
				$headers = $this->requestHeaders;
				$headers['Content-Length'] = strlen( $body );

				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}
				 
				$out = "POST $url HTTP/1.1\r\n";
				$out .= $this->_constructHeaderString( $headers );
				$out .= "\r\n";
				$out .= $body;
				fwrite( $fp, $out );
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient.post():  $out " );

				while ( !feof( $fp ) ) {
					$res .=  fgets( $fp, 128 );
				}
				fclose( $fp );
			}
		}
		$this->_parseResponseHeaders( $res );
		// cut off the response headers
		$res = substr( $res, strpos( $res, "\r\n\r\n" ) + 4 );
		return $res;
	}
	
	
	/**
	 * Parse http response header
	 */
	function _parseResponseHeaders ( $res ) {
		if ( $res == '' ) return;
		// Init response header array
		$this->responseHeaders = array();
		
		$res = substr( $res, 0, strpos( $res, "\r\n\r\n" ) );
		if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseResponseHeaders(): Response header: ".$res );
		
		$lines = explode( "\r\n", $res );
		if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseResponseHeaders(): count(lines) ".count( $lines ) );
		$index = -1;
		foreach ( $lines as $line ) {
			$param = explode( ":", $line, 2 );
			if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseResponseHeaders(): $param[0] = $param[1]" );
			$this->responseHeaders[trim( $param[0] )] = trim( $param[1] );			
			
			if ( preg_match ( "/^Set-Cookie/i", $line ) ) {
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseResponseHeaders(): cookie found :  $line " );
				$this->_parseCookie($line);
			}		
		}
	}
	
	/**
	 * Parse the cookies from the http response
	 */
	function _parseCookie ( $line ) {
		// Sample set-cookie line is like following.
		// Set-Cookie: name=value; path=/; expires=Wednesday, 09-Nov-99 23:12:40 GMT
		
		// Get the "Set-Cookie: name=value" part
		$cookie = explode( ";", $line );
		// Take off "Set-Cookie:" 
		$cookie = explode ( ":", $cookie[0] );
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

	function _constructHeaderString ( $headers ) {
		$str = "";
		foreach ( $headers as $name=>$value ) {
			$str .= "$name: $value\r\n";
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
<?php
/*
 Plugin Name: Publish to Mixi
 Plugin URI: http://ksnn.com/diary/?page_id=2437
 Description: Publish the post to Mixi.
 Author: Kei Saito
 Version: 1.3
 Author URI: http://ksnn.com/
 */

/*
 * A wordpress plugin to publish the post to Mixi
 * Copyright (C) 2008 Kei Saito (http://ksnn.com/)
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

// Publish to Mixi checkbox default value.
// If this is true, Publish to Mixi checkbox is checked by default. 
$P2Mixi_default = false;

// Your WordPress encoding. In most of cases, it is 'utf-8'.
// Modify it if you use the other encoding for your wordpress.
$P2Mixi_wordpressEncoding = 'utf-8';
// Mixi encoding. So far they use 'euc-jp'. 
// Don't modify this.
$P2Mixi_mixiEncoding = 'euc-jp';

// DO NOT EDIT LINES BELOW
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
	global $P2Mixi_default;

	?>
	<input type="hidden" name="P2Mixi_noncename" id="P2Mixi_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ) ?>" />
	<input type="checkbox" name="P2Mixi_publishcheckbox" id="P2Mixi_publishcheckbox" <?php if ( $P2Mixi_default == true ) { echo "checked"; } ?> />
	<label for="P2Mixi_publishbox"> <?php echo __("Publish To Mixi", 'P2Mixi_textdomain' ) ?> </label>
	<?php 
}

/**
 * Publishes the wordpress entry to mixi.
 *
 * @param number $postId
 * @return postId
 */
function P2Mixi_publishHandler ( $postId ) {
	global $P2Mixi_username, $P2Mixi_password, $P2Mixi_wordpressEncoding, $P2Mixi_mixiEncoding;

	// verify this came from the our screen and with proper authorization,
	// because publish_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['P2Mixi_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
		
	if ( $_POST['P2Mixi_publishcheckbox'] == null || $_POST['P2Mixi_publishcheckbox'] == 'false'  ) {
		return $postId;
	}
	// Get the post detail from wordpress.
	$post = get_post( $postId );
	if ( $post->post_status != 'publish' ) {
		return $postId;
	}
	// Extracting images from the post content.
	$extractor = new P2Mixi_JpegExtractor();
	$images = $extractor->extract( $post->post_content );

	// Create P2Mixi_MixiConnector instance.
	$connector = new P2Mixi_MixiConnector ( $P2Mixi_username, $P2Mixi_password );
	// Publish the entry to mixi.
	$connector->publishDiary( $post->post_title, $post->post_content, $images, $P2Mixi_wordpressEncoding, $P2Mixi_mixiEncoding );

	return $postId;
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
	// Mixi's max number of images per entry is 3. (using free account)
	var $maxNumOfImages = 3;

	/**
	 * Constructor.
	 *
	 * @return P2Mixi_JpegExtractor
	 */
	function P2Mixi_JpegExtractor () {
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
			if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor.extract(): URL: $urls[$i]" );
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

		if ( $this->debug ) error_log ( "P2Mixi_JpegExtractor._getData(): Host: >>>$host<<<, Port: >>>$port<<<" );

		// Get the image data.
		$client = new P2Mixi_TinyHttpClient( $host, $port );
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
 * Mixi connector class
 *
 */
class P2Mixi_MixiConnector {
	var $debug = false;
	/**
	 * constructor
	 *
	 * @param unknown_type $username
	 * @param unknown_type $password
	 * @return P2Mixi_MixiConnector
	 */
	function P2Mixi_MixiConnector ( $username = "", $password = "" ) {
		$this->username = $username;
		$this->password = $password;
	}
	/**
	 * Posts the diary to mixi.
	 *
	 * @param unknown_type $title
	 * @param unknown_type $content
	 */
	function publishDiary ( $title = "", $content = "", $images = null, $wordpressEncoding = "utf-8", $mixiEncoding = "euc-jp" ) {
		if ( $title == "" || $content == "" ) {
			return;
		}
		
		error_log( '[Message] P2Mixi_MixiConnector.publishDiary(): Start publishing the dairy to mixi...' );
		
		// Take off all the html tags from the post since tags don't work in mixi post.
		$content = strip_tags( $content );
		// If there are more than 2 line breaks, remove redundant line breaks and
		// make it 2 line breaks.
		$content = preg_replace( "/(\\r\\n){3,}/", "\r\n\r\n", $content );
		// escape unicode special chars to html numeric character references.
		$content = $this->_escapeSpecialChars( $content );
		$title = $this->_escapeSpecialChars( $title );		
		// Convert the encoding from utf-8 to euc-jp.
		// Mixi is based on euc-jp encoding.
		// Use mb_convert_encoding if available, if not, use iconv.
		if ( function_exists( 'mb_convert_encoding' ) ) {
			error_log( '[Message] P2Mixi_MixiConnector.publishDiary(): Using mb_convert_encoding() for conversion.' );
			$title = mb_convert_encoding( $title, $mixiEncoding, $wordpressEncoding );
			$content = mb_convert_encoding( $content, $mixiEncoding, $wordpressEncoding );
		} else {
			error_log( '[Message] P2Mixi_MixiConnector.publishDiary(): Using iconv() for conversion.' );
			$title = iconv( $wordpressEncoding, $mixiEncoding, $title );
			$content = iconv( $wordpressEncoding, $mixiEncoding, $content );
		}
		
		// URL encode the title and content.
		$title = urlencode( $title );
		$content = urlencode( $content );
		
		// Instanciate http client
		$client = new P2Mixi_TinyHttpClient( "mixi.jp", 80 );
		// Create login URL param
		$params = array();
		$params['email'] = $this->username;
		$params['password'] = $this->password;
		$params['next_url'] = '/home.pl';
		$params['sticky'] = 'off';
		// Login to mixi.
		$response = $client->post( "http://mixi.jp/login.pl", $params, false );
		if ( $this->debug ) error_log( 'P2Mixi_MixiConnector.publishDiary():  ' . htmlspecialchars( $response ) );
		sleep(1);
		
		// Access the check page after the login.
		$response = $client->get( "http://mixi.jp/check.pl?n=%2Fhome.pl" );
		if ( $this->debug ) error_log( 'P2Mixi_MixiConnector.publishDiary():  ' . htmlspecialchars( $response ) );
		sleep(1);
		
		// Access the home page.
		$response = $client->get( "http://mixi.jp/home.pl" );
		if ( $this->debug ) error_log( 'P2Mixi_MixiConnector.publishDiary():  ' . htmlspecialchars( $response ) );
		// Get the user id from the response.
		$userid = $this->_getId( $response );

		if ( $userid == "" ) {
			error_log( '[Message] P2Mixi_MixiConnector.publishDiary(): ID not found. Maybe you failed to login mixi. Exiting...' );
			return;
		}

		// Post the diary to the mixi.
		$params = array();
		$params['diary_body'] = $content;
		$params['diary_title'] = $title;
		$params['id'] = $userid;
		$params['tag_id'] = '0';
		$params['campaign_id'] = '';
		$params['invite_campaign'] = '';
		$params['news_title'] = '';
		$params['news_url'] = '';
		$params['movie_id'] = '';
		$params['movie_title'] = '';
		$params['movie_url'] = '';
		$params['submit'] = 'main';

		// It would be better to do the base64 encoding for images but we just send as is now 
		// since mixi can understand those raw image mime contents.  
		if ( $images != null ) {
			for ( $i = 1, $j = count( $images ); $i <= $j; $i++ )	{
				$params["photo$i"] =
					array( 'name'=>"photo$i",
              		'filename'=>"photo$i.jpg", 
					'content-type'=>'image/jpeg',
					'data'=>$images[$i-1] );
			}
			error_log( '[Message] P2Mixi_MixiConnector.publishDiary(): Sending ' . count( $images ) . ' images to mixi.' );
		}
		sleep(1);
		
		$response = $client->post( "http://mixi.jp/add_diary.pl", $params, true );
		if ( $this->debug ) error_log( 'P2Mixi_MixiConnector.publishDiary():  ' . htmlspecialchars( $response ) );
		// Get the post key from the response.
		$postkey = $this->_getPostKey( $response );

		// Access the post confirmation page.
		$params['submit'] = 'confirm';
		$params['post_key'] = $postkey;

		if ( $images != null ) {
			// Remove the image data from the parameter.
			for ( $i = 1, $j = count( $images ); $i <= $j; $i++ ) {
				unset( $params["photo$i"] );
			}
			// Get the 'packed' (image id) from the response.
			$params['packed'] = $this->_getPacked( $response );

		}
		sleep(1);
		
		$response = $client->post( "http://mixi.jp/add_diary.pl", $params, false );
		if ( $this->debug ) error_log( 'P2Mixi_MixiConnector.publishDiary():  ' . htmlspecialchars( $response ) );

		error_log( '[Message] P2Mixi_MixiConnector.publishDiary(): The diary has been successfully published to mixi!' );

	}

	function _getId ( $string = "" ) {
		$id = "";
		if ( preg_match ( "/add_diary.pl\?id=([0-9]+)/", $string, $regs ) ) {
			$id = $regs[1];
			if ( $this->debug ) error_log( "P2Mixi_MixiConnector._getId(): ID found : $id " );
		}
		return $id;
	}
	/**
	 * Looks for the value for the 'post_key' in hidden form parameter
	 * from the given html.
	 *
	 * @param unknown_type $html
	 * @return unknown
	 */
	function _getPostKey ( $html = "" ) {
		$id = "";
		if ( preg_match ( "/name=\"post_key\"\\s+value=\"([^\"]+)\"/", $html, $regs ) ) {
			$id = $regs[1];
			if ( $this->debug ) error_log( "P2Mixi_MixiConnector._getPostKey(): Post key found : $id " );
		}
		return $id;
	}

	/**
	 * Looks for the value for the 'packed' in hidden form parameter
	 * from the given html.
	 *
	 * @param string $html
	 * @return unknown
	 */
	function _getPacked ( $html = "" ) {
		$id = "";
		if ( preg_match ( "/name=\"packed\"\\s+value=\"([^\"]+)\"/", $html, $regs ) ) {
			$id = $regs[1];
			if ( $this->debug ) error_log( "P2Mixi_MixiConnector._getPacked(): packed found : $id " );
		}
		return $id;
	}

	/**
	 * Replaces utf-8 special chars to the numeric character references. 
	 * TODO: This function should be located in an appropriate location.
	 * 
	 * @param string $html
	 * @return html string
	 */
	function _escapeSpecialChars( $html = "" )
	{
		// music notes
		$html = str_replace("\xE2\x99\xAB", "&#9835;", $html);
		// TODO: should have more...
		
		return $html;
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
	var $defaultHeaders = array();
	var $mimeBoundary = "---------------------------111111111111111111111111111";

	/**
	 * constructor
	 */
	function P2Mixi_TinyHttpClient ( $host = "", $port = 80 ) {
		$this->host = $host;
		$this->port = $port;

		# Init default http request headers.
		$this->defaultHeaders["Host"] = $this->host;
		$this->defaultHeaders["Accept"] = '*/*';
		$this->defaultHeaders["Connection"] = "Close";
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
				$headers = $this->defaultHeaders;
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
			$this->_parseCookies( $res );
		}
		// cut off the response headers
		$res = substr( $res, strpos( $res, "\r\n\r\n" ) + 4 );
		return $res;
	}

	/**
	 * Run POST http request.
	 */
	function post ( $url = "", $params = null, $isMime = false ) {
		$res = "";

		if ( $url == "" ) {
			error_log( 'P2Mixi_TinyHttpClient.post(): $url is empty.' );
		} elseif ( $params == null ) {
			error_log( 'P2Mixi_TinyHttpClient.post(): $params is empty.' );
		} else {
			$fp = fsockopen( $this->host, $this->port, $errno, $errstr, 30 );
			if ( !$fp ) {
				error_log( "P2Mixi_TinyHttpClient.post(): fsockopen failed: $errstr ( $errno )" );
			} else {

				$headers = $this->defaultHeaders;

				$postdata = null;

				if ( $isMime == false ) {
					$headers['Content-Type'] = 'application/x-www-form-urlencoded';
					$postdata = $this->_constructPostData( $params );
				} else {
					$headers['Content-Type'] = 'multipart/form-data; boundary=' . $this->mimeBoundary;
					$postdata = $this->_constructMimeData( $params );
				}
				$headers['Content-Length'] = strlen( $postdata );

				if ( count( $this->cookies ) > 0 ) {
					$headers['Cookie'] = $this->_constructCookieString();
				}
				 
				$out = "POST $url HTTP/1.1\r\n";
				$out .= $this->_constructHeaderString( $headers );
				$out .= "\r\n";
				$out .= $postdata;
				//        $out .= "\r\n";
				fwrite( $fp, $out );
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient.post():  $out " );

				while ( !feof( $fp ) ) {
					$res .=  fgets( $fp, 128 );
				}
				fclose( $fp );
			}
		}
		$this->_parseCookies( $res );
		// cut off the response headers
		$res = substr( $res, strpos( $res, "\r\n\r\n" ) + 4 );
		return $res;
	}
	/**
	 * Parse the cookies from the http response
	 */
	function _parseCookies ( $res ) {
		if ( $res == '' ) return;

		// cut off the response body
		$res = substr( $res, 0, strpos( $res, "\r\n\r\n" ) );
		if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseCookies(): Response header: ".$res );

		$lines = explode( "\r\n", $res );
		if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseCookies(): count(lines) ".count( $lines ) );
		$index = -1;
		foreach ( $lines as $line ) {
			if ( preg_match ( "/^Set-Cookie/i", $line ) ) {
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseCookies(): cookie found :  $line " );
				$params = explode( ";", $line );
				$cookie = explode ( ":", $params[0] );
				$cookie = explode ( "=", $cookie[1] );
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseCookies(): $cookie[0] = $cookie[1]" );
				$this->cookies[trim( $cookie[0] )] = trim( $cookie[1] );
			} else {
				if ( $this->debug ) error_log( "P2Mixi_TinyHttpClient._parseCookies(): cookie not found in this line" );
			}
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
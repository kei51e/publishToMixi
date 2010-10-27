<?php
/*
 Plugin Name: publishToMixi
 Plugin URI: http://ksnn.com/diary/?page_id=2437
 Description: WordPressへの投稿をmixiにも同時に投稿するためのプラグインです。
 Author: Kei Saito
 Version: 3.0.2.1
 Author URI: http://ksnn.com/
 Contributors: ento
 */

/*
 * A wordpress plugin to publish the post to Mixi
 * Copyright (C) 2008,2009,2010 Kei Saito (http://ksnn.com/)
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
$p2mixi_debug = false;

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
	add_meta_box( 'myplugin_sectionid', __( 'mixi投稿設定', 'p2mixi_textdomain' ), 
		'p2mixi_render_option_content', 'post', 'advanced' );
//	add_options_page( __( 'mixi autopost settings', 'p2mixi_textdomain' ), __( 'Mixi Autoposting', 'p2mixi_textdomain' ), 8, __FILE__, 'p2mixi_render_admin_option_content' );
	add_options_page( __( 'mixi投稿設定', 'p2mixi_textdomain' ), __( 'mixi投稿設定', 'p2mixi_textdomain' ), 8, __FILE__, 'p2mixi_render_admin_option_content' );
}

function p2mixi_render_admin_option_content () {
	
	// HTTP connection testing. 
	// If this doesn't work, maybe the main functionality won't work
	// because maybe the PHP configuration on this server doesn't allow you
	// to open the connection to the different servers. 
  $request_headers['Accept'] = '*/*';
  $request_headers["Connection"] = "Keep-Alive";
  $response_headers = array();
  $response_body = '';
  $connectable = p2mixi_http_get( 'http://mixi.jp/', $request_headers, $response_headers, $response_body );
	
	?>
	<div class="wrap">
		<h2><?php echo __( 'mixi投稿設定', 'p2mixi_textdomain' ) ?></h2>

		<div style="border:1px dotted #999999;padding:2px">
		<?php 
			if ( $connectable == true ) 
				echo __( 'publishToMixiは動作しています。', 'p2mixi_textdomain' );
			else 
				echo __( 'WordPressからmixiへのネットワークの接続に問題がありました。publishToMixiは動作しない可能性があります。', 'p2mixi_textdomain' );
		?> 
		</div>
		
		<form method="post" action="options.php">
			<?php settings_fields( 'p2mixi' ); ?>
			<h3><?php echo __( 'ログイン情報', 'p2mixi_textdomain' ) ?></h3>
			<p></p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php echo __( 'mixi 登録メールアドレス', 'p2mixi_textdomain' ) ?></th>
					<td><input type="text" name="p2mixi_username" value="<?php echo get_option( 'p2mixi_username' ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php echo __( 'mixi パスワード', 'p2mixi_textdomain' ) ?></th>
					<td><input type="password" name="p2mixi_password" value="<?php echo get_option( 'p2mixi_password' ); ?>" /></td>
				</tr>
				<tr valign="top">
				 <th scope="row"><?php echo __( 'mixi ID', 'p2mixi_textdomain' ) ?></th>
					<td><input type="text" name="p2mixi_id" value="<?php echo get_option( 'p2mixi_id' ); ?>" /></td>
				</tr>
			</table>
			<h3><?php echo __( 'デフォルトの投稿設定', 'p2mixi_textdomain' ) ?></h3>
			<p></p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php echo __( 'ヘッダー', 'p2mixi_textdomain' ) ?></th>
					<td><textarea name="p2mixi_header_default" cols="60" rows="4"><?php echo get_option( 'p2mixi_header_default' ); ?></textarea><br/>
					<?php echo __( '%%URL%% と書くと記事へのパーマリンクで置換されます', 'p2mixi_textdomain' ) ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php echo __( 'フッター', 'p2mixi_textdomain' ) ?></th>
					<td><textarea name="p2mixi_footer_default" cols="60" rows="4"><?php echo get_option( 'p2mixi_footer_default' ); ?></textarea><br/>
					<?php echo __( '%%URL%% と書くと記事へのパーマリンクで置換されます', 'p2mixi_textdomain' ) ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"></th>
					<td><label for="p2mixi_default">
					<input type="checkbox" name="p2mixi_default" id="p2mixi_default" <?php if ( get_option( 'p2mixi_default' ) == true ) { echo 'checked="checked"'; } ?> />
					<?php echo __( '「mixiに投稿する」チェックボックスをデフォルトでオンにする', 'p2mixi_textdomain' ) ?></label><br/>
					<?php echo __( ' (WordPress iPhoneアプリやリモート投稿経由でmixiに投稿したい場合もここを有効にします)', 'p2mixi_textdomain' ) ?>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
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
		<label for="p2mixi_publishbox"> <?php echo __("mixiに投稿する", 'p2mixi_textdomain' ) ?> </label>
	</div>

	<div style="margin-top:6px">
		<label for="p2mixi_headertext"> <?php echo __("ヘッダー:", 'p2mixi_textdomain' ) ?> </label>
	</div>
	<div>
		<textarea style="width:98%" name="p2mixi_headertext" id="p2mixi_headertext"><?php echo $p2mixi_header_default; ?></textarea>
	</div>
	<div style="margin-top:6px">
		<label for="p2mixi_footertext"> <?php echo __("フッター:", 'p2mixi_textdomain' ) ?> </label>
	</div>
	<div>
		<textarea style="width:98%" name="p2mixi_footertext" id="p2mixi_footertext"><?php echo $p2mixi_footer_default; ?></textarea>
	</div>
	
	<?php 
}

/**
 * Tells the post is coming from the wordpress admin page or not.
 * @param $post $_POST
 * @return boolean 
 */
function p2mixi_is_submitted_from_wp_admin ( $post ) {
	foreach ($post as $key => $value) {
		if ( $key == 'p2mixi_footertext' ) {
			return true;
		}
	}
	return false;
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

	// If the post was published from the wordpress admin page.
	if ( p2mixi_is_submitted_from_wp_admin( $_POST ) == true ) {
		// verify this came from the our screen and with proper authorization,
		// because publish_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['p2mixi_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}		
		if ( $_POST['p2mixi_publishcheckbox'] == null || $_POST['p2mixi_publishcheckbox'] == 'false' ) {
			return $postId;
		}
		$header = trim( $_POST['p2mixi_headertext'] );
		$footer = trim( $_POST['p2mixi_footertext'] );
				
	} else {
		// If the post was published not from the wordpress admin page 
		// such as iphone application, user doesn't see the publishToMixi option. 
		// If that's the case, publishToMixi just follows the default configuration.
		if ( $p2mixi_default == false ) {
			return $postId;
		}
		$header = $p2mixi_header_default;
		$footer = $p2mixi_footer_default;
	}
	
	// Get the post detail from wordpress.
	$post = get_post( $postId );
	if ( $post->post_status != 'publish' || $post->post_type != 'post' ) {
		return $postId;
	}
	// Extracting images from the post content.
	$images = p2mixi_extract_jpeg_images( $post->post_content );
	
	// Header
	if ( $header != '' ) {
		$header = str_replace( '%%URL%%', get_permalink( $postId ), $header );
		$header = p2mixi_sanitize_html ( $header . "\r\n\r\n" );		
	}

	// Body
	$body = $post->post_content;
	$movies = p2mixi_parse_movie_links( $body );
	$body = p2mixi_replace_hyperlinks( $body, array( $images['urls'][0] ) );
	$body = p2mixi_sanitize_html( $body );
	$body .= $movies;
	// Footer	
	if ( $footer != '' ) {
		$footer = str_replace( '%%URL%%', get_permalink( $postId ), $footer );
		$footer = p2mixi_sanitize_html( "\r\n\r\n" . $footer );
	}
	
	// Chop the body if it is too big.
	// The max length for the mixi diary body is 10k letters.
	// Actually it is 10k 'Japanese' letters (20k bytes) so it could be more 
	// if you use ASCII letters, but here just say 10k letters to make it safer.
	//
	// Not sure mb_ functions are available in any php env.
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
		$images = $args[5];
	}
	
	// WSSE Authentication
	$nonce = ""; 
	if ( function_exists( 'posix_getpid' ) ) {
		$nonce = pack( 'H*', sha1(md5(time().rand().posix_getpid())));
	} else {
		// Use uniqid() in case of windows.
		$nonce = pack( 'H*', sha1(md5(time().rand().uniqid())));
	}
	
	$created     = date( 'Y-m-d\TH:i:s\Z' );
	$digest      = base64_encode(pack( 'H*', sha1($nonce . $created . $password)));
	$wsse_text   = 'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"';
	$wsse_header = sprintf($wsse_text, $username, $digest, base64_encode($nonce), $created);
	
	// mixi POST URL
	$url = 'http://mixi.jp/atom/diary/member_id=' . $id;
	$request_headers = array();
	$request_headers['X-WSSE'] = $wsse_header;
	$request_headers['Accept'] = '*/*';
	$request_headers["Connection"] = "Close";
	
	//------------------------------------------------------------
	// Post Image
	//------------------------------------------------------------
	if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi: # of images : " . sizeof( $images ) );
	if ( sizeof( $images ) > 0 )
	{
		if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi: Uploading images to Mixi." );

		$response_headers = array();
		$response_body = '';
		$request_headers['Content-Type'] = 'image/jpeg';
		p2mixi_http_post( $url, $request_headers, $images[0], $response_headers, $response_body );

		$location = $response_headers['Location'];
		if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi: Finished uploading images to Mixi." );
		if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi: Location: $location" );
		
		if ( $location != '' )
		{
			$url = $location;
		}
	}
	
	//------------------------------------------------------------
	// Post Text
	//------------------------------------------------------------
	$request_body = "<?xml version='1.0' encoding='utf-8'?>"
	. "<entry xmlns='http://www.w3.org/2007/app'>"
	. "<title>$title</title>"
	. "<summary>$content</summary>"
	. "</entry>";
	$request_headers['Content-Type'] = 'application/atom+xml';

	if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi: Uploading text to Mixi." );
	p2mixi_http_post( $url, $request_headers, $request_body, $response_headers, $response_body );
	if ( $p2mixi_debug ) error_log ( "p2mixi_publish_to_mixi: Finished uploading text to Mixi." );	
}

// ----------------------------------------------------------------------------
// Register actions to wordpress.
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_init', 'p2mixi_admin_init' );
	add_action( 'admin_menu', 'p2mixi_render_option' );
//	add_action( 'publish_post', 'p2mixi_publish_handler' );
	add_action( 'draft_to_publish', 'p2mixi_publish_handler' );
	add_action( 'private_to_publish', 'p2mixi_publish_handler' );
	add_action( 'pending_to_publish', 'p2mixi_publish_handler' );
	add_action( 'future_to_publish', 'p2mixi_publish_handler' );
	add_action( 'new_to_publish', 'p2mixi_publish_handler' );
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

function p2mixi_parse_movie_links ( $text ) {
	$ret = '';
	//$ids = preg_grep( "/http:\/\/www\.youtube\.com\/watch\?v\=([0-9A-Za-z]*)/", array( $text ) );
	
	$ids = array();
	preg_match_all( "/href=\"http:\/\/www\.youtube\.com\/watch\?v=([0-9A-Za-z_]*)/", $text, $ids, PREG_PATTERN_ORDER );
	foreach( $ids[1] as $id ) {
		$ret .= '&lt;externalvideo src="YT:' . $id . '"&gt;';
	}
	
	$ids = array();
	preg_match_all( "/src=\"http:\/\/www\.youtube\.com\/v\/([0-9A-Za-z_]*)/", $text, $ids, PREG_PATTERN_ORDER );
	foreach( $ids[1] as $id ) {
		$ret .= '&lt;externalvideo src="YT:' . $id . '"&gt;';
	}
	
	$ids = array();
	preg_match_all( "/href=\"http:\/\/www\.nicovideo\.jp\/watch\/(sm[0-9]*)/", $text, $ids, PREG_PATTERN_ORDER );
	foreach( $ids[1] as $id ) {
		$ret .= '&lt;externalvideo src="NC:' . $id . '"&gt;';
	}
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
		
		$request_headers['Accept'] = '*/*';
		$request_headers["Connection"] = "Keep-Alive";
		$response_headers = array();
		$response_body = '';
		p2mixi_http_get( $url[1], $request_headers, $response_headers, $response_body );
		
		// Checking the data is really the jpeg data or not
		// by checking 'JFIF' string inside. 
		// http://en.wikipedia.org/wiki/JFIF
		//
		// Usually the string comes up in the 7th - 11th byte 
		// of the data, but it does not if the image contains exif data
		// because the exif headers comes before the JFIF appearance. 
		// Ideally, the logic should understand the exif structures.
		if ( strpos( $response_body, 'JFIF' ) != false ) {
			array_push( $images, $response_body );
			array_push( $urls, $url[1] );
			if ( ++$cnt == $max ) {	
				break;
			}
		}
	}
	return array( 'urls' => $urls, 'images' => $images );
}

function p2mixi_http_get ( $url, $request_headers, &$response_headers, &$response_body, $retries = 0 ) {
	global $p2mixi_debug;
	$url_comps = parse_url( $url );
	if ( ! isset( $url_comps['port'] ) ) $url_comps['port'] = 80;
	$sock = new p2mixi_TinyHttpSocket( $url_comps['host'], $url_comps['port'] );
	$sock->setDebugMode( $p2mixi_debug );
	if ( !$sock->connect() ) {
		error_log( "p2mixi_http_get: fsockopen failed: $errstr ( $errno )" );
		return false;
	} else {
		$request_headers["Host"] = $url_comps['host'];
		$sock->send( "GET", $url, $request_headers );
		$sock->recv( $response_headers, $response_body );
		if ( $p2mixi_debug ) error_log( "p2mixi_TinyHttpClient.get:  socket recv end " );
		if ( $response_headers["Status-Code"] == 403 && $p2mixi_debug ) error_log( "p2mixi_http_get:  Body: $response_body " );

		// Redirection support
		if ( isset( $response_headers["Status-Code"] ) ) {
			$code = $response_headers["Status-Code"];
			switch( $code ) {
			case ( (300 <= $code && $code <= 303) || $code == 307 ):
				if ( isset( $response_headers["Location"] ) ) {
					$location = $response_headers["Location"];
					if ( $p2mixi_debug ) error_log( "p2mixi_http_get: Redirecting($retries retries so far): $location" );
//					if ( isset( $response_headers['cookies'] ) ) {
//						if ( $p2mixi_debug ) error_log( "p2mixi_http_get: Setting cookies :". p2mixi_constrcut_cookies_string( $response_headers['cookies'] ) );
//						$request_headers['Cookie'] = p2mixi_construct_cookies_string( $response_headers['cookies'] );
//					}
					p2mixi_http_get( $location, $request_headers, $response_headers, $response_body, $retries + 1 );
				}
			break;
			}
		}
		return true;
	}
}

function p2mixi_http_post ( $url, $request_headers, $request_body, &$response_headers, &$response_body ) {
	global $p2mixi_debug;
	$url_comps = parse_url( $url );
	if ( ! isset( $url_comps['port'] ) ) $url_comps['port'] = 80;
	$sock = new p2mixi_TinyHttpSocket( $url_comps['host'], $url_comps['port'] );
	$sock->setDebugMode( $p2mixi_debug );
	if ( !$sock->connect() ) {
		error_log( "p2mixi_http_post: fsockopen failed: $errstr ( $errno )" );
		return false;
	} else {
		$request_headers["Host"] = $url_comps['host'];
		$request_headers['Content-Length'] = strlen( $request_body );
		$sock->send( "POST", $url, $request_headers, $request_body );
		$sock->recv( $response_headers, $response_body );
		if ( $p2mixi_debug ) error_log( "p2mixi_http_post:  socket recv end " );
		return true;
	}
}



function p2mixi_construct_cookies_string ( $cookies ) {
	$str = "";
	foreach ( $cookies as $name=>$value ) {
		$str .= "$name=$value;";
	}
	return $str;
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

	function constructHeaderString ( $headers ) {
		$str = "";
		foreach ( $headers as $name=>$value ) {
			$str .= "$name: $value\r\n";
		}
		return $str;
	}


//	function parseCookie ( $line ) {
//		// $line should look like following
//		// name=value; path=/; expires=Wednesday, 09-Nov-99 23:12:40 GMT
//		// Get the "Set-Cookie: name=value" part
//		$cookie = explode( ";", $line );
//		// Split name and value.
//		$cookie = explode ( "=", $cookie[0] );
//		if ( count( $cookie ) == 2 ) {
//			return array ( 'name' => trim( $cookie[0] ), 'value' => trim( $cookie[1] ) );
//		}
//		else {
//			return array ();
//		}
//	}
	
	function send ( $method, $url, $headers, $body='' ) {
		$out = "$method $url HTTP/1.1\r\n";
		$out .= $this->constructHeaderString( $headers );
		$out .= "\r\n";
		if ( $body != '' ) {
			$out .= $body;
		}
		$this->request = $out;
		if ( isset( $headers["Connection"] ) ) {
			$this->connection = strtolower( $headers["Connection"] );
		}
		if ( $this->fp ) {
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.send:  $out " );
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
		if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv: Status line: ".$header );

		if ( $header == "" ) return;

		$mime = '';
		$transfer = '';
		$connection = $this->connection;
//		$cookies = array();
		
		while ( $line = fgets( $this->fp, $this->getlen ) ) {
			if ( $line == "\r\n" ) { break; }
			$param = explode( ":", $line, 2 );
			$name = trim( $param[0] );
			$value = trim( $param[1] );
			$headers[$name] = $value;
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv: $name = $value" );

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
//				case 'Set-Cookie':
//					$cookie = $this->parseCookie( $value );
//					if ( count( $cookie ) > 0 ) {
//						$cookies[$cookie['name']] = $cookie['value'];
//					}
//					break;
			}
		}
//		if ( count( $cookies ) > 0 ) {
//			$headers['cookies'] = $cookies; 
//		}

		$body = '';

		if ( $connection == 'close' ) {
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv: looping for closed connection" );
			while ( !feof( $this->fp ) ) {
				$body .= fread( $this->fp, $this->getlen );
			}
			return ;
		}

		if ( isset( $length ) and strpos( $transfer, 'chunked' ) === false) {
			if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv: looping unchunked keep-alive connection for $length" );
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

		if ( $this->debug ) error_log( "p2mixi_TinyHttpSocket.recv: looping chunked keep-alive connection for $length" );
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


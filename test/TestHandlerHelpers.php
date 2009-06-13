<?php
require_once( '../publishToMixi.php' );

class TestHandlerHelpers extends Test {
	function doTest ( $testfunc, $input, $expected, $arg = NULL, $message = '' ) {
		$actual = $testfunc( $input, $arg );
//		echo ( $actual );
		Assert::equals( $actual, $expected, $message );
	}

	function testReplaceHyperlinksExtractsUrls () {
		$in = '<br/><a href="http://www.example.com" class="external">www.example.com</a>\n<hr/><a href="http://www.google.com/q?search=publishtomixi"/>search me</a>';
		$out = '<br/>www.example.com(http://www.example.com)\n<hr/>search me(http://www.google.com/q?search=publishtomixi)';
		$this->doTest( 'p2mixi_replace_hyperlinks', $in, $out );
	}

	function testReplaceHyperlinksAcceptsEsotericUrls () {
		$url = 'http://www.flickr.com/people/id@trail?key=value&sess=ABC_123';
		$in = '<br/><a href="' . $url . '" class="external">should appear</a>';
		$out = '<br/>should appear(' . $url . ')';
		$this->doTest( 'p2mixi_replace_hyperlinks', $in, $out );
	}

	function testReplaceHyperlinksAcceptsSingleQuotedAttributes () {
		$url = 'http://www.flickr.com/people/id@trail';
		$src = 'http://farm1.static.flickr.com/upper/id.jpg';
		$in = '<br/><a href="' . $url . '" ><img src=\'' . $src . '\'></a>';
		$out = '<br/>' . $src . '(' . $url . ')';
		$this->doTest( 'p2mixi_replace_hyperlinks', $in, $out );

	}

	function testReplaceHyperlinksShouldIgnoreExcludes () {
		$ignore = 'http://www.example.com';
		$in = '<br/><a href="http://www.example.com" class="external">www.example.com</a>\n<hr/><img src="http://www.example.com"/>foo';
		$out = '<br/>www.example.com\n<hr/>foo';
		$this->doTest( 'p2mixi_replace_hyperlinks', $in, $out, array( $ignore ) );
	}
	
	function testSanitizeYoutubeEmbed () {
		$in = 'abc<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/Krw7DJL69c8&hl=en&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/Krw7DJL69c8&hl=en&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>def';
		$out = 'abc def';
		$this->doTest( 'p2mixi_sanitize_html', $in, $out );
		
	}
	function testSanitizeAmazonAffiliateImageLink () {
		$in = 'abc<iframe src="http://rcm-jp.amazon.co.jp/e/cm?t=ksnn-22&o=9&p=8&l=as1&asins=4840233616&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>def';
		$out = "abc\n\ndef";
		$this->doTest( 'p2mixi_sanitize_html', $in, $out );
	}	
	function testSanitizeAmazonAffiliateTextLink () {
		$in = 'abc<a href="http://www.amazon.co.jp/gp/product/4840233616?ie=UTF8&tag=ksnn-22&linkCode=as2&camp=247&creative=1211&creativeASIN=4840233616">図書館戦争</a><img src="http://www.assoc-amazon.jp/e/ir?t=ksnn-22&l=as2&o=9&a=4840233616" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />def';
		$out = "abc図書館戦争def";
		$this->doTest( 'p2mixi_sanitize_html', $in, $out );
	}	
	
	function testParseYoutubeEmbed () {
//		$in = 'abc<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/Krw7DJL69c8&hl=en&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/Krw7DJL69c8&hl=en&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>def';
		$in = '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/FOFwN_6vAQc&hl=en&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/FOFwN_6vAQc&hl=en&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>';
		$out = '&lt;externalvideo src="YT:FOFwN_6vAQc"&gt;';
		$this->doTest( 'p2mixi_parse_movie_links', $in, $out );
	}
	function testParseYoutubeLink () {
		$in = 'abc<a href="http://www.youtube.com/watch?v=FOFwN_6vAQc">de</a>f';
		$out = '&lt;externalvideo src="YT:FOFwN_6vAQc"&gt;';
		$this->doTest( 'p2mixi_parse_movie_links', $in, $out );
	}
	function testParseNicovideoEmbed () {
		$in = 'abc<iframe width="312" height="176" src="http://ext.nicovideo.jp/thumb/sm137256" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="http://www.nicovideo.jp/watch/sm137256">【ニコニコ動画】一度は耳にしたことがある洋楽集</a></iframe>';
		$out = '&lt;externalvideo src="NC:sm137256"&gt;';
		$this->doTest( 'p2mixi_parse_movie_links', $in, $out );
		
	}
	function testParseNicovideoLink () {
		$in = 'abc<a target="_blank" href="http://www.nicovideo.jp/watch/sm137256">【ニコニコ動画】一度は耳にしたことがある洋楽集</a>';
		$out = '&lt;externalvideo src="NC:sm137256"&gt;';
		$this->doTest( 'p2mixi_parse_movie_links', $in, $out );
	}
	function testParseMovieLinkCombinations () {
		$in = '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/FOFwN_6vAQc&hl=en&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/FOFwN_6vAQc&hl=en&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>';
		$in .= 'abc<a href="http://www.youtube.com/watch?v=FOFwN_6vAQc">de</a>f';
		$in .= 'abc<iframe width="312" height="176" src="http://ext.nicovideo.jp/thumb/sm137256" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="http://www.nicovideo.jp/watch/sm137256">【ニコニコ動画】一度は耳にしたことがある洋楽集</a></iframe>';
		$in .= 'abc<a target="_blank" href="http://www.nicovideo.jp/watch/sm137256">【ニコニコ動画】一度は耳にしたことがある洋楽集</a>';
		$out = '&lt;externalvideo src="YT:FOFwN_6vAQc"&gt;';
		$out .= '&lt;externalvideo src="YT:FOFwN_6vAQc"&gt;';
		$out .= '&lt;externalvideo src="NC:sm137256"&gt;';
		$out .= '&lt;externalvideo src="NC:sm137256"&gt;';
		$this->doTest( 'p2mixi_parse_movie_links', $in, $out );
		
	}	
}
?>

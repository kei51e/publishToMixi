<?php
/*
 Plugin Name: Publish to Mixi
 Plugin URI: http://wordpress.org/#
 Description: Publish the post to Mixi.
 Author: Kei Saito
 Version: 1.0
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
$mixi_username = "youremail@gmail.com";
$mixi_password = "yourpassword";

// DO NOT EDIT BELOW THIS LINE
// ----------------------------------------------------------------------------


/**
 * Renders the option box in the "Write Post" page in the wordpress admin. 
 *
 */
function renderOption() {
  echo '<div class="postbox closed" id="test">';
  echo '<h3><a class="togbox">+</a> Publish to Mixi</h3>';
  echo '<div class="inside">';
  echo '<input type="checkbox" name="publishToMixi" id="publishToMixi" value="1" /> Publish to Mixi';
  echo '</div>';
  echo '</div>';
}

/**
 * Publishes the wordpress entry to mixi.
 *
 * @param number $postId
 * @return postId
 */
function publishHandler($postId)
{
  global $mixi_username, $mixi_password;
  
  if($_POST['publishToMixi'] != 1)
  {
    return $postId;
  }

  // Get the post detail from wordpress.
  $post = get_post($postId);
  if($post->post_status != 'publish')
  {
    return $postId;
  }
  
  // Entry title.
  $title = $post->post_title;
  // Entry content.
  // Take off all the html tags from the post since tags don't work in mixi post.
  $content = strip_tags($post->post_content);

//  $fh = fopen('/tmp/mixitest.txt', 'r+');
//  fwrite($fh, $content);
//  fclose($fh);

  // Convert the encoding from utf-8 to euc-jp.
  // Mixi is based on euc-jp encoding.
  $title = iconv("utf-8", "euc-jp", $title);
  $content = iconv("utf-8", "euc-jp", $content);

  // URL encode the title and content.
  $title = urlencode($title);
  $content = urlencode($content);

//  $fh = fopen('/tmp/mixitest2.txt', 'r+');
//  fwrite($fh, $content);
//  fclose($fh);
  
  // Create MixiConnector instance.
  $connector = new MixiConnector ($mixi_username, $mixi_password);
  // Publish the entry to mixi.
  $connector->publishDiary($title, $content); 

  return $postId;
  
}

// Register actions to wordpress.
add_action('dbx_post_advanced', 'renderOption');
add_action('publish_post', 'publishHandler');



/**
 * Mixi connector class
 *
 */
class MixiConnector
{
  var $debug = false;
  /**
   * constructor
   *
   * @param unknown_type $username
   * @param unknown_type $password
   * @return MixiConnector
   */
  function MixiConnector ($username = "", $password = "")
  {
    $this->username = $username;
    $this->password = $password;
  }
  /**
   * Posts the diary to mixi.
   *
   * @param unknown_type $title
   * @param unknown_type $content
   */
  function publishDiary($title = "", $content = "")
  {
    if ($title == "" || $content == "")
    {
      return;
    }

    // Instanciate http client
    $client = new TinyHttpClient("mixi.jp", 80);
    // Create login URL param
    $urlparam = "email=" . $this->username . "&password=" . $this->password . "&next_url=/home.pl&sticky=off";
    // Login to mixi.
    $response = $client->post("http://mixi.jp/login.pl", $urlparam);
    if ($debug) echo htmlspecialchars($response);
    // Access the check page after the login.
    $response = $client->get("http://mixi.jp/check.pl?n=%2Fhome.pl");
    if ($debug) echo htmlspecialchars($response);
    // Access the home page.
    $response = $client->get("http://mixi.jp/home.pl");
    if ($debug) echo htmlspecialchars($response);
    // Get the user id from the response.
    $userid = $this->_getId($response);
    // Post the diary to the mixi.
    $response = $client->post("http://mixi.jp/add_diary.pl", "diary_body=" .
    $content . "&diary_title=" .
    $title . "&id=" .
    $userid . "&tag_id=0&campaign_id=&invite_campaign=&news_title=&news_url=&movie_id=&movie_title=&movie_url=&submit=main");
    if ($debug)  echo htmlspecialchars($response);
    // Get the post key from the response.
    $postkey = $this->_getPostKey($response);
    // Access the post confirmation page.
    $response = $client->post("http://mixi.jp/add_diary.pl", "diary_body=" .
    $content . "&diary_title=" .
    $title . "&id=" .
    $userid . "&tag_id=0&campaign_id=&invite_campaign=&news_title=&news_url=&movie_id=&movie_title=&movie_url=&submit=confirm&post_key=" .
    $postkey);
    if ($debug) echo htmlspecialchars($response);
  }

  function _getId($string = "")
  {
    $id = "";
    if (preg_match ("/add_diary.pl\?id=([0-9]+)/", $string, $regs))
    {
      $id = $regs[1];
      if ($this->debug) echo "## _getId : ID found : $id \n";
    }
    return $id;
  }

  function _getPostKey($string = "")
  {
    $id = "";
    if (preg_match ("/name=\"post_key\" value=\"([0-9a-f]+)/", $string, $regs))
    {
      $id = $regs[1];
      if ($this->debug) echo "## _getPostKey : Post key found : $id \n";
    }
    return $id;
  }
}

/**
 * Http client class
 */
class TinyHttpClient
{
  var $cookies = array();
  var $host = "";
  var $port = 80;
  var $debug = false;

  /**
   * constructor
   */
  function TinyHttpClient($host = "", $port = 80)
  {
    $this->host = $host;
    $this->port = $port;
  }

  /**
   * Run GET http request.
   */
  function get ($url = "")
  {
    $res = "";
    if ($url != "")
    {
      $fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
      if (!$fp)
      {
        $res = "$errstr ($errno)";
      }
      else
      {
        $out = "";
        $out = "GET $url HTTP/1.1\r\n";
        $out .= "Host: $this->host\r\n";
        $out .= "Accept: */*\r\n";

        if (count($this->cookies) > 0)
        {
          $out .= "Cookie: ";
          foreach ($this->cookies as $name=>$value)
          {
            $out .= "$name=$value;";
          }
          $out .= "\r\n";
        }
        $out .= "Connection: Close\r\n\r\n";
        fwrite($fp, $out);
        if ($this->debug) echo "## $out \n";

        while (!feof($fp)) 
        {
          $res .=  fgets($fp, 128);
        }
        fclose($fp);
      }
    }
    $this->_parseCookies($res);
    return $res;
  }

  /**
   * Run POST http request.
   */
  function post ($url = "", $param = "")
  {
    $res = "";
    if ($url != "")
    {
      $fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
      if (!$fp)
      {
        $res = "$errstr ($errno)";
      }
      else
      {
        $out = "";
        $out = "POST $url HTTP/1.1\r\n";
        $out .= "Host: $this->host\r\n";
        $out .= "Accept: */*\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";

        if (count($this->cookies) > 0)
        {
          $out .= "Cookie: ";
          foreach ($this->cookies as $name=>$value)
          {
            $out .= "$name=$value;";
          }
          $out .= "\r\n";
        }
        $out .= "Content-Length: ";
        $out .= strlen($param);
        $out .= "\r\n\r\n";
        $out .= $param;
        $out .= "\r\n";
        fwrite($fp, $out);
        if ($this->debug) echo "## $out \n";

        while (!feof($fp))
        {
          $res .=  fgets($fp, 128);
        }
        fclose($fp);
      }
    }
    $this->_parseCookies($res);
    return $res;
  }


  /**
   * Parse the cookies from the http response
   */
  function _parseCookies($res)
  {
    $lines = explode("\r\n", $res);
    $index = -1;
    foreach ($lines as $line)
    {
      if (preg_match ("/^Set-Cookie/i", $line))
      {
        if ($this->debug) echo "## _parseCookies() : cookie found :  $line \n";
        $params = explode(";", $line);
        $cookie = explode (":", $params[0]);
        $cookie = explode ("=", $cookie[1]);
        if ($this->debug) echo "## _parseCookies() : $cookie[0] = $cookie[1] \n";
        $this->cookies[trim($cookie[0])] = trim($cookie[1]);
      }
      else
      {
        if ($this->debug) echo "## _parseCookies() : cookie not found in this line\n";
      }
    }
    if ($this->debug)  print_r($this->cookies);
  }
}



?>
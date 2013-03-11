/*-------------------------------------------------------
 * crazy shit going on up here. i did not write this code
 --------------------------------------------------------*/
<?php
	function get_redirect_url($url){
		$redirect_url = null; 

		$url_parts = @parse_url($url);
		if (!$url_parts) return false;
		if (!isset($url_parts['host'])) return false; //can't process relative URLs
		if (!isset($url_parts['path'])) $url_parts['path'] = '/';

		$sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
		if (!$sock) return false;

		$request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n"; 
		$request .= 'Host: ' . $url_parts['host'] . "\r\n"; 
		$request .= "Connection: Close\r\n\r\n"; 
		fwrite($sock, $request);
		$response = '';
		while(!feof($sock)) $response .= fread($sock, 8192);
		fclose($sock);

		if (preg_match('/^Location: (.+?)$/m', $response, $matches)){
			if ( substr($matches[1], 0, 1) == "/" )
				return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[1]);
			else
				return trim($matches[1]);

		} else {
			return false;
		}

	} 

	function get_all_redirects($url){
		$redirects = array();
		while ($newurl = get_redirect_url($url)){
			if (in_array($newurl, $redirects)){
				break;
			}
			$redirects[] = $newurl;
			$url = $newurl;
		}
		return $redirects;
	}

	function get_final_url($url){
		$redirects = get_all_redirects($url);
		if (count($redirects)>0){
			return array_pop($redirects);
		} else {
			return $url;
		}
	}

?>
/*------------------
 * end of crazy shit
 -------------------*/

<?php 
	$current_page = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	$comment_url = get_final_url("http://www.reddit.com/".$current_page); //get_final_url traces reddit redirects: if posted to reddit returns url to comments, if not then returns reddit.com/s/$current_page
	$json_url = $comment_url.".json";
	$download=json_decode(file_get_contents($json_url)); //json_decode(wp_get_remote($json_url)) supposedly proper for WordPress, seesms to break it though.
	//$comment_url = ("http://www.reddit.com/r/csuf/comments/18x6tu/mark_your_calendars_first_meetup_of_the_semester/"); //testdata
	//$download = json_decode(file_get_contents('http://www.reddit.com/r/csuf/comments/18x6tu/mark_your_calendars_first_meetup_of_the_semester/.json')); //testdata
	if ($download != null){
		?><ul><?php
		foreach ($download as $comments){
			foreach ($comments->data->children as $comment){
				if($comment->data->body != null){
					?><li><a href="http://www.reddit.com/user/<?php echo($comment->data->author)?>" target="_blank">
						<?php echo html_entity_decode($comment->data->author); ?></a><br /><?php
					echo html_entity_decode($comment->data->body); ?></li><br /><br /><ul><?php
					foreach($comment->data->replies->data->children as $reply){
						?><li><a href="http://www.reddit.com/user/<?php echo($reply->data->author)?>" target="_blank">
							<?php echo html_entity_decode($reply->data->author); ?></a><br /><?php
						echo html_entity_decode($reply->data->body_html); ?></li><br /><?php
					} ?></ul><?php
				}
			} 
		}
		?></ul><?php
	}else{
		?><p>No comments yet! Click <a href="'.$comment_url.'" target="_blank">here</a> to add a comment.</p><?php
}?>
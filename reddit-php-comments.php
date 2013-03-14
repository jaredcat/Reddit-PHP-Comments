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
/*Begin: THANKS JAY */
		function time_ago_in_words($since) {
			$chunks = array(
				array(60 * 60 * 24 * 365 , 'year'),
				array(60 * 60 * 24 * 30 , 'month'),
				array(60 * 60 * 24 * 7, 'week'),
				array(60 * 60 * 24 , 'day'),
				array(60 * 60 , 'hour'),
				array(60 , 'minute'),
				array(1 , 'second')
			);
			for ($i = 0, $j = count($chunks); $i < $j; $i++) {
				$seconds = $chunks[$i][0];
				$name = $chunks[$i][1];
				if (($count = floor((time()-$since) / $seconds)) != 0) {
					break;
				}
			}
			$print = ($count == 1) ? '1 '.$name.' ago': "$count {$name}s ago";
			return $print;
		 }
		function getreplies($comment) {
			if($comment->data->body != null) {
				echo $spaces . '<p class="tagline"><a href="http://www.reddit.com/user/' . $comment->data->author . '" target="_blank" class="author">';
				echo html_entity_decode($comment->data->author) .'</a>';
				if($comment->data->author_flair_text != null){
					echo '  <span class="flair" title="' . $comment->data->author_flair_text . '">' .  $comment->data->author_flair_text . '</span>';
				};
				$count = $comment->data->ups - $comment->data->downs;
				$score = ($count == 1) ? '1 point' : "$count points";
				echo '<span class="score">' . $score . '</span>';
				$utc = $comment->data->created_utc;
				$utc_str = gmdate("M d Y H:i:s", $utc);
				echo '  <time title"=' . $utc_str . '">' . time_ago_in_words($utc) . '</time></p>';
				echo "\n" . '<div class="usertext-body">' . "\n" . html_entity_decode($comment->data->body_html) . "\n" . '</div>' . "\n";
				if ($comment->data->replies != null) {
					echo '<div class="child">' . "\n";
					foreach($comment->data->replies->data->children as $reply){
						getreplies($reply);
					}
					echo '</div>' . "\n";
				}
			}
		}
/*End: THANKS JAY */			
	?>
	<?php
	if ('open' == $post->comment_status) {
		//$download = null;
		//$current_page = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		//$comment_url = get_final_url("http://www.reddit.com/".$current_page);
		//if ($comment_url != "http://www.reddit.com/s/http://".$current_page) { //check is has been submitted to reddit based on redirection provided by visting 	reddit.com/{$current_page}
			//$json_url = $comment_url.".json";
			//$download = json_decode(wp_remote_retrieve_body(wp_remote_get($json_url)));
			$comment_url = ("http://www.reddit.com/r/csuf/comments/18x6tu/mark_your_calendars_first_meetup_of_the_semester/"); //testdata
			$download = json_decode(wp_remote_retrieve_body(wp_remote_get('http://www.reddit.com/r/csuf/comments/18x6tu/mark_your_calendars_first_meetup_of_the_semester/.json'))); //testdata
			if($download[1]->data->children[0]->data != null){ ?>
				<base target="_blank">
				<strong><a href="<?php echo $comment_url; ?>" target="_blank">Click Here</a> to add a comment.</strong><br /><br /><br />
				<?php foreach ($download as $comments){
					foreach ($comments->data->children as $comment){
						getreplies($comment);
					} 
				} ?>
				<br /><br /><strong><a href="<?php echo $comment_url; ?>" target="_blank">Click Here</a> to add a comment.</strong>
			<?php }else{ //error message if page has no comments ?>
				<div id="comment-section" class="nocomments">
					<p>there doesn't seem to be anything <a href="<?php echo $comment_url; ?>" target="_blank">here</a></p>
				</div>
			<?php } 
		//}else{ 
			 //Code here for error messages if page hasn't been submitted to reddit
		//}
	} ?>
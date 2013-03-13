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
		function getreplies($comment) {
			if($comment->data->body != null) {
				echo $spaces . '<p class="tagline"><a href="http://www.reddit.com/user/' . $comment->data->author . '" target="_blank">';
				echo html_entity_decode($comment->data->author) .'</a>';
				if($comment->data->author_flair_text != null){
					echo '  <span class="flair" title="' . $comment->data->author_flair_text . '">' .  $comment->data->author_flair_text . '</span>';
				};
				$utc_str = gmdate("M d Y H:i:s", $comment->data->created_utc);
				echo '  <time title"=' . $utc_str . '">' . $utc_str . '</time></p>';
				echo '<br />' . "\n" . '<div class="usertext-body">' . "\n" . '<div class="md">' . "\n" . '<p>' . html_entity_decode($comment->data->body) . '</p>' . "\n" . '</div>' . "\n" . '</div>' . "\n" . '<br />' . "\n";
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
/*------------------
 * end of crazy shit
 -------------------*/

	<?php
	if ('open' == $post->comment_status) {
		$download = null;
		$current_page = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		$comment_url = get_final_url("http://www.reddit.com/".$current_page);
		if ($comment_url != "http://www.reddit.com/s/http://".$current_page) { //check is has been submitted to reddit based on redirection provided by visting 	reddit.com/{$current_page}
			$json_url = $comment_url.".json";
			$download = json_decode(wp_remote_retrieve_body(wp_remote_get($json_url)));
			//$comment_url = ("http://www.reddit.com/r/csuf/comments/18x6tu/mark_your_calendars_first_meetup_of_the_semester/"); //testdata
			//$download = json_decode(wp_remote_retrieve_body(wp_remote_get('http://www.reddit.com/r/csuf/comments/18x6tu/mark_your_calendars_first_meetup_of_the_semester/.json'))); //testdata
			if($download[1]->data->children[0]->data != null){ ?>
				<strong><a href="<?php echo $comment_url; ?>" target="_blank">Click Here</a> to add a comment.</strong><br /><br /><br />
				<ul>
				<?php foreach ($download as $comments){
					foreach ($comments->data->children as $comment){
						getreplies($comment);
					} 
				} ?>
				</ul>
				<br /><br /><strong><a href="<?php echo $comment_url; ?>" target="_blank">Click Here</a> to add a comment.</strong>
			<?php }else{ //error message if page has no comments ?>
				<div id="comment-section" class="nocomments">
					<p>there doesn't seem to be anything <a href="<?php echo $comment_url; ?>" target="_blank">here</a></p>
				</div>
			<?php } 
		}else{ 
			 //Code here for error messages if page hasn't been submitted to reddit
		}
	} ?>
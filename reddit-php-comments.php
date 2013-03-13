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
		function getreplies($comment, $indent = 0) {
			if($comment->data->body != null) {
				$spaces = str_repeat(" ",$indent);
				echo $spaces . '<li><a href="http://www.reddit.com/user/' . $comment->data->author . '" target="_blank">';
				echo html_entity_decode($comment->data->author) .'</a>';
				echo '<br />' . html_entity_decode($comment->data->body) . '</li><br />' . "\n";
				if ($comment->data->replies != null) {
					echo str_repeat(" ",$indent) . '<ul style="padding-left: 25px !important;">' . "\n";
					$indent += 1;
						foreach($comment->data->replies->data->children as $reply){
						getreplies($reply, $indent);
					}
					echo str_repeat(" ",$indent - 1) . '</ul>' . "\n";
				}
			}
		}
//*End: THANKS JAY */			
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
			if($download != null){ ?>
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
<?php

/*
	phpFileDownload by vjeux, November 2010
	<vjeuxx@gmail.com> http://blog.vjeux.com/

	file_get_contents replacement for spider scripts handling:
		- Cookies
		- POST Data
		- Location Redirect
*/

/*
	// Example: Execute a logged search on a forum

	$fd = new FileDownload();

	// Login Phase: 
	// It logs in by following the many redirects 
	// and by settings all the required cookies

	$fd->get('http://some-forum.com/login.php?do=login', // URL
			 'vb_login_username=vjeux&vb_login_password=!@#$%^&*'); // POST Data

	// Optional:
	// You can view / edit the cookies using the variable $fd->cookies

	print_r($fd->cookies);

	// Spider Phase:
	// You are now logged in, get any page you need

	$page = $fd->get('http://some-forum.com/search.php?do=finduser&userid=12345');
*/

class FileDownload {
	public $cookies = array();

	public function get($url, $content = NULL) {  
			$r = $this->get_redirect($url, $content);
			return $r[1];
	}



	/* Handle Location redirect */
	public function get_redirect($url, $content = NULL) {
			$r = $this->get_basic($url, $content);
			preg_match('`Location: ([^\r]+)`', $r[0], $location);
			if (count($location) > 0) {
					$r = $this->get_redirect($location[1]);
			}
			return $r;
	}

	/* Get a file from the url with the post content. It handles cookies */
	function get_basic($url, $content = NULL) {

		/* Open the socket */

		$url_parsed = parse_url($url);

		if (!isset($url_parsed['port'])) {
			if ($url_parsed['scheme'] == 'http') {
				$url_parsed['port'] = 80;
			}
			else if ($url_parsed['scheme'] == 'https') {
				$url_parsed['port'] = 443;
			}
		}

		while (!($fd = fsockopen($url_parsed['host'], $url_parsed['port']))) {
			// Retry
		}

		/* Generate the Query */

		$query = '';
		if (isset($url_parsed['query'])) {
			$query = $url_parsed['query'];
		}

		if (isset($content)) {
			$method = 'POST';
		} else {
			$method = 'GET';
		}

		if (isset($query) && strlen($query) > 0) {
			$header  = $method." ".$url_parsed['path'].'?'.$query." HTTP/1.1\r\n";
		} else {
			$header  = $method." ".$url_parsed['path']." HTTP/1.1\r\n";
		}
		$header .= "Host: ".$url_parsed['host']."\r\n";
		$header .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; fr; rv:1.9.0.6) Gecko/2009011913 Firefox/3.0.6\r\n";
		$header .= "Referer: ".$url_parsed['scheme'].'://'.$url_parsed['host'].$url_parsed['path']."\r\n";

		$cookie = $this->dumpCookies();
		if (isset($cookie)) {
			$header .= "Cookie: ".$cookie."\r\n";
		}

		if (isset($content)) {
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: ".strlen($content)."\r\n";
		}

		$header .= "Connection: close\r\n\r\n";

		if (isset($content)) {
			$header .= $content;
		}

		fputs($fd, $header);

		/* Handle the results */

		$content = '';
		$header = '';
		$is_content = false;

		while ($line = fgets($fd)) {
			if ($is_content) {
				$content .= $line;
			} else {
				$header .= $line;
			}

			if (strlen($line) <= 2) {
				$is_content = true;
			}
		}

		$this->handleCookies($header);
		fclose($fd);
		return array($header, $content);
	}
	
	/* Read the HTTP Header Response and Sets the cookies accordingly */
	private function handleCookies($header) {
		preg_match_all('`Set-Cookie: ([^=]+)=([^;]+);`', $header, $out);
		foreach ($out[1] as $key => $val) {
			if ($out[2][$key] == 'delete') {
					unset($this->cookies[$out[1][$key]]);
			} else {
					$this->cookies[$out[1][$key]] = $out[2][$key];
			}
		}
	}
	
	/* Convert the cookie array into a key=val; format */
	private function dumpCookies() {
		if (count($this->cookies) == 0) {
			return NULL;
		}
	   
		$c = '';
		foreach($this->cookies as $key => $val) {
			$c .= $key.'='.$val.'; ';
		}
		return substr($c, 0, strlen($c) - 2);
	}

}

?>
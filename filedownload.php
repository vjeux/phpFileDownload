<?php

function file_get_contents2($url, &$cookies, $content = NULL){
        $cookie = dumpCookies($cookies);
        $url_parsed = parse_url($url);
       
    if (!isset($url_parsed['port'])) {
      if ($url_parsed['scheme'] == 'http')
                  $url_parsed['port'] = 80;
      elseif ($url_parsed['scheme'] == 'https')
                  $url_parsed['port'] = 443;
    }
 
        while (!($fd = fsockopen($url_parsed['host'], $url_parsed['port']))) { echo 'Retry ...<br />'; fl(); }
        $query = '';
        if (isset($url_parsed['query']))
                $query = $url_parsed['query'];
 
        if (isset($content))
                $method = 'POST';
        else
                $method = 'GET';
 
       
        if (isset($query) && strlen($query) > 0)
                $header  = $method." ".$url_parsed['path'].'?'.$query." HTTP/1.1\r\n";
        else
                $header  = $method." ".$url_parsed['path']." HTTP/1.1\r\n";
        $header .= "Host: ".$url_parsed['host']."\r\n";
        $header .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; fr; rv:1.9.0.6) Gecko/2009011913 Firefox/3.0.6\r\n";
        $header .= "Referer: ".$url_parsed['scheme'].'://'.$url_parsed['host'].$url_parsed['path']."\r\n";
 
 
        if (isset($cookie))
                $header .= "Cookie: ".$cookie."\r\n";
 
        if (isset($content)) {
                $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $header .= "Content-Length: ".strlen($content)."\r\n";
        }
       
        $header .= "Connection: close\r\n\r\n";
       
        if (isset($content))
                $header .= $content;
       
        fputs($fd, $header);
       
        $content = '';
        $header = '';
        $is_content = false;
       
        while ($line = fgets($fd)) {
                if ($is_content)
                        $content .= $line;
                else
                        $header .= $line;
 
                if(strlen($line) <= 2)
                        $is_content = true;
        }
       
        fclose($fd);
        handleCookies($cookies, $header);
        return array($header, $content);
}
 
function handleCookies(&$cookies, $header) {
        preg_match_all('`Set-Cookie: ([^=]+)=([^;]+);`', $header, $out);
        foreach ($out[1] as $key => $val) {
                if ($out[2][$key] == 'delete') {
                        unset($cookies[$out[1][$key]]);
                } else {
                        $cookies[$out[1][$key]] = $out[2][$key];
                }
        }
}
 
function dumpCookies($cookies) {
        if (count($cookies) == 0)
                return NULL;
       
        $c = '';
        foreach($cookies as $key => $val) {
                $c .= $key.'='.$val.'; ';
        }
        return substr($c, 0, strlen($c) - 2);
}
 
 
/* Handle Location redirect */
function file_get_contents3($url, &$cookies, $content = NULL) {
        $r = file_get_contents2($url, $cookies, $content);
        preg_match('`Location: ([^\r]+)`', $r[0], $location);
        if (count($location) > 0) {
                $r = file_get_contents3($location[1], $cookies);
        }
        return $r;
}
 
function download($url, &$cookies, $content = NULL) {  
        $r = file_get_contents3($url, $cookies, $content);
        return $r[1];
}

?>
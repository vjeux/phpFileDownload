[phpFileDownload](http://blog.vjeux.com/) - file_get_content replacement for spiders
================================

file_get_contents works well for static page but when working with spider scripts that gather data from real websites, it doesn't meet all the requirements.

phpFileDownload has been made to do:
	1 Automatic Cookies Management
	2 Easy to do POST Data
	3 Handling Location Redirect

### Example

A common task your spider want to execute is to login on a forum and access private pages there. Doing this is only achieved in two functions call.

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

<?php
// For use with the PHP built-in web server (for testing or purposes only!)
// Just run: cd jira-log/htdocs; php -S localhost:8000 router.php
$dir = dirname(__FILE__);
session_save_path($dir.DIRECTORY_SEPARATOR.'session');
$dir = realpath(implode(DIRECTORY_SEPARATOR, [$dir, '..', 'htdocs']));
if (is_file($dir.$_SERVER['REQUEST_URI'])) {
	return false;
}
else {
	require($dir.DIRECTORY_SEPARATOR.'dispatcher.php');
}
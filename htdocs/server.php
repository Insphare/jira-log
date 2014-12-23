<?php

/**
 * About this script
 *
 * This script sets up a simple cli-server. This is ONLY for testing purposes /
 * personal use. No warranty is provided. There were no special precautions met
 * in regards to security. Under no circumstances should you use this in a live
 * environment. No support will be provided.
 *
 * To run the server, just execute this script with PHP.
 * The server can be accessed through localhost:8000.
 */

$dir = __DIR__;
$file = __FILE__;

// Start the server
if (PHP_SAPI === 'cli') {
	$descs = array (
		0 => array ('file', 'php://stdin', 'r'),
		1 => array ('file', 'php://stdout', 'w'),
		2 => array ('file', 'php://stderr', 'w')
	);
	$proc = proc_open ("php -S localhost:8000 -t $dir $file", $descs, $fp);
	exit(is_resource($proc) ? proc_close($proc) : 1);
}

// Do the routing magic
if (is_file($dir.$_SERVER['REQUEST_URI'])) {
	return false;
}
else {
	require($dir.DIRECTORY_SEPARATOR.'dispatcher.php');
}

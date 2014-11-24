<?php
session_start();
ini_set('display_errors', 1);

define('DS', DIRECTORY_SEPARATOR);
define('BASR_DIR', dirname(dirname(__FILE__)) . DS);

// quick and dirty autoloader
spl_autoload_register(function ($class) {
	$libFilePath =  BASR_DIR . 'lib/' . $class . '.php';

	if (file_exists($libFilePath)) {
		require_once $libFilePath;
	}
});

$sessionUsername = null;
$sessionPassword = null;
if (isset($_SESSION['username'])) {
	$sessionUsername = $_SESSION['username'];
}

if (isset($_SESSION['password'])) {
	$sessionPassword = $_SESSION['password'];
}

$user = isset($_POST['username']) ? $_POST['username'] : $sessionUsername;
$pass = !empty($_POST['password']) ? $_POST['password'] : $sessionPassword;

Config::set(Config::KEY_BASE_DIR, BASR_DIR);
Config::set(Config::KEY_USERNAME, $user);
Config::set(Config::KEY_PASSWORD, $pass);
Config::set(Config::KEY_JIRA_HOST, file_get_contents(dirname(dirname(__FILE__)).DS.'host'));

$jira = new Jira();
$template = new Template();

try {
	$json = $jira->getMyProfile();
	$template->assign('data', $json);
	$template->assign('user', $template->fetch('profile.tpl'));
	$template->assign('userHide', '');
}
catch (Exception $e) {
	$template->assign('userHide', 'hide');
}

$template->assignByArray(array(
	'host' => Config::get(Config::KEY_JIRA_HOST)
));

$html = '';
$requestUri = $_SERVER['REQUEST_URI'];

if (!empty($_SERVER['HTTP_X_IS_AJAX_CALL']) && $_SERVER['HTTP_X_IS_AJAX_CALL'] === 'yes') {
	$json = array();

	$storeVariables = array(
		'username',
		'password',
		'sheet'
	);

	foreach ($storeVariables as $variableName) {
		if (!empty($_POST[$variableName])) {
			$_SESSION[$variableName] = $_POST[$variableName];
		}
	}

	try {
		$successMessage = '';
		$container = '#ajaxContent';
		switch ($requestUri) {
			case '/check':
				$json = $jira->testConnection();
				$successMessage = 'Verbindung konnte hergestellt werden.';
				if (isset($json['serverTitle']) && isset($json['version'])) {
					$successMessage .= ' ' . $json['serverTitle'] . ' Version ' . $json['version'];
				}
				break;

			case '/me':
				$container = '.user';
				$json = $jira->getMyProfile();
				$html = $template->assign('data', $json)->fetch('profile.tpl');

				break;

			case '/timetrack':
				$json = $jira->getTimeTrackTask();
				if (isset($json['issues'][0]['key'])) {
					$_SESSION['timetrack'] = $json['issues'][0]['key'];
					$successMessage = 'Timetrack gefunden. ('.$json['issues'][0]['key'].': '.$json['issues'][0]['fields']['summary'].')';
				}

				break;

			case '/preview':
				$plainText = $_SESSION['sheet'];
				// class to dispatch in a loop
				$html = $template->assign('data', array())->fetch('preview.tpl');
				break;
		}

		$json['success'] = $successMessage;
		$json['container'] = $container;
		$json['html'] = $html;
	}
	catch(Exception $e) {
		$json = array('error' => $e->getMessage());
	}

	$html = json_encode($json);
}
else {
	$html = $template->fetch('index.tpl');
	$template->assign('content', $html);
	$html = $template->fetch('body.tpl');
}

echo $html;

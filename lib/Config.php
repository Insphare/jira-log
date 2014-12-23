<?php

/**
 * Class Config
 *
 * @author Manuel Will <insphare@gmail.com>
 */
abstract class Config {

	/**
	 *
	 */
	const KEY_BASE_DIR = 'base-dir';
	const KEY_JIRA_HOST = 'jira-host';
	const KEY_USERNAME= 'jira-user';
	const KEY_PASSWORD = 'jira-pass';
	const KEY_REPLACEMENTS = 'replacements';
	const KEY_PARSERS = 'parsers';
	const KEY_PROJECTS = 'projects';
	const SUBKEY_PROJECTS_TIMELOGGING_ALLOWED = 'projects-timelogging-allowed';
	const SUBKEY_PROJECTS_TIMETRACKING_TASK_SEARCH = 'timetracking-task-search';

	/**
	 * @var array
	 */
	private static $config = array();

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value) {
		self::$config[(string)$key] = $value;
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 *
	 * @param string $key
	 *
	 * @return null
	 */
	public static function get($key) {
		// Lazy load
		if (!array_key_exists($key, self::$config)) {
			self::$config[$key] = null;
			$filename = dirname(__DIR__) . "/config/$key.json";
			if(is_readable($filename)) {
				self::$config[$key] = json_decode(file_get_contents($filename), true);
			}
		}
		$config = self::$config;
		foreach (func_get_args() as $key) {
			if (!isset($config[$key])) {
				$config = null;
				break;
			}
			$config = $config[$key];
		}

		return $config;
	}

}

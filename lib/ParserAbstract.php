<?php

/**
 * Class ParserAbstract
 */
abstract class ParserAbstract {

	/**
	 * @var string
	 */
	protected $textSheet = '';

	/**
	 * @var mixed
	 */
	protected $format;

	/**
	 * @var string
	 */
	private $alternateIssue = '';

	/**
	 * @var array
	 */
	private static $taskObjects = array();

	/**
	 * @var TaskHtml[]
	 */
	private $tasks = [];

	/**
	 * Finds out if the parser understands format of the given data
	 *
	 * @param $sheet
	 *
	 * @return mixed
	 */
	public static function canParse($sheet) {
		return false;
	}

	/**
	 * @return mixed
	 */
	abstract protected function parse();

	/**
	 * @param string $sheet
	 * @param string $alternateIssue
	 * @param mixed $format
	 */
	public function __construct($sheet, $alternateIssue, $format) {
		$this->textSheet = (string)trim($sheet);
		$this->alternateIssue = (string)$alternateIssue;
		$this->format = $format;
	}

	/**
	 * @param string $issue
	 * @return mixed
	 */
	private function getTaskObject($issue) {
		if (!isset(self::$taskObjects[$issue])) {
			self::$taskObjects[$issue] = new Task($issue);
		}

		return self::$taskObjects[$issue];
	}

	/**
	 * @return string
	 */
	protected function getTextSheet() {
		return $this->textSheet;
	}

	/**
	 * @param string $task
	 * @param string $time
	 * @param string $comment
	 * @param null $start
	 */
	protected function addTask($task, $time, $comment, $start = null) {
		$projects = implode('|', (array) Config::get(
			Config::KEY_PROJECTS,
			Config::SUBKEY_PROJECTS_TIMELOGGING_ALLOWED
		));
		$referenceTask = null;
		if (!preg_match("~^($projects)-\\d+$~", $task)) {
			list($referenceTask, $task) = [$task, $this->alternateIssue];
		}
		$this->formatComment($comment, $referenceTask);
		$taskObject = $this->getTaskObject($task);
		$taskHtml = new TaskHtml($taskObject);
		$taskHtml->setComment($comment)->setTime($time)->setStart($start);
		$this->tasks[] = $taskHtml;
	}

	/**
	 * @return TaskHtml[]
	 */
	public function getTaskHtmlObjects() {
		$this->parse();
		return $this->tasks;
	}

	/**
	 * Overwrite method. Called in ParserAbstract::addTask
	 *
	 * @param string $comment
	 * @param null $task
	 */
	protected function formatComment(&$comment, $task = null) {
		$searchReplacePattern = (array)Config::get(Config::KEY_REPLACEMENTS);
		$comment = preg_replace(
			array_keys($searchReplacePattern),
			array_values($searchReplacePattern),
			$comment
		);

		if (!empty($task)) {
			$this->formatComment($task);

			if (empty(trim($comment))) {
				$comment = "$task";
			}
			else {
				$comment = "$task:\n$comment";
			}
		}
	}
}

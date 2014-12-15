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
	 * @var array
	 */
	private static $taskObjects = array();

	/**
	 * @var TaskHtml[]
	 */
	private $tasks;

	/**
	 * @return mixed
	 */
	abstract protected function parse();

	/**
	 * @param string $sheet
	 * @param string $alternateIssue
	 */
	public function __construct($sheet, $alternateIssue) {
		$this->textSheet = (string)trim($sheet);
		$this->alternateIssue = (string)$alternateIssue;
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
	 */
	protected function addTask($task, $time, $comment, $start = null) {
		//@todo configurable
		if (!preg_match('~^VS-\d+~', $task)) {
			$comment = $task . ', ' . $comment;
			$task = $this->alternateIssue;
		}
		$this->formatComment($comment);
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
	 */
	protected function formatComment(&$comment) {}
}

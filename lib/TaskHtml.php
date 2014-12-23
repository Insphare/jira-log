<?php

/**
 * Class TaskHtml
 */
class TaskHtml {

	/**
	 * @var Task
	 */
	private $task;

	/**
	 * @var string
	 */
	private $comment = '';

	/**
	 * @var string
	 */
	private $time = '';

	/**
	 * @var DateTime
	 */
	private $start;

	/**
	 * @param Task $task
	 */
	public function __construct(Task $task) {
		$this->task = $task;
		$this->start = new DateTime();
	}

	/**
	 * @param string $time
	 * @return $this
	 */
	public function setTime($time) {
		$this->time = (string)$time;
		return $this;
	}

	/**
	 * @param string $comment
	 * @return $this
	 */
	public function setComment($comment) {
		$this->comment = (string)$comment;
		return $this;
	}

	/**
	 * @param DateTime|string $start
	 * @return $this
	 */
	public function setStart($start) {
		if (!$start instanceof DateTime) {
			$start = new DateTime((string)$start);
		}
		$this->start = $start;
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$time = $this->time;
		$time = preg_replace('~([^0-9,])~', '', $time);
		$time = explode(',', $time);
		if (!isset($time[1])) {
			$time[1] = 0;
		}

		$t = new Template();
		$t->assignByArray(array(
			'issue' => $this->task->getIssue(),
			'summary' => $this->task->getSummary(),
			'timeHour' => $time[0],
			'timeMin' => round(($time[1] * 60) / 100),
			'comment' => $this->comment,
			'startDay' => $this->start->format('d.m.Y'),
			'startHour' => $this->start->format('G'),
			'startMin' => (int)$this->start->format('i'),
		));

		return $t->fetch('taskHtml.tpl');
	}
}

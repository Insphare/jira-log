<?php

/**
 * Class ParserCompact
 */
class ParserCompact extends ParserAbstract {

	/**
	 * Finds out if the parser understands format of the given data
	 *
	 * @todo Analyze the sheet and find out if it can be parsed (or remove this parser)
	 *
	 * @param string $sheet
	 * @return mixed
	 */
	public static function canParse($sheet) {
		return true;
	}

	/**
	 * parse the sheet
	 */
	protected function parse() {
		foreach (explode(PHP_EOL, $this->getTextSheet()) as $stringTaskLine) {
			$stringTaskLine = trim($stringTaskLine, '|');
			$data = explode('|', $stringTaskLine);
			foreach ($data as &$row) {
				$row = trim($row);
			}

			list($task, $time, $comment) = $data;

			$comment = trim($comment);
			if ($comment === '-') {
				$comment = '';
			}

			$this->addTask($task, $time, $comment);
		}
	}

	/**
	 * Overwrite method. Called in ParserAbstract::addTask
	 *
	 * @param string $comment
	 */
	protected function formatComment(&$comment, $task = null) {
		if (isset($task)) {
			$comment = $task . ', ' . $comment;
		}
		$comment = trim($comment);
		$parts = explode(',', $comment);
		foreach ($parts as $key => &$piece) {
			$piece = ucfirst(parent::formatComment(trim($piece)));

			if (empty($piece)) {
				unset($parts[$key]);
				continue;
			}

			$piece = '- ' . $piece;
		}

		$comment = implode(PHP_EOL, $parts);
	}
}

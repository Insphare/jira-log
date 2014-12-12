<?php

/**
 * Class ParserCompact
 */
class ParserCompact extends ParserAbstract {

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
	protected function formatComment(&$comment) {
		$searchReplacePattern = array(
			'^tl$' => '',
			'^ae$' => 'Erfassung der Arbeitszeiten',
			'^sm$' => 'Sprint-Meeting',
			'^rm$' => 'Refactoring Meeting',
			'^su$' => 'Standup',
			'^ik$' => 'Interne Kommunikation',
			'^ao$' => 'Allgemeines (und Organisatorisches)',
			'^tf$' => 'Team Frühstück',
			'^ms$' => 'Team Massage',
			'^wf$' => 'Weihnachtsfeier',
			'kommunikation' => 'Kommunikation',
			'(^|\s+)task(\s+|$)' => '$1Task$2',
			'(^|\s+)vorbereitung(\s+|$)' => '$1Vorbereitung$2',
		);

		$comment = trim($comment);
		$parts = explode(',', $comment);
		foreach ($parts as $key => &$piece) {
			$piece = trim($piece);
			foreach($searchReplacePattern as $regEx => $replace) {
				$piece = trim(preg_replace('~' . $regEx . '~i', $replace, $piece));
			}
			$piece = ucfirst($piece);

			if (empty($piece)) {
				unset($parts[$key]);
				continue;
			}

			$piece = '- ' . $piece;
		}

		$comment = implode(PHP_EOL, $parts);
	}
}

<?php

/**
 * Class ParserGeneric
 */
class ParserGeneric extends ParserAbstract {

	/**
	 * @var DataFormat
	 */
	private $format;

	/**
	 * @param string $sheet
	 * @param string $alternateIssue
	 * @param DataFormat $format
	 */
	public function __construct($sheet, $alternateIssue, DataFormat $format) {
		parent::__construct($sheet, $alternateIssue);
		$this->format = $format;
	}

	/**
	 * parse the sheet
	 */
	protected function parse() {
		$this->format->each($this->getTextSheet(), function($row) {
			$this->addTask($row['tasknumber'], $row['duration'], $row['description'], $row['starttime']);
		});
	}
}

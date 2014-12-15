<?php

/**
 * Class TaskParserFactory
 */
class TaskParserFactory {

	/**
	 * @param string $sheet
	 * @param string $alternateIssue
	 * @return TaskHtml[]
	 */
	public static function getTaskHtmlObjects($sheet, $alternateIssue) {

		// possibility to implement other parser
		$format = DataFormat::guess($sheet);
		if (isset($format)) {
			$parser = new ParserGeneric($sheet, $alternateIssue, $format);
		}
		else {
			$parser = new ParserCompact($sheet, $alternateIssue);
		}

		return $parser->getTaskHtmlObjects();
	}
}

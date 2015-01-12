<?php

/**
 * Class TaskParserFactory
 */
class TaskParserFactory {

	/**
	 * @param string $sheet
	 * @param string $alternateIssue
	 *
	 * @throws ParseException
	 * @return TaskHtml[]
	 */
	public static function getTaskHtmlObjects($sheet, $alternateIssue) {

		foreach ((array)Config::get(Config::KEY_PARSERS) as $classname) {
			if (!class_exists($classname)) continue;
			$format = $classname::canParse($sheet);
			if ($format) {
				$parser = new $classname($sheet, $alternateIssue, $format);
				break;
			}
		}

		if (!isset($parser)) {
			throw new ParseException('None of the configured parsers could understand the data format.');
		}
		return $parser->getTaskHtmlObjects();
	}
}

<?php

if (!function_exists('stats_standard_deviation')) {
	/**
	 * This user-land implementation follows the implementation quite strictly;
	 * it does not attempt to improve the code or algorithm in any way. It will
	 * raise a warning if you have fewer than 2 values in your array, just like
	 * the extension does (although as an E_USER_WARNING, not E_WARNING).
	 *
	 * From: http://php.net/manual/en/function.stats-standard-deviation.php
	 *
	 * @param array $a
	 * @param bool $sample [optional] Defaults to false
	 * @return float|bool The standard deviation or false on error.
	 */
	function stats_standard_deviation(array $a, $sample = false) {
		$n = count($a);
		if ($n === 0) {
			trigger_error("The array has zero elements", E_USER_WARNING);
			return false;
		}
		if ($sample && $n === 1) {
			trigger_error("The array has only 1 element", E_USER_WARNING);
			return false;
		}
		$mean = array_sum($a) / $n;
		$carry = 0.0;
		foreach ($a as $val) {
			$d = ((double) $val) - $mean;
			$carry += $d * $d;
		};
		if ($sample) {
			--$n;
		}
		return sqrt($carry / $n);
	}
}

/**
 * Class DataFormat
 */
class DataFormat {
	const COL_SEPARATOR_CHARS = ";,|\t";
	const ROW_SEPARATOR_MATCH = '~\r?\n|\r~';
	
	private static $typeGuessing = array(
		'duration' => '~^(?:(?:(\d+)h)?\s*(?:(\d+)m)?|\d+(?:[.,]\d+)?h?)$~',
		'tasknumber' => '~^[A-Z]{1,4}-?\d{1,5}$~i',
		'starttime' => null,
		'description' => null,
	);

	private $rowSeparator, $colSeparator, $starttimeCol, $durationCol,
		$tasknumberCol, $descriptionCol;

	/**
	 * Guesses line separator, field separator, and column IDs for column types
	 * specified above. If not possible to guess, null is returned.
	 *
	 * @param string $data
	 * @return self|null
	 */
	public static function guess($data) {
		preg_match_all(self::ROW_SEPARATOR_MATCH, $data, $results);
		if (empty($results[0])) return null;
		$rowSeps = array_count_values($results[0]);
		arsort($rowSeps);
		reset($rowSeps);
		$format = new self();
		$format->rowSeparator = key($rowSeps);
		$rows = $format->explode($data);
		$sepRegex = '~['.self::COL_SEPARATOR_CHARS.']~';
		$rows = array_filter($rows, function($row) use($sepRegex) {
			return count(preg_split($sepRegex, current($row))) > 1;
		});
		if (count($rows) <= 1) return null;
		$colSeps = [];
		foreach (str_split(self::COL_SEPARATOR_CHARS) as $sepChar) {
			$colCounts = [];
			foreach ($rows as $row) {
				$colCounts[] = count(explode($sepChar, current($row)));
			}
			$colSeps[$sepChar] = array_sum($colCounts) / count($colCounts);
			$colSeps[$sepChar] -= stats_standard_deviation($colCounts);
		}
		arsort($colSeps);
		reset($colSeps);
		$format->colSeparator = key($colSeps);
		$rows = $format->explode($data);
		$colCount = max(array_map('count', $rows));
		$types = array_keys(self::$typeGuessing);
		$default = array_combine($types, array_fill(0, count($types), 0));
		$colTypes = array_combine(range(0, $colCount - 1), array_fill(0, $colCount, $default));
		foreach ($rows as $row) {
			foreach ($row as $colNo => $col) {
				$guess = self::guessType($col);
				if (isset($guess)) $colTypes[$colNo][$guess]++;
			}
		}
		$typeCols = [];
		foreach ($colTypes as $colNo => $types) {
			arsort($types);
			reset($types);
			$type = key($types);
			if (!isset($typeCols[$type])) $typeCols[$type] = $colNo;
		}
		foreach ($typeCols as $type => $colNo) {
			$format->{"{$type}Col"} = $colNo;
		}
		if (isset($format->durationCol)) return $format;
	}
	
	/**
	 * Guesses the type of a single value according to the specification defined
	 * above.
	 *
	 * @param string $col
	 * @return string|null
	 */
	private static function guessType($col) {
		foreach (self::$typeGuessing as $type => $expr) {
			if (is_callable([get_class(), "guess$type"])) {
				if (self::{"guess$type"}($col)) return $type;
			} elseif (preg_match($expr, $col)) return $type;
		}
		return null;
	}
	
	/**
	 * Guess if current column holds a start time.
	 *
	 * @param string $col
	 * @return bool
	 */
	private static function guessStarttime($col) {
		return strtotime($col) !== false;
	}
	
	/**
	 * Guess if current column holds a description.
	 *
	 * @param string $col
	 * @return bool
	 */
	private static function guessDescription($col) {
		return strlen($col) > 3;
	}
	
	/**
	 * Splits the given data into rows and columns according to current settings.
	 *
	 * @param string $data
	 * @return string[][]
	 */
	private function explode($data) {
		$rows = isset($this->rowSeparator) ? explode($this->rowSeparator, $data) : [$data];
		foreach ($rows as &$row) {
			$row = isset($this->colSeparator) ? explode($this->colSeparator, $row) : [$row];
			$row = array_map('trim', $row);
		}
		return $rows;
	}
	
	/**
	 * Calls the given callable for each row of the given data.
	 *
	 * @param string $data
	 * @param callable $callback
	 */
	public function each($data, callable $callback) {
		$rows = $this->explode($data);
		$types = array_keys(self::$typeGuessing);
		$defaults = array_combine($types, array_fill(0, count($types), null));
		foreach ($rows as $row) {
			$args = $defaults;
			foreach ($row as $colNo => $col) {
				foreach (array_keys(self::$typeGuessing) as $type) {
					if ($colNo === $this->{"{$type}Col"}) {
						$args[$type] = $col;
						break;
					}
				}
			}
			// Convert xh ym into x,zzh
			preg_match(self::$typeGuessing['duration'], (string) $args['duration'], $match);
			if (isset($match[1])) {
				$args['duration'] = number_format($match[1] + $match[2] / 60, 2, ',', '');
			}
			$callback($args);
		}
	}
}
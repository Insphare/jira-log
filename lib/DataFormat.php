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
		'duration' => '~^(?:(?:(\d+)h\s*)?(\d+)m|(\d+(?:[.,]\d+)?)\s*h?)$~',
		'tasknumber' => '~^([A-Z]{1,4})-?\d{1,5}$~i',
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
		$format = new self();
		if (!self::guessSeparators($format, $data)) return null;
		if (!self::guessColumnTypes($format, $data)) return null;
		return $format;
	}

	/**
	 * Guesses line and field separators. Returns success.
	 *
	 * @param self $format
	 * @param string $data
	 */
	private static function guessSeparators(self $format, $data) {
		preg_match_all(self::ROW_SEPARATOR_MATCH, $data, $results);
		if (empty($results[0])) return false;
		$rowSeps = array_count_values($results[0]);
		arsort($rowSeps);
		reset($rowSeps);
		$format->rowSeparator = key($rowSeps);
		$rows = $format->explode($data);
		$sepRegex = '~['.self::COL_SEPARATOR_CHARS.']~';
		$rows = array_filter($rows, function($row) use($sepRegex) {
			return count(preg_split($sepRegex, current($row))) > 1;
		});
		if (count($rows) <= 1) return false;
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
		return true;
	}


	/**
	 * Guesses column numbers for types specified at the tom. Returns success.
	 *
	 * @param self $format
	 * @param string $data
	 */
	private static function guessColumnTypes(self $format, $data) {
		$rows = $format->explode($data);
		array_unshift($rows, null);
    $cols = call_user_func_array('array_map', $rows);
    $colTypes = [];
    $ratings = [];
    $cols = array_map('array_filter', $cols);
    foreach ($cols as $colNo => $col) {
    	$col = array_filter($col);
    	if (empty($col)) continue;
    	foreach (self::guessType($col) as $type => $rating) {
    		$colTypes[] = [$colNo, $type];
    		$ratings[] = $rating;
    	}
    }
    array_multisort($ratings, SORT_DESC, $colTypes);
		$typeCols = [];
		foreach ($colTypes as $entry) {
			list($colNo, $type) = $entry;
			if (!isset($typeCols[$type]) && !in_array($colNo, $typeCols)) $typeCols[$type] = $colNo;
		}
		foreach ($typeCols as $type => $colNo) {
			$format->{"{$type}Col"} = $colNo;
		}
		return isset($format->durationCol);
	}

	/**
	 * Guesses the type of a single value according to the specification defined
	 * above.
	 *
	 * @param array $col
	 * @return array
	 */
	private static function guessType($col) {
		$types = [];
		$alreadyGuessed = false;
		foreach (self::$typeGuessing as $type => $expr) {
			$modifier = 1;
			$sum = 0;
			foreach ($col as $value) {
				if (!isset($expr)) $expr = $alreadyGuessed;
				$sum += self::{"guess$type"}($value, $modifier, $expr);
			}
			$rating = $modifier * $sum / count($col);
			if ($rating > 0.25) {
				$types[$type] = $rating;
				$alreadyGuessed = true;
			}
		}
		return $types;
	}

	/**
	 * Guess if current value is a duration.
	 *
	 * @param string $value
	 * @param float &$modifier
	 * @param string $expr
	 * @return bool
	 */
	private static function guessDuration($value, &$modifier, $expr) {
		$isDuration = preg_match($expr, $value);
		// Shrink modifier for every duration failed to recognize
		if (!$isDuration) $modifier *= 0.5;
		return (int) $isDuration;
	}

	/**
	 * Guess if current value is a duration.
	 *
	 * @param string $value
	 * @param float &$modifier
	 * @param string $expr
	 * @return bool
	 */
	private static function guessTasknumber($value, &$modifier, $expr) {
		$rating = 0.75;
		$timelogProjects = Config::get(
			Config::KEY_PROJECTS,
			Config::SUBKEY_PROJECTS_TIMELOGGING_ALLOWED
		);
		if (!preg_match($expr, $value, $matches)) {
			$rating = 0.25;
		} elseif (in_array($matches[1], $timelogProjects)) {
			$rating = 1;
		}
		return $rating;
	}

	/**
	 * Guess if current value is a start time.
	 *
	 * @param string $value
	 * @param float &$modifier
	 * @return bool
	 */
	private static function guessStarttime($value, &$modifier) {
		$isDatetime = strtotime($value) !== false;
		// If one entry of the column isn't a date, this can't be the date column.
		if (!$isDatetime) $modifier = 0;
		return (int) $isDatetime;
	}

	/**
	 * Guess if current value is a description.
	 *
	 * @param string $value
	 * @param float &$modifier
	 * @param bool $guessed
	 * @return bool
	 */
	private static function guessDescription($value, &$modifier, $guessed) {
		return (strlen($value) > 3) && !$guessed;
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
			// Convert xh ym or x,zzh into number of minutes
			if (preg_match(self::$typeGuessing['duration'], (string) $args['duration'], $match)) {
				if (!empty($match[2])) {
					$args['duration'] = 60 * $match[1] + $match[2];
				}
				else {
					$args['duration'] = 60 * (float) str_replace(',', '.', $match[3]);
				}
			}
			$callback($args);
		}
	}
}
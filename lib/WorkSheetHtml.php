<?php

class WorkSheetHtml {

	private $data;

	public function __construct($data) {
		$this->data = $data;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$data = current($this->data);
		$kw = current(array_keys($this->data));
		$t = new Template();
		$t->assignByArray(array(
			'KW' => $kw,
		));

		$map =[
			'Mon' => 1,
			'Tue' => 2,
			'Wed' => 3,
			'Thu' => 4,
			'Fri' => 5,
		];

		$tasks = [];
		$columns=[];
		if (empty($data)) {
			return '';
		}

		foreach ($data as $weekDay => $row) {
			foreach ($row['task'] as $taskNumber => $seconds) {
				if (!isset($tasks[$taskNumber][$weekDay])) {
					$tasks[$taskNumber][$weekDay] = 0.0;
				}
				$tasks[$taskNumber][$weekDay] += (float)$seconds;
				$columns[$taskNumber] = array_fill(1, 7, 0.0);
				array_unshift($columns[$taskNumber], $taskNumber);
			}
		}

		foreach ($tasks as $taskNumber => $dataRows) {
			foreach ($dataRows as $weekDay => $seconds) {
				$columns[$taskNumber][$map[ucfirst(strtolower($weekDay))]] = $this->formatSeconds($seconds);
			}
		}

		$html = '';
		foreach ($columns as $row) {
			list($task, $mon, $di, $mi, $do, $fr) = $row;
			$html .= sprintf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				$task, 'TODO', $mon, $di, $mi, $do, $fr, 'TODO'
			);
		}

		$sum = array_fill(1, 7, 0.0);
		array_unshift($sum, '');
		foreach ($data as $weekDay => $row) {
			$sum[$map[ucfirst(strtolower($weekDay))]] = $this->formatSeconds($row['sum']);
		}

		list($nix, $mon, $di, $mi, $do, $fr) = $sum;
		$html .= sprintf('<tr style="font-weight:bold;"><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $nix, 'TODO', $mon, $di, $mi, $do, $fr, 'TODO');

		$fullTable = $t->assign('rows', $html)->fetch('sheet.tpl');
		return $fullTable;
	}

	private function formatSeconds($seconds) {
		return number_format((float)(($seconds/60)/60), 2, ',', '.');
	}
}

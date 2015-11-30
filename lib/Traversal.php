<?php

class Traversal {

	/**
	 * @var array|object
	 */
	private $data;

	public static function traverse($data, $path) {
		$objInstance = new self($data);
		return $objInstance->walk($path);
	}

	public function __construct($data) {
		$this->data = $data;
	}

	public function walk($path) {

		if (is_scalar($path)) {
			$path = explode('.', $path);
		}
		$mxdData = $this->data;
		try {
			foreach ($path as $strComponent) {
				$intIndex = null;
				if (preg_match('~^(.*)\[(\d+)\]$~', $strComponent, $arrMatches)) {
					$strComponent = $arrMatches[1];
					$intIndex = (int)$arrMatches[2];
				}
				if (is_array($mxdData)) {
					$mxdData = isset($mxdData[$strComponent]) ? $mxdData[$strComponent] : null;
				}
				elseif (is_object($mxdData)) {
					$strComponent = 'get' . ucfirst($strComponent);
					$mxdData = method_exists($mxdData, $strComponent) ? $mxdData->$strComponent() : null;
				}
				else {
					// Fehler im Pfad
					$mxdData = null;
					break;
				}
				if (isset($intIndex) && (is_array($mxdData) || $mxdData instanceof \ArrayAccess)) {
					$mxdData = isset($mxdData[$intIndex]) ? $mxdData[$intIndex] : null;
				}
			}
		}
		catch (Exception $ex) {
			$mxdData = null;
		}

		return $mxdData;
	}
}

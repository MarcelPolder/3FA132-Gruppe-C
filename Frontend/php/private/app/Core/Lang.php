<?php
namespace Webapp\Core;

class Lang {

	protected static $data;

	public static function load($langCode) {
		$langFilePath = LANGDIR.DS.strtolower($langCode).'.php';
		if(!is_file($langFilePath)) throw new Error("Die benötigte Sprachdatei konnte nicht geladen werden: ".$langFilePath, 503);

		self::$data = require $langFilePath;
	}

	public static function get($key, $defaultValue = '') {
		return self::$data[$key] ?? $defaultValue;
	}

}
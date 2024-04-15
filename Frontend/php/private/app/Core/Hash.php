<?php
namespace Webapp\Core;

class Hash {

	public static function make($string) {
		return password_hash($string, PASSWORD_DEFAULT, [
			'cost' => 12
		]);
	}

	public static function verify($string, $hash) {
		return password_verify($string, $hash);
	}

	public static function unique($onlyString = false, $stringLength = 8) {
		return !$onlyString ? self::make(uniqid()) : str_shuffle(bin2hex(random_bytes(($stringLength / 2))));
	}
	
}
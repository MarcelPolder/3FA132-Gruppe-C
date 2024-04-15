<?php
namespace Webapp\Core;

class Token {
	private static $tokenName = "token";

	public static function generate($name = null) {
		$name = ($name ? $name : self::$tokenName);
		return (object) [
			'name' => $name,
			'value' => Session::set($name, md5(uniqid())),
		];
	}

	public static function check($tokenName = null, $token = "") {
		$tokenName = ($tokenName ? $tokenName : self::$tokenName);
		
		if(Session::exists($tokenName) && $token==Session::get($tokenName)) {
			Session::delete($tokenName);
			return true;
		}
		return false;
	}
}
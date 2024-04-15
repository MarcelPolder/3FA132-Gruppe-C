<?php
namespace Webapp\Core;

class Session {

	protected static 	$flashMessage,
						$flashType;

	public static function setFlash($message, $type = 'info') {
		self::$flashMessage = $message;
		self::$flashType = $type;
	}

	public static function hasFlash() {
		return !is_null(self::$flashMessage);
	}

	public static function flash($echo = false) {
		$return = self::$flashMessage;
		self::$flashMessage = null;
		if(!$echo) return $return;
		else echo $return;
	}

	public static function flashType($echo = false) {
		$return = self::$flashType;
		self::$flashType = null;
		if(!$echo) return $return;
		else echo $return;
	}

	public static function exists($key) {
		return (isset($_SESSION[$key]) ? true : false);
	}

	public static function get($key) {
		return $_SESSION[$key] ?? null;
	}

	public static function set($key, $value) {
		return $_SESSION[$key] = $value;
	}

	public static function add($key, $value) {
		if(isset($_SESSION[$key])) {
			if(is_array($_SESSION[$key])) {
				return $_SESSION[$key] = array_merge($_SESSION[$key], $value);
			} else {
				if(is_array($value)) {
					return $_SESSION[$key] = [$_SESSION[$key], ...$value];
				} else {
					return $_SESSION[$key] = [$_SESSION[$key], $value];
				}
			}
		}
		return $_SESSION[$key] = $value;
	}

	public static function delete($key) {
		if(isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}

	public static function destroy() {
		session_destroy();
	}

	public static function existsCookie($key) {
		return (isset($_COOKIE[$key]) ? true : false);
	}

	public static function getCookie($key) {
		return (self::existsCookie($key) ? $_COOKIE[$key] : null);
	}

	public static function setCookie($key, $value, $expiry = (60*60*24*30), $samesite = "Lax") {
		if(setcookie($key, $value, [
			'expires' => time()+$expiry,
			'path' => '/',
			'domain' => '',
			'secure' => (Config::get('forceHttps') || isset($_SERVER['HTTPS']) ? true : false),
			'httponly' => true,
			'samesite' => $samesite,
		])) {
			$_COOKIE[$key] = $value;
			return true;
		}
		return false;
	}

	public static function deleteCookie($key) {
		if(self::existsCookie($key)) {
			self::setCookie($key, '', time() - 1);
		}
	}

}
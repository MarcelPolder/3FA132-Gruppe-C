<?php
namespace Webapp\Lib;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use UnexpectedValueException;
use Webapp\Core\Config;
use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Core\Session;

class HVUser extends User {
	
	public function loginCheck() {
		$cookie = Session::getCookie('jwt');
		if (empty($cookie)) return false;
		try {
			$decoded = (array) JWT::decode($cookie, new Key(Config::get('jwt.key'), "HS256"));
			if (!empty($decoded['user']) && !empty($decoded['token']) && HVApi::isAvailable()) {
				$dbUser = HVApi::getUser($decoded['user']);
				if ($dbUser['token'] == $decoded['token']) return true;
				else {
					Router::redirect("/auth/logout");
					return false;
				}
				return true;
			}
			return false;
		} catch (LogicException $e) {
			return false;
		} catch (UnexpectedValueException $e) {
			return false;
		}
	}
}
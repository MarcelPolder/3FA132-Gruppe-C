<?php
namespace Webapp\Lib;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use UnexpectedValueException;
use Webapp\Core\Config;
use Webapp\Core\Request;
use Webapp\Core\Session;

class HVUser extends User {
	
	public function loginCheck() {
		$cookie = Session::getCookie('jwt');
		if (empty($cookie)) return false;
		try {
			$decoded = (array) JWT::decode($cookie, new Key(Config::get('jwt.key'), "HS256"));
			if (!empty($decoded['user'])) {
				return true;
			}
			return false;
		} catch (LogicException $e) {
			return false;
		} catch (UnexpectedValueException $e) {
			return false;
		}
	}

	public function login() {
		
		$post = Request::getInstance()->getPost();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/users/authenticate");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'username' => $post['username'],
			'password' => $post['password'],
		]));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: Application/json'
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($response, true);
		if (!empty($response['user'])) {
			$jwtHeader = [
				'alg' => "HS256",
				'typ' => "JWT",
			];
			$jwtPayload = [
				'iss' => "Hausverwaltung",
				'exp' => strtotime("now + 1 Month"),
				'sub' => 'Webapp',
				'user' => $response['user']['id']
			];
			$jwt = JWT::encode($jwtPayload, Config::get('jwt.key'), 'HS256', null, $jwtHeader);
			Session::setCookie('jwt', $jwt);
			return true;
		}
		return false;
	}
}
<?php
namespace Webapp\Controllers;

use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Lib\Captcha;

/** @property \Webapp\Models\Ajax $model */

class AjaxController extends \Webapp\Core\Controller {

	function __construct($data = []) {
		parent::__construct($data);

		if(!Request::getInstance()->isAjax()) Router::redirect('/');

		header('Content-Type: application/json');
		header('Accept-Ranges: bytes');
	}

	public function captcha() {
		header('Content-Type: text/plain');
		$captcha = new Captcha();
		$captchaImage = $captcha->regenerate();
		header("Content-length: ".strlen($captchaImage));
		echo $captchaImage;
		exit;
	}

	// public function passwordReset() {
	// 	$response = $this->user->passwordReset();
	// 	$response = json_encode($response);
	// 	header("Content-length: ".mb_strlen($response));
	// 	echo $response;
	// 	exit;
	// }

	// public function passwordResetMail() {
	// 	$response = $this->user->passwordResetMail();
	// 	$response = json_encode($response);
	// 	header("Content-length: ".mb_strlen($response));
	// 	echo $response;
	// 	exit;
	// }

	// public function login() {
	// 	$response = $this->user->login();
	// 	$response = json_encode($response);
	// 	header("Content-length: ".mb_strlen($response));
	// 	echo $response;
	// 	exit;
	// }

	// public function logout() {
	// 	$response = $this->user->logout();
	// 	$response = json_encode($response);
	// 	header("Content-length: ".mb_strlen($response));
	// 	echo $response;
	// 	exit;
	// }

	// public function register() {
	// 	$response = $this->user->create();
	// 	$response = json_encode($response);
	// 	header("Content-length: ".mb_strlen($response));
	// 	echo $response;
	// 	exit;
	// }

	// public function checkUsername() {
	// 	$post = Request::getInstance()->getPost();
	// 	$username = $post['username'] ?? '';
	// 	$response = true;
	// 	if(strlen($username)>=8 && strlen($username)<=25) {
	// 		$user = $this->user->find('username', $username);
	// 		if(empty($user['id'])) $response = false;
	// 	}
	// 	$response = json_encode($response);
	// 	header("Content-length: ".mb_strlen($response));
	// 	echo $response;
	// 	exit;
	// }

}
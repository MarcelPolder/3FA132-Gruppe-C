<?php
namespace Webapp\Controllers;

use Webapp\Core\Error;
use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Lib\Captcha;
use Webapp\Lib\HVApi;

/** @property \Webapp\Models\Ajax $model */

class AjaxController extends \Webapp\Core\Controller {

	function __construct($data = []) {
		parent::__construct($data);

		if(!Request::getInstance()->isAjax()) Router::redirect('/');

		header('Content-Type: application/json');
		header('Accept-Ranges: bytes');
	}

	public function user() {
		$params = Router::getParams();
		$response = [];
		if (!empty($params)) {
			$post = Request::getInstance()->getPost();
			switch ($params[0]) {
				case 'update':
					if (!empty($post['firstname']) && !empty($post['lastname']) && !empty($post['id']) && !empty($post['token'])) {
						$success = HVApi::updateUser($post['id'], $post['firstname'], $post['lastname'], $post['token']);
						if (!empty($success)) {
							$response = Error::json(
								type: 'success',
								msg: 'Der Benutzer wurde geändert',
								status: 200,
								data: $success,
								returnEncoded: false,
							);
						}
					}
					break;
				case 'password':
					if (!empty($post['new-password']) && !empty($post['id'])) {
						$success = HVApi::updateUserPassword($post['id'], $post['new-password']);
						if (!empty($success)) {
							$response = Error::json(
								type: 'success',
								msg: 'Das Passwort wurde geändert.',
								status: 200,
								data: $success,
								returnEncoded: false,
							);
						}
					}
					break;
				default:
					throw new Error(__('error.404.text'), 404);
			}

			$response = json_encode($response);
			header("Content-Length: ".mb_strlen($response));
			echo $response;
			exit;
		}
	}

}
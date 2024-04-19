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

	public function users() {
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

	public function readings() {
		$params = Router::getParams();
		$post = Request::getInstance()->getPost();
		$response = [];

		if (!empty($params)) {
			switch ($params[0]) {
				case 'get':
					$chunk = empty($post['index']) ? 0 : $post['index'];
					$customers = HVApi::getReadings(true, $chunk);
					if (!empty($customers)) {
						$response = Error::json(
							msg: 'Erfolgreich',
							type: 'success',
							status: 200,
							returnEncoded: false,
							data: ['html' => $customers],
						);
					}
					break;
				case 'delete':
					if (!empty($post['id'])) {
						$response = HVApi::deleteReading($post['id']);
						if (!empty($response)) {
							$response = Error::json(
								msg: 'Der Zählerstand wurde gelöscht.',
								type: 'success',
								status: 200,
								returnEncoded: false,
								data: [
									'id' => $post['id'],
								],
							);
						} else {
							$response = Error::json(
								msg: 'Der Kunde konnte nicht gelöscht werden.',
								returnEncoded: false,
							);
						}
					}
					break;
				case 'update':
					if (!empty($post['reading']['id'])) {
						$success = HVApi::updateReading($post['reading']['id'], ['reading' => $post['reading']]);
						if (!empty($success)) {
							$response = Error::json(
								msg: 'Der Zählerstand wurde bearbeitet.',
								type: 'success',
								status: 200,
								data: $post['reading'],
								returnEncoded: false,
							);
						}
						break;
					}
					break;
			}
		}

		$response = json_encode($response);
		header("Content-Length: ".mb_strlen($response));
		echo $response;
		exit;
	}

	public function customers() {
		$params = Router::getParams();
		$post = Request::getInstance()->getPost();
		$response = [];

		if (!empty($params)) {
			switch ($params[0]) {
				case 'update':
					if (!empty($post['id']) && !empty($post['firstname']) && !empty($post['lastname'])) {
						$success = HVApi::updateCustomer($post['id'], $post
						['firstname'], $post['lastname']);
						if (!empty($success)) {
							$response = Error::json(
								msg: 'Der Kunde wurde bearbeitet.',
								type: 'success',
								status: 200,
								data: $success,
								returnEncoded: false,
							);
						}
						break;
					}
					break;
				case 'get':
					$chunk = empty($post['index']) ? 0 : $post['index'];
					$customers = HVApi::getCustomers(true, $chunk);
					if (!empty($customers)) {
						$response = Error::json(
							msg: 'Erfolgreich',
							type: 'success',
							status: 200,
							returnEncoded: false,
							data: ['html' => $customers],
						);
					}
					break;
				case 'delete':
					if (!empty($post['id'])) {
						$response = HVApi::deleteCustomer($post['id']);
						if (!empty($response)) {
							$response = Error::json(
								msg: 'Der Kunde wurde gelöscht.',
								type: 'success',
								status: 200,
								returnEncoded: false,
								data: [
									'id' => $post['id'],
								],
							);
						} else {
							$response = Error::json(
								msg: 'Der Kunde konnte nicht gelöscht werden.',
								returnEncoded: false,
							);
						}
					}
					break;
				default:
					throw new Error(__('error.404.text'), 404);
			}
		}

		$response = json_encode($response);
		header('Content-Length: '.mb_strlen($response));
		echo $response;
		exit;
	}

}
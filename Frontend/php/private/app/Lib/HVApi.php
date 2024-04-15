<?php
namespace Webapp\Lib;

use Firebase\JWT\JWT;
use Webapp\Core\Config;
use Webapp\Core\Form;
use Webapp\Core\Request;
use Webapp\Core\Session;

class HVApi {

	public static function authenticateUser(): mixed {
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

	public static function getCustomers(bool $asHtml = false): false|array|string {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/customers/get");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json'
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if (!empty($response)) {
			if ($asHtml) {
				return implode("", array_map(fn($value) => '<tr class="customer" data-id="'.$value['id'].'"><td>'.$value['firstname'].'</td><td>'.$value['lastname'].'</td></tr>', json_decode($response, true)));
			}
			return json_decode($response, true);
		}
		return false;
	}

	public static function getUsers(bool $asHtml = false): false|array|string {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/users/get");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json'
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if (!empty($response)) {
			if ($asHtml) {
				$form = Form::getInstance();
				return implode("", array_map(fn($value) => '
					<tr class="user" data-id="'.$value['id'].'">
						<td>'.$value['firstname'].'</td>
						<td>'.$value['lastname'].'</td>
						<td><a href="#"><i class="material-symbols-rounded">edit</i></a></td>
					</tr>
					<tr class="edit-box">
						<td colspan="3">
							'.$form->render(children: [
								$form->label(
									title: 'Vorname',
									titleAfterChildren: true,
									children: [
										$form->input(
											type: \Webapp\Core\FormInputType::Text,
											name: 'firstname',
											value: $value['firstname'],
											required: true,
										),
									],
								),
								$form->label(
									title: 'Nachname',
									titleAfterChildren: true,
									children: [
										$form->input(
											type: \Webapp\Core\FormInputType::Text,
											name: 'lastname',
											value: $value['lastname'],
											required: true,
										),
									],
								),
							], return: true).'
						</td>
					</tr>', json_decode($response, true)));
			}
			return json_decode($response, true);
		}
		return false;
	}
}
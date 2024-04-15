<?php
namespace Webapp\Lib;

use Firebase\JWT\JWT;
use Webapp\Core\Config;
use Webapp\Core\Form;
use Webapp\Core\Hash;
use Webapp\Core\Request;
use Webapp\Core\Session;

class HVApi {

	public static function isAvailable(): bool {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/world/hello");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response == 'Hello World!';
	}

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
				'user' => $response['user']['id'],
				'token' => $response['user']['token'],
			];
			$jwt = JWT::encode($jwtPayload, Config::get('jwt.key'), 'HS256', null, $jwtHeader);
			Session::setCookie('jwt', $jwt);
			return true;
		}
		return false;
	}
	public static function getUser(int $id) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/users/get/".$id);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		if (!empty($response)) {
			return json_decode($response, true)['user'];
		}
		return false;
	}

	public static function updateUserPassword(int $id, string $newPassword) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/users/update/".$id);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json',
			'Content-Type: application/x-www-form-urlencoded',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'password' => $newPassword,
			'token' => Hash::unique(true, 16),
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$response = json_decode($response, true);
		if (!empty($response['user'])) {
			return $response['user'];
		}
		return false;
	}

	public static function updateUser(int $id, string $firstname, string $lastname, string $token) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/users/update/".$id);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json',
			'Content-Type: application/x-www-form-urlencoded',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'firstname' => $firstname,
			'lastname' => $lastname,
			'token' => $token,
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$response = json_decode($response, true);
		if (!empty($response['user'])) {
			return $response['user'];
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
					<div class="user grid grid-center col-8" data-id="'.$value['id'].'">
						<div class="user-circle start-col-1">
							<i class="material-symbols-rounded s32">account_circle</i>
						</div>
						<div class="start-col-2">
							<p><a href="#" class="user-edit" title="Benutzer bearbeiten"><i class="material-symbols-rounded">edit</i></a></p>
							<p><a href="#" class="user-password" title="Passwort ändern"><i class="material-symbols-rounded">lock</i></a></p>
						</div>
						<div class="start-col-3 end-col-9">
							<p class="user-firstname">'.$value['firstname'].'</p>
							<p class="user-lastname">'.$value['lastname'].'</p>
						</div>
						<div class="user-info-edit start-col-1 end-col-9 start-row-2 end-row-3">
							'.$form->render(
								return: true,
								action: 'user/update',
								method: 'AJAX',
								attributes: [
									'callback' => 'updateUser',
								],
								children: [
									$form->input(
										type: \Webapp\Core\FormInputType::Hidden,
										name: 'token',
										value: $value['token']
									),
									$form->input(
										type: \Webapp\Core\FormInputType::Hidden,
										name: 'id',
										value: $value['id'],
									),
									$form->label(
										title: 'Vorname',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Text,
												name: 'firstname',
												required: true,
												value: $value['firstname'],
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
												required: true,
												value: $value['lastname'],
											),
										],
									),
									'<div class="text-right">',
									$form->button(
										type: \Webapp\Core\FormButtonType::Submit,
										name: 'edit-user',
										value: '<i class="material-symbols-rounded">edit</i>'
									),
									'</div>',
								],
							).'
						</div>
						<div class="user-password-edit start-col-1 end-col-9 start-row-2 end-row-3">
							'.$form->render(
								return: true,
								action: 'user/password',
								method: 'AJAX',
								attributes: [
									'callback' => 'updatePassword',
								],
								children: [
									$form->input(
										type: \Webapp\Core\FormInputType::Hidden,
										name: 'id',
										value: $value['id'],
									),
									$form->label(
										title: 'Neues Passwort',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Password,
												name: 'new-password',
												required: true,	
											),
										],
									),
									'<div class="text-right">',
									$form->button(
										type: \Webapp\Core\FormButtonType::Submit,
										name: 'edit-password',
										value: '<i class="material-symbols-rounded">edit</i>',
									),
									'</div>'
								]
							).'
						</div>
					</div>
				', json_decode($response, true)));
			}
			return json_decode($response, true);
		}
		return false;
	}

	public static function createUser(string $firstname, string $lastname, string $password) {
		// TODO Create does not work?
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/users/create");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json',
			'Content-Type: application/x-www-form-urlencoded',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'user' => [
				'firstname' => $firstname,
				'lastname' => $lastname,
				'token' => Hash::unique(true, 16),
				'password' => $password,
			]
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$response = json_decode($response, true);
		if (!empty($response['user'])) {
			return $response['user'];
		}
		return false;
	}

	public static function getCustomers(bool $asHtml = false): false|array|string {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/rest/customers/get");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json',
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
}
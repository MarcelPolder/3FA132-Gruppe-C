<?php
namespace Webapp\Lib;

use CurlHandle;
use Firebase\JWT\JWT;
use Webapp\Core\Config;
use Webapp\Core\Form;
use Webapp\Core\Hash;
use Webapp\Core\Request;
use Webapp\Core\Session;

class HVApi {

	private static function getCurl(string $url): CurlHandle {
		$url = Config::get('backend.url').$url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return $ch;
	}
	private static function execCurl(CurlHandle $ch) {
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return [
			'status' => $status,
			'content' => $response
		];
	}

	public static function isAvailable(): bool {
		$ch = static::getCurl("/world/hello");
		$response = static::execCurl($ch);
		return $response['status'] == 200;
	}

	public static function authenticateUser(): mixed {
		$post = Request::getInstance()->getPost();

		$ch = static::getCurl("/users/authenticate");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'username' => $post['username'],
			'password' => $post['password'],
		]));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: Application/json'
		]);
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			$data = json_decode($response['content'], true);
			$jwtHeader = [
				'alg' => "HS256",
				'typ' => "JWT",
			];
			$jwtPayload = [
				'iss' => "Hausverwaltung",
				'exp' => strtotime("now + 1 Month"),
				'sub' => 'Webapp',
				'user' => $data['user']['id'],
				'token' => $data['user']['token'],
			];
			$jwt = JWT::encode($jwtPayload, Config::get('jwt.key'), 'HS256', null, $jwtHeader);
			Session::setCookie('jwt', $jwt);
			return true;
		}
		return false;
	}
	public static function getUser(int $id) {
		$ch = static::getCurl("/users/get/".$id);
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			return json_decode($response['content'], true)['user'];
		}
		return false;
	}

	public static function updateUserPassword(int $id, string $newPassword) {
		$ch = static::getCurl("/users/update/".$id);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json',
			'Content-Type: application/x-www-form-urlencoded',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'password' => $newPassword,
			'token' => Hash::unique(true, 16),
		]));
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			return json_decode($response['content'], true)['user'];
		}
		return false;
	}

	public static function updateUser(int $id, string $firstname, string $lastname, string $token) {
		$ch = static::getCurl("/users/update/".$id);
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
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			return json_decode($response['content'], true)['user'];
		}
		return false;
	}

	public static function getUsers(bool $asHtml = false): false|array|string {
		$ch = static::getCurl("/users/get");
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			$data = json_decode($response['content'], true);
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
								action: 'users/update',
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
								action: 'users/password',
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
				', $data));
			}
			return $data;
		}
		return false;
	}

	public static function createUser(string $firstname, string $lastname, string $password) {
		// TODO Create does not work?
		$ch = static::getCurl("/users/create");
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
		$response = static::execCurl($ch);
		if ($response['status'] == 201) {
			return json_decode($response['content'], true)['user'];
		}
		return false;
	}

	public static function updateCustomer(int $id, string $firstname, string $lastname) {
		$ch = static::getCurl("/customers/update/".$id);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: Application/json',
			'Content-Type: application/json',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
			'customer' => [
				'firstname' => $firstname,
				'lastname' => $lastname,
			]
		]));
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			return json_decode($response['content'], true)['customer'];
		}
		return false;
	}

	public static function getCustomers(bool $asHtml = false, int $chunkIdx = 0): false|array|string {
		$ch = static::getCurl("/customers/get");
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			$data = json_decode($response['content'], true);
			if ($asHtml) {
				$form = \Webapp\Core\Form::getInstance();
				$chunks = array_chunk($data, 100);
				return implode("", array_map(fn($value) => '
					<tr class="customer" data-id="'.$value['id'].'">
						<td class="customer-firstname">'.$value['firstname'].'</td>
						<td class="customer-lastname">'.$value['lastname'].'</td>
						<td class="customer-actions">
							<a href="#" title="bearbeiten" class="edit-customer-toggle"><i class="material-symbols-rounded">edit</i></a>
							<a href="customers/delete" title="löschen" class="delete-customer ajaxClick" confirm="Soll der Kunde wirklich gelöscht werden?" data=\''.json_encode(["id" => $value['id']]).'\' callback="deleteCustomer"><i class="material-symbols-rounded">delete</i></a>
						</td>
					</tr>
					<tr class="edit-customer" data-id="'.$value['id'].'" style="display:none">
						<td colspan="3">
						'.$form->render(
							return: true,
							method: 'AJAX',
							action: 'customers/update',
							attributes: [
								'callback' => 'updateCustomer',
							],
							children: [
								$form->input(
									type: \Webapp\Core\FormInputType::Hidden,
									name: 'id',
									value: $value['id'],
									required: true,
								),
								$form->grid(
									left: 12,
									leftTabletPortrait: 6,
									leftTabletLandscape: 4,
									children: [

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
									],
								),
								'<div class="text-right">',
								$form->button(
									type: \Webapp\Core\FormButtonType::Submit,
									name: 'update-customer',
									value: '<i class="material-symbols-rounded">edit</i>',
								),
								'</div>',
							]
						).'
						</td>
					</tr>
				', $chunks[$chunkIdx] ?? []));
			}
			return $data;
		}
		return false;
	}
	public static function createCustomer(string $firstname, string $lastname) {
		// TODO: Create does not work? -> ID needs to be set in backend
		$ch = static::getCurl("/customers/create");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'Content-Type: application/json',
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
			'customer' => [
				'firstname' => $firstname,
				'lastname' => $lastname,
			]
		]));
		$response = static::execCurl($ch);
		if ($response['status'] == 201) {
			return json_decode($response['content'], true)['customer'];
		}
		return false;
	}

	public static function deleteCustomer(int $id) {
		$ch = static::getCurl("/customers/delete/".$id);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		$response = static::execCurl($ch);
		return $response['status'] == 200;
	}

	public static function getReadings(bool $asHtml = false, int $chunkIdx) {
		$ch = static::getCurl("/readings/get");
		$response = static::execCurl($ch);
		if ($response['status'] == 200) {
			$data = json_decode($response['content'], true);
			if ($asHtml) {
				$form = \Webapp\Core\Form::getInstance();
				$chunks = array_chunk($data, 100);
				return implode("", array_map(fn($value) => '
					<div class="reading" data-id="'.$value['id'].'">
						<div class="grid col-2">
							<div>
								<p>ID:</p=>
								<p>Kommentar:</p>
								<p>Datum:</p>
								<p>Typ:</p>
								<p>Zählerstand:</p>
								<p>Zählernummer:</p>
								<p>Austausch:</p>
								<p>&nbsp;</p>
							</div>
							<div>
								<p class="reading-id">'.(empty($value['id']) ? "&nbsp;" : $value['id']).'</p>
								<p class="reading-comment">'.(empty($value['comment']) ? "&nbsp;" : $value['comment']).'</p>
								<p class="reading-date">'.(empty($value['date_of_reading']) ? "&nbsp;" : $value['date_of_reading']).'</p>
								<p class="reading-type">'.(empty($value['kind_of_meter']) ? "&nbsp;" : $value['kind_of_meter']).'</p>
								<p class="reading-count">'.(empty($value['meter_count']) ? "&nbsp;" : $value['meter_count']).'</p>
								<p class="reading-meterid">'.(empty($value['meter_id']) ? "&nbsp;" : $value['meter_id']).'</p>
								<p class="reading-substitute">'.(empty($value['substitute']) ? "&nbsp;" : $value['substitute']).'</p>
								<p class="reading-actions">
									<a href="readings/delete" class="ajaxClick" data=\''.json_encode(['id' => $value['id']]).'\' confirm="Soll der Zählerstand wirklich gelöscht werden?" callback="deleteReading"><i class="material-symbols-rounded">delete</i></a>
									<a href="#" class="edit-reading-toggle"><i class="material-symbols-rounded">edit</i></a>
								</p>
							</div>
						</div>
						<div class="edit-reading" style="display: none;">
							'.$form->render(
								action: 'readings/update',
								method: 'AJAX',
								attributes: [
									'callback' => 'updateReading'
								],
								return: true,
								children: [
									$form->input(
										type: \Webapp\Core\FormInputType::Hidden,
										name: 'reading[id]',
										required: true,
										value: $value['id'],
									),
									$form->label(
										title: 'Kommentar',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Text,
												name: 'reading[comment]',
												value: $value['comment']
											)
										]
									),
									$form->label(
										title: 'Datum',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Text,
												name: 'reading[date_of_reading]',
												value: $value['date_of_reading']
											)
										]
									),
									$form->label(
										title: 'Typ',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Text,
												name: 'reading[kind_of_meter]',
												value: $value['kind_of_meter']
											)
										]
									),
									$form->label(
										title: 'Zählerstand',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Text,
												name: 'reading[meter_count]',
												value: $value['meter_count']
											)
										]
									),
									$form->label(
										title: 'Zählernummer',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Text,
												name: 'reading[meter_id]',
												value: $value['meter_id']
											)
										]
									),
									$form->label(
										title: 'Austausch',
										titleAfterChildren: true,
										children: [
											$form->input(
												type: \Webapp\Core\FormInputType::Checkbox,
												name: 'reading[substitute]',
												checked: !empty($value['substitute']),
											)
										]
									),
									'<div class="text-right">',
									$form->button(
										type: \Webapp\Core\FormButtonType::Submit,
										name: 'update-reading',
										value: '<i class="material-symbols-rounded">edit</i>',
									),
									"</div>"
								]
							).'
						</div>
					</div>
				', $chunks[$chunkIdx] ?? []));
			}
			return $data;
		}
		return false;
	}
	public static function createReading(
		string $comment,
		int $customer_id,
		string $date_of_reading,
		string $kind_of_meter,
		int $meter_count,
		int $meter_id,
		int $substitute,
	)  {
		$ch = static::getCurl("/readings/create");
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['reading' => [
			'comment' => $comment,
			'customer_id' => $customer_id,
			'date_of_reading' => $date_of_reading,
			'kind_of_meter' => $kind_of_meter,
			'meter_count' => $meter_count,
			'meter_id' => $meter_id,
			'substitute' => $substitute,
		]]));
		$response = static::execCurl($ch);
		return $response['status'] == 201;
	}

	public static function deleteReading(int $id) {
		$ch = static::getCurl("/readings/delete/".$id);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		$response = static::execCurl($ch);
		return $response['status'] == 200;
	}

	public static function updateReading(int $id, array $reading) {
		$ch = static::getCurl("/readings/update/".$id);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reading));
		$response = static::execCurl($ch);
		return $response['status'] == 200;
	}
}
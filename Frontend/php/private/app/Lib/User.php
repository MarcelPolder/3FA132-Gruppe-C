<?php
namespace Webapp\Lib;

use PHPMailer\PHPMailer\Exception;
use Webapp\Core\Config;
use Webapp\Core\DB;
use Webapp\Core\Error;
use Webapp\Core\Hash;
use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Core\Session;
use Webapp\Lib\Mailer;

class User extends \Webapp\Core\Model {

	private $_data = [],
			$_isLoggedIn = false;

	protected string $dbTable = "tbl_user";

	function __construct() {
		parent::__construct();

		$userId = Session::get(Config::get('sessionName')) ?? '';
		$userHash = Session::getCookie(Config::get('cookieName')) ?? '';
		$userHashSecure = Session::getCookie(Config::get('cookieNameSecure')) ?? '';
		
		if(Config::get('db.active')) {
			$table = DB::statement("SHOW TABLES LIKE 'tbl_user_session'");
			if(!empty($table)) {
				$q_userId = DB::select('user_id')->from('tbl_user_session')->where('hash', $userHash)->where('security_hash', sha1($userHashSecure))->limit(1)->get(singleResultConversion: true);
				if(empty($userId)) {
					if(!empty($userHash) && !empty($userHashSecure) && count($q_userId)) {
						$new_userHashSecure = Hash::unique();
						DB::table('tbl_user_session')->where('hash', $userHash)->limit(1)->update([
							'security_hash' => sha1($new_userHashSecure),
						]);
						Session::setCookie(Config::get('cookieName'), $userHash);
						Session::setCookie(Config::get('cookieNameSecure'), $new_userHashSecure);

						$userId = $q_userId['user_id'];
						Session::set(Config::get('sessionName'), $userId);
					}
				}
			}
		}
		if(!empty($userHash) && !empty($userHashSecure) && !count($q_userId)) {
			Session::delete(Config::get('sessionName'));
			Session::deleteCookie(Config::get('cookieName'));
			Session::deleteCookie(Config::get('cookieNameSecure'));
			header("Location: ./?loggedout");
			exit;
		}
		if(!empty($userId)) $this->_data = $this->find('id', $userId);
		if(!empty($this->_data['id'])) $this->_isLoggedIn = true;
		return $this->_data;
	}

	public function data() {
		return $this->_data;
	}

	public function find($columns = null, $values = "", $forceOr = false) {
		if(empty($values) || empty($columns)) return false;
		if(is_array($columns) && count($columns)==1) $columns = $columns[0];
		if(is_array($values) && count($values)==1) $values = $values[0];

		$wheres = [];
		if(!is_array($columns) && !is_array($values)) {
			$wheres[] = [$columns => $values];
		} else if(is_array($columns) && is_array($values) && count($columns)==count($values)) {
			foreach($columns as $key => $column) {
				$wheres[] = [$column => $values[$key]];
			}
		} else if(!is_array($columns) && is_array($values)) {
			foreach($values as $key => $value) {
				$wheres[] = [$columns => $values[$key]];
			}
		} else if(is_array($columns) && !is_array($values)) {
			foreach($columns as $key => $column) {
				$wheres[] = [$column => $values];
			}
		}

		if(!empty($this->dbFields)) {
			$q = DB::from('tbl_user');
			foreach($wheres as $key => $where) {
				$column = array_keys($where)[0];
				$value = array_values($where)[0];
				if(array_key_exists($column, $this->dbFields)) {
					if($key>0 && (
						(is_array($values) && !is_array($columns)) || 
						$forceOr
					)) {
						$q = $q->orWhere($column, $value);
					} else {
						$q = $q->where($column, $value);
					}
				}
			}
			$q = $q->limit(1);
			$q = $q->get(singleResultConversion: true);

			if($q && count($q)) {
				return array_merge($this->_data, $q);
			}
			return [];
		}
		return false;
	}

	public function create(array $params, bool $sendMail = false): array {
		if(!array_keys_exists(array_keys($params), $this->dbFields)) return false;

		if(!empty($this->dbFields['_required'])) {
			$insertParams = [];
			foreach($this->dbFields['_required'] as $required) {
				if(!empty($params[$required])) $insertParams[$required] = $params[$required];
			}
		}

		if(!empty($insertParams)) {
			$user = $this->find('username', $insertParams['username']);
			if(!empty($user)) return ['status' => false, 'error' => Error::html('Ein Konto mit diesem Benutzernamen existiert bereits. Bitte wähle andere Daten.')];

			if(!empty($this->dbFields)) {
				foreach($this->dbFields as $field => $fieldParams) {
					if($field=='_required' || $field=='_primary' || in_array($field, $this->dbFields['_primary'])) continue;
					if(isset($params[$field])) $insertParams[$field] = $params[$field];
				}
			}
			$insertParams['username'] = strtolower($insertParams['username']);
			if(!empty($insertParams['password'])) $insertParams['password'] = Hash::make($insertParams['password']);
			if(empty($insertParams['password'])) $insertParams['password'] = Hash::unique(true);
			if(!isset($insertParams['display_name'])) $insertParams['display_name'] = ucfirst($insertParams['username']);
			
			$create = DB::table('tbl_user')->insert([$insertParams]);
			if($create) {
				$return = ['status' => true, 'msg' => Error::html('Das Konto für den Benutzer <strong>'.$insertParams['username'].'</strong> wurde erfolgreich angelegt.', 'success-bg')];

				if($sendMail && !empty($insertParams['email'])) {
					$mailBody = "<h2>Deine Registrierung</h2>
					<h3>Hiermit erhältst du deine Anmeldeinformationen</h3>
					<p>
						Benutzername: <strong>".$insertParams['username']."</strong><br>
						Passwort: <strong>".$insertParams['password']."</strong><br>
					</p>";
					$mailBodyAlt = str_replace("\t", "", 
						"Deine Registrierung.\r\n\r\n
						Hiermit erhältst du deine Anmeldeinformationen\r\n\r\n
						Benutzername: ".$insertParams['username']."\r\n
						vorläufiges Passwort: ".$insertParams['password']."\r\n
						Falls du diese E-Mail nicht zuordnen kannst, lösche sie einfach."
					);
					$mailer = new Mailer(true);
					try {
						$sendStatus = $mailer->sendMail([
							'to' => $insertParams['email'],
							'toName' => $insertParams['display_name'],
							'subject' => 'Registrierung bei '.__('frontend.siteTitle'),
							'body' => $mailBody,
							'bodyAlt' => $mailBodyAlt
						]);
						if($sendStatus) {
							$return['msg'] = $return['msg']." Eine E-Mail mit den Anmeldedaten wurde an ".$insertParams['email']." gesendet.";
							return $return;
						}
					} catch(Exception $e) {
						return ['status' => false, 'error' => Error::html('Beim Erstellen des Kontos ist ein Fehler aufgetreten. Bitte versuche es noch einmal.')];
					}
				} else {
					return $return;
				}
			}
			return ['status' => false, 'error' => Error::html('Beim Erstellen des Kontos ist ein Fehler aufgetreten. Bitte versuche es noch einmal.')];
		}
		return ['status' => false, 'error' => Error::html('Die angegebenen Daten enthalten Fehler. Bitte versuche es noch einmal.')];
	}

	public function edit($formData = []): false|int {
		if(!empty($formData) && !empty($formData['id']) && !empty($formData['username'])) {
			$updateRows = [];
			foreach($formData as $key => $value) {
				if(!empty($value) || is_bool($value) || is_null($value)) {
					$updateRows[$key] = $value;
				}
			}
			if(!empty($updateRows['password'])) $updateRows['password'] = Hash::make($updateRows['password']);
			$edit = DB::table('tbl_user')->where('id', $formData['id'])->update($updateRows);
			return $edit;
		}
		return false;
	}

	public function editPassword($formData = [], $user = null) {
		if(!is_null($user) && !empty($formData) && !empty($formData['password']) && !empty($formData['password_new']) && !empty($formData['password_new_wdh']) && $formData['password_new']==$formData['password_new_wdh']) {
			$userId = $user['id'];
			$passwordHash = $user['password'];
			if(!empty($userId) && Hash::verify($formData['password'], $passwordHash)) {
				$edit = DB::table('tbl_user')->where('id', $userId)->update([
					'password' => Hash::make($formData['password_new']),
				]);
				return $edit;
			}
		}
		return false;
	}

	public function checkPassword($password = null) {
		if(!is_null($password) && !empty($this->_data)) {
			$userPassword = $this->_data['password'];
			if(Hash::verify($password, $userPassword)) {
				return true;
			}
		}
		return false;
	}

	public function delete($userId = null): bool {
		if(!is_null($userId)) {
			DB::table('tbl_user_session')->where('user_id', $userId)->delete();
			$delete = DB::table('tbl_user')->where('id', $userId)->limit(1)->delete();
			return $delete;
		}
		return false;
	}

	public function loginCheck() {
		return $this->_isLoggedIn;
	}

	public function login() {
		$post = Request::getInstance()->getPost();
		$username = $post['username'] ?? '';
		$password = $post['password'] ?? '';
		$remember = (isset($post['remember']) && $post['remember']=='on' ? true : false);
		if(!empty($username) && !empty($password)) {
			$user = $this->find('username', $username);
			if($user && isset($user['password']) && Hash::verify($password, $user['password'])) {
				$this->_data = array_merge($this->_data, $user);
				
				Session::set(Config::get('sessionName'), $user['id']);
				
				if($remember || Config::get('forceCookieLogin')) {
					[$hash, $security_hash] = $this->setSession();
					Session::setCookie(Config::get('cookieName'), $hash);
					if(empty($security_hash)) {
						$security_hash = Hash::unique();
						DB::table('tbl_user_session')->where('hash', $hash)->limit(1)->update([
							'security_hash' => sha1($security_hash),
						]);
					}
					Session::setCookie(Config::get('cookieNameSecure'), $security_hash);
				}
				
				$this->_isLoggedIn = true;
				return ['status' => true];
			} else {
				return ['status' => false, 'error' => Error::html('Der Benutzer oder das Passwort war nicht korrekt.')];
			}
		} else {
			return ['status' => false];
		}
	}

	public function logout() {
		if($this->_isLoggedIn) {
			$this->_isLoggedIn = false;
			$this->deleteSession();
			Session::delete(Config::get('sessionName'));
			Session::deleteCookie(Config::get('cookieName'));
			Session::deleteCookie(Config::get('cookieNameSecure'));
			return true;
		}
		return false;
	}

	public function setSession() {
		$q_session = DB::select('hash, security_hash')->from('tbl_user_session')->where('user_id', $this->_data['id'])->where('session_id', md5(session_id()))->limit(1)->get(singleResultConversion: true);
		if(!count($q_session)) {
			$hash = Hash::unique();
			$security_hash = Hash::unique();
			DB::table('tbl_user_session')->insert([
				[
					'user_id' => $this->_data['id'],
					'hash' => $hash,
					'security_hash' => sha1($security_hash),
					'session_id' => md5(session_id()),
				],
			]);
			return [$hash, $security_hash];
		} else {
			$hash = $q_session['hash'];
			return [$hash, null];
		}
	}

	public function deleteSession() {
		if(!empty($this->_data) && !empty($this->_data['id'])) {
			$userHash = Session::getCookie(Config::get('cookieName')) ?? '';
			if(!empty($userHash)) {
				DB::table('tbl_user_session')->where('user_id', $this->_data['id'])->where('hash', Session::getCookie(Config::get('cookieName')) ?? '')->limit(1)->delete();
			}
			return true;
		}
		return false;
	}

	public function passwordReset() {
		$post = Request::getInstance()->getPost();
		$token = $post['reset_token'] ?? '';
		$newPassword = $post['password_new'] ?? '';
		$newPasswordRepeat = $post['password_new_repeat'] ?? '';
		if(
			!empty($token) &&
			!empty($newPassword) && !empty($newPasswordRepeat) &&
			$newPassword===$newPasswordRepeat &&
			preg_match('/^(?=.{8,}$)(?=.*?[a-z])(?=.*?[A-Z])(?=.*?[0-9])(?=.*?\W).*$/', $newPassword)
		) {
			$update = DB::table('tbl_user')->where('password_reset_token', $token)->limit(1)->update([
				'password' => Hash::make($newPassword),
				'password_reset_token' => NULL,
				'password_reset_token_valid' => NULL,
			]);
			if($update) return ['status' => true, 'msg' => Error::html('Das Passwort wurde erfolgreich geändert.', 'success')];
		}
		return ['status' => false, 'error' => Error::html('Das neue Passwort muss aus mindestens 8 Zeichen bestehen, einen Groß- und Kleinbuchstaben enthalten und mit der Wiederholung übereinstimmen.')];
	}

	public function passwordResetCheck($token = '') {
		$token = Router::getParams()[0] ?? $token;
		if(!empty($token)) {
			$user = DB::from('tbl_user')->where('password_reset_token', $token)->where('unix_timestamp(password_reset_token_valid)', '>', strtotime("now"))->get();
			if(!empty($user)) return $token;
		} else {
			Session::setFlash('Der Link ist abgelaufen oder fehlerhaft.', 'error');
		}
		return false;
	}

	public function passwordResetMail() {
		$post = Request::getInstance()->getPost();
		$username = $post['username'] ?? '';
		$username = 'rossamedia';
		if(!empty($username)) {
			$user = $this->find(['username','email'], $username, true);
			if($user) {
				$userId = $user['id'];
				$username = $user['username'];
				$email = $user['email'];
				$display_name = $user['display_name'];

				if(!empty($email)) {
					$token = Hash::unique(true, 32);
					$tokenValid = date("Y-m-d H:i:s", strtotime("+1 hour"));
					DB::table('tbl_user')->where('id', $userId)->limit(1)->update([
						'password_reset_token' => $token,
						'password_reset_token_valid' => $tokenValid,
					]);

					$mailBody = "<h2>Passwort zurücksetzen</h2>
					<p>Klicke auf den Button oder kopiere den Link in deinen Browser um dein Passwort zurückzusetzen.</p>
					<p class=\"text-center\">
						<a href=\"".Config::get('mail.url')."/pwreset/".$token."\" target=\"_blank\" class=\"btn\">zurücksetzen</a>
					</p>
					<p class=\"text-center\">
						<a href=\"".Config::get('mail.url')."/pwreset/".$token."\" target=\"_blank\" style=\"font-size: 14px;\">".Config::get('mail.url')."/pwreset/".$token."</a>
					</p>
					<p class=\"text-center\" style=\"font-size: 12px; color: #999999;\">
						Der Link ist eine Stunde gültig.
					</p>";
					$mailBodyAlt = str_replace("\t", "", 
						"Passwort zurücksetzen.\r\n\r\n
						Kopiere den Link in deinen Browser um dein Passwort zurückzusetzen.\r\n\r\n
						".Config::get('mail.url')."/pwreset/".$token."\r\n\r\n
						Der Link ist eine Stunde gültig.\r\n\r\n
						Falls du diese E-Mail nicht zuordnen kannst, lösche sie einfach."
					);
					$mailer = new Mailer(true);
					try {
						$send = $mailer->sendMail([
							'to' => $email,
							'toName' => $display_name,
							'subject' => 'Dein Passwort zurücksetzen auf '.__('siteTitle'),
							'body' => $mailBody,
							'bodyAlt' => $mailBodyAlt,
						]);
						if($send) return ['status' => true, 'msg' => Error::html('Es wurde ein Link zum Zurücksetzen des Passworts an die hinterlegte E-Mail-Adresse gesendet.', 'success mail')];
					} catch(Exception $e) {
						return ['status' => false];
					}
				}
			}
		}
		return ['status' => false];
	}
	
}
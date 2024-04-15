<?php
namespace Webapp\Controllers;

use Webapp\Core\Controller;
use Webapp\Core\Error;
use Webapp\Core\Form;
use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Core\Session;
use Webapp\Lib\HVApi;

class AuthController extends Controller {

	public function __construct($data = []) {
		parent::__construct($data);
		$this->user = new \Webapp\Lib\HVUser();
	}

	public function login() {
		if ($this->user->loginCheck()) {
			Router::redirect('/');
		}
		$form = Form::getInstance();

		if ($form->is('login')) {
			if (HVApi::authenticateUser()) {
				Router::redirect("/");
			} else {
				$form->error('Die Zugangsdaten waren falsch.');
			}
		}
	}

	public function logout() {
		Session::deleteCookie('jwt');
		Router::redirect("/");
	}

}
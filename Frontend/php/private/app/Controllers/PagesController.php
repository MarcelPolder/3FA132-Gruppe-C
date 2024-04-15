<?php
namespace Webapp\Controllers;

use Webapp\Core\Config;
use Webapp\Core\Form;
use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Lib\HVApi;

/** @property \Webapp\Models\Pages $model */

class PagesController extends \Webapp\Core\Controller {

	public function __construct($data = []) {
		parent::__construct($data);
		$this->user = new \Webapp\Lib\HVUser();

		if (!$this->user->loginCheck()) {
			Router::redirect("/auth/login");
		}
	}

	/* Sites */
	public function startseite() {}
	public function customers() {
		$this->data['customersHTML'] = HVApi::getCustomers(true);
	}
	public function users() {
		$form = Form::getInstance();
		$post = Request::getInstance()->getPost();
		if ($form->is('create-user')) {
			$response = HVApi::createUser($post['firstname'], $post['lastname'], $post['password']);
		}
		$this->data['usersHTML'] = HVApi::getUsers(true);
	}

}

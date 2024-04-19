<?php
namespace Webapp\Controllers;

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
		$form = Form::getInstance();
		$post = Request::getInstance()->getPost();
		if ($form->is('create-customer')) {
			$response = HVApi::createCustomer($post['firstname'], $post['lastname']);
			if (!empty($response)) {
				$form->success('Der Kunde wurde erfolgreich erstellt.');
			} else {
				$form->error('Ein Fehler ist aufgetreten.');
			}
		}
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

	public function readings() {
		$form = Form::getInstance();
		$post = Request::getInstance()->getPost();
		if ($form->is('create-reading')) {
			$response = HVApi::createReading(
				$post['reading']['comment'],
				$post['reading']['customer_id'],
				$post['reading']['date_of_reading'],
				$post['reading']['kind_of_meter'],
				$post['reading']['meter_count'],
				$post['reading']['meter_id'],
				$post['reading']['substitute'],
			);
			if ($response) {
				$form->success('Der Zählerstand wurde hinzugefügt');
			}
		}
	}

}

<?php
namespace Webapp\Controllers;

use Webapp\Core\Config;
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
		$this->data['usersHTML'] = HVApi::getUsers(true);
	}

}

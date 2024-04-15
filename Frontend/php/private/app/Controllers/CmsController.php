<?php
namespace Webapp\Controllers;

use Webapp\Core\Config;
use Webapp\Core\Request;
use Webapp\Core\Router;
use Webapp\Core\Session;

/** @property \Webapp\Models\Cms $model */

class CmsController extends \Webapp\Core\Controller {

	function __construct($data = []) {
		parent::__construct($data);

		if(!$this->data['userLoggedIn'] && strpos($this->data['navActive'], 'cms.login')===false) {
			$redirectMethod = Router::getMethod();
			Router::redirect('./login'.(!empty($redirectMethod) && $redirectMethod!=Config::get('defaultMethod') ? "?r=".$redirectMethod : ""), 403);
		}
	}

	public function startseite() {
		// // ! add route /pwreset/{token}
		// if(Router::getParams()[0]=='pwreset') {
		// 	$this->data['pwreset'] = $this->user->passwordResetCheck();
		// }
	}

	public function login() {
		if($this->data['userLoggedIn']) Router::redirect('./');

		$requestGet = Request::getInstance()->getGet();
		if(isset($requestGet['loggedout'])) {
			Session::setFlash('Die Abmeldung war erfolgreich.', 'info-bg');
		}

		// after successful login => Router::redirect('./'.(isset($requestGet['r']) ? $requestGet['r'] : ""));
	}

}
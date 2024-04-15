<?php
namespace Webapp;

use Webapp\Core\App;
use Webapp\Core\Config;
use Webapp\Lib\ResourceManager;
use Webapp\Lib\Updater;

// Definitions - Global
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));
define('PRIVATEROOT', ROOT.DS.'private');
define('NS', '\\'.__NAMESPACE__.'\\');
define('CONFIGNS', NS.'Config\\');
define('CONTROLLERNS', NS.'Controllers\\');
define('CORENS', NS.'Core\\');
define('MODELNS', NS.'Models\\');
define('LIBNS', NS.'Lib\\');

// Definitions - App
define('VENDORDIR', PRIVATEROOT.DS.'vendor');
define('NODEMODULES', PRIVATEROOT.DS.'node_modules');
define('APPDIR', PRIVATEROOT.DS.'app');
define('CONFIGDIR', APPDIR.DS.'Config');
define('COREDIR', APPDIR.DS.'Core');
define('CONTROLLERDIR', APPDIR.DS.'Controllers');
define('MODELDIR', APPDIR.DS.'Models');
define('VIEWDIR', APPDIR.DS.'Views');
define('VIEWHELPERDIR', VIEWDIR.DS.'_helper');
define('LANGDIR', APPDIR.DS.'Lang');

// Init
require_once APPDIR.DS.'init.php';

// Run App
try {
	if(isset($_GET['type']) && $_GET['type']=='resource') ResourceManager::resolve();
	if(Config::get('updater')) Updater::update();
	App::run();
} catch(Core\Error $error) {
	if(isset($_GET['type']) && $_GET['type']=='resource') http_response_code($error->getCode());

	if (\Webapp\Core\Request::getInstance()->isMain()) {
		$error->render();
	} else {
		echo \Webapp\Core\Error::json($error->getMessage(), status: $error->getCode());
	}
} catch(\Exception $e) {
	if(isset($_GET['type']) && $_GET['type']=='resource') http_response_code(404);

	if(Config::get('dev')) $whoopsRun->handleException($e);
	else {
		$error = new \Webapp\Core\Error($e->getMessage(), $e->getCode());
		$error->render();
	}
}
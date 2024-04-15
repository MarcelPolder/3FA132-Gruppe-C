<?php
namespace Webapp\Core;

class App {
	
	public static function run() {
		// db
		if(Config::get('db.active')) {
			$db = new Database();
			if(!empty($db->getError())) throw new Error("Datenbank Wartungsmodus.", 503);
		}

		// language
		setlocale(LC_ALL, Config::get('languageCodes')[Router::getLanguage()].'.utf-8');
		Lang::load(Router::getLanguage());
		$lang = Router::getLanguage();

		// routing
		if(is_file(CONFIGDIR.DS.'Routes.php')) require_once CONFIGDIR.DS.'Routes.php';
		Router::init();

		// language
		if(Router::getLanguage()!=$lang) {
			setlocale(LC_ALL, Config::get('languageCodes')[Router::getLanguage()].'.utf-8');
			Lang::load(Router::getLanguage());
		}

		// if main call from browser OR ajax call
		if(Request::getInstance()->isMain() || Request::getInstance()->isAjax()) {
			// get call object
			$call = Router::getCall();
			$callReturn = Router::getCallReturn();

			// controller
			if($call instanceof \Closure) {
				$controllerClass = new (CONTROLLERNS.ucfirst(Router::getController()).Config::get('defaultControllerSuffix'));
				if(!empty($callReturn)) {
					if(!is_file($callReturn)) {
						$controllerClassDataContent = $callReturn;
					}
				} else {
					Router::setNotFound();
				}
			} else {
				if(!empty($call)) {
					$controllerClass = $call[0];
					if(!empty($callReturn) && !is_file($callReturn)) {
						$controllerClassDataContent = $callReturn;
						$callReturn = "";
					}
				} else {
					$controllerClass = new (CONTROLLERNS.ucfirst(Router::getController()).Config::get('defaultControllerSuffix'));
					Router::setNotFound();
				}
			}

			$controllerClassData = $controllerClass->getData();
			$controllerClassContentData = $controllerClassData;
			if(!empty($controllerClassDataContent)) $controllerClassData['content'] = $controllerClassDataContent;

			// darkmode
			$controllerClassData['darkmode'] = Config::get('darkmodeCheck');
			if($controllerClassData['darkmode']) {
				$controllerClassData['darkmodeActive'] = (isset($_COOKIE['darkmode']) && $_COOKIE['darkmode']=='true' ? true : false);
			} else if(isset($_COOKIE['darkmode']) && $_COOKIE['darkmode']=='true') {
				setcookie("darkmode", false, -1);
			}

			// seo
			$controllerClassData['lang'] = Router::getLanguage();
			if(!array_key_exists('titel', $controllerClassData)) {
				$controllerClassData['titel'] = (!empty(__(Router::getLayout().'.'.(Router::getController()!=Config::get('defaultController') ? Router::getController().'.' : '').Router::getMethod().'.title')) ? __(Router::getLayout().'.'.(Router::getController()!=Config::get('defaultController') ? Router::getController().'.' : '').Router::getMethod().'.title')." ".__(Router::getLayout().'.'.'siteTitlePrefix')." " : "").__(Router::getLayout().'.'.'siteTitle');
			}
			if(!array_key_exists('beschreibung', $controllerClassData)) {
				$controllerClassData['beschreibung'] = (!empty(__(Router::getLayout().'.'.(Router::getController()!=Config::get('defaultController') ? Router::getController().'.' : '').Router::getMethod().'.siteDescription')) ? __(Router::getLayout().'.'.(Router::getController()!=Config::get('defaultController') ? Router::getController().'.' : '').Router::getMethod().'.siteDescription') : __(Router::getLayout().'.'.'siteDescription'));
			}
			if(!array_key_exists('keywords', $controllerClassData)) {
				$controllerClassData['keywords'] = (!empty(__(Router::getLayout().'.'.(Router::getController()!=Config::get('defaultController') ? Router::getController().'.' : '').Router::getMethod().'.siteKeywords')) ? __(Router::getLayout().'.'.(Router::getController()!=Config::get('defaultController') ? Router::getController().'.' : '').Router::getMethod().'.siteKeywords') : __(Router::getLayout().'.'.'siteKeywords'));
			}

			// settings
			$controllerClassData['googleAnalytics'] = Config::get('googleAnalytics');
			$controllerClassData['adblockChecker'] = Config::get('adblockChecker');

			// finalize
			if(Router::isNotFound()) {
				throw new Error(__('error.404.text'), 404);
			} else {
				if(!isset($controllerClassData['content'])) {
					// view
					$view = new View($callReturn);
					if(Router::isNotFound()) {
						throw new Error(__('error.404.text'), 404);
					} else {
						// add css and js
						if(method_exists($controllerClass, 'addCss')) {
							if(!isset($controllerClassData['css'])) $controllerClassData['css'] = [];
							$controllerClass->addCss(
								$view->getCss(),
								order: 'global',
								dataAppend: $controllerClassData['css']
							);
						}
						if(method_exists($controllerClass, 'addJs')) {
							if(!isset($controllerClassData['js'])) $controllerClassData['js'] = [];
							$controllerClass->addJs(
								$view->getJs(),
								id: 'pageScript',
								order: 'global',
								dataAppend: $controllerClassData['js']
							);
						}

						// render content
						$content = $view->render($controllerClassContentData);
						$controllerClassData['content'] = $content;

						// render layout
						$layout = Router::getLayout();
						$layoutPath = VIEWDIR.DS.$layout.'.php';
						$layoutViewObject = new View($layoutPath);
						if(!Router::isNotFound()) {
							echo $layoutViewObject->render($controllerClassData);
						} else {
							throw new \Exception("Fehler: Layout kann nicht gefunden werden.", 404);
						}
					}
				} else {
					echo $controllerClassData['content'];
				}
			}
		} else {
			http_response_code(404);
		}
	}

}
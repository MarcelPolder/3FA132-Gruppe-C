<?php
namespace Webapp\Core;

use Webapp\Lib\User;

class Router {

	private static 	$url,
					$baseHref = '/',
					$params = [],
					$route = [],
					$customRoutes = [],
					$request = null,
					$notfound = true;
	private static ?User $user = null;
	
	public static function init() : void {
		self::$user = new User();
		self::$request = Request::getInstance();
		self::resolve(self::$request->getGet()['url'] ?? "");
	}

	private static function resolve($url = "") : void {
		self::$url = $url;
		self::$params = array_filter(explode('/', strtolower(trim(self::$url, '/'))));

		// config
		self::$route['layout'] = Config::get('defaultLayout');
		self::$route['language'] = Config::get('defaultLanguage');
		self::$route['controller'] = Config::get('defaultController');
		self::$route['method'] = Config::get('defaultMethod');
		self::$route['filename'] = Config::get('defaultFilename');
		self::$route['call'] = [self::$route['controller'], self::$route['method']];
		self::$route['callParams'] = [];

		// set language
		$browserLanguages = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : self::$route['language'];
		if(in_array($browserLanguages, Config::get('languages')) && self::$route['language']!=$browserLanguages) {
			self::$route['language'] = $browserLanguages;
		}

		// params --- /{layout?}/{sprache?}/{controller?}/{methode/alias}/{...params?}
		if(count(self::$params)) {
			// set layout (and controller)
			if(current(self::$params) && is_file(VIEWDIR.DS.current(self::$params).'.php')) {
				self::setLayout(current(self::$params));
				self::$baseHref .= current(self::$params).'/';

				// set controller
				if(current(self::$params) && self::resolveController(current(self::$params), false)) {
					if(!defined('SUBDOMAIN') || (defined('SUBDOMAIN') && SUBDOMAIN!='api')) {
						self::$route['controller'] = current(self::$params);
					}
				}
				
				array_shift(self::$params);
			}

			// set language
			if(in_array(current(self::$params), Config::get('languages'))) {
				self::$route['language'] = current(self::$params);
				self::$baseHref .= self::$route['language'].'/';
				array_shift(self::$params);
			}

			// set controller
			if(current(self::$params) && self::resolveController(current(self::$params), false)) {
				if(!defined('SUBDOMAIN') || (defined('SUBDOMAIN') && SUBDOMAIN!='api')) {
					self::$route['controller'] = current(self::$params);
					self::$baseHref .= self::$route['controller'].'/';
				}
				array_shift(self::$params);
			}

			// set method and filename
			$method = current(self::$params) ? str_replace('-', '_', current(self::$params)) : "";
			
			// Mappings
			if(!empty($method) && $method!=self::$route['method']) {
				if(method_exists(self::resolveController(self::$route['controller'], false), $method)) {
					self::$route['method'] = $method;
					self::$route['filename'] = current(self::$params);
					array_shift(self::$params);
				} else {
					self::$route['method'] = "_found";
					self::$route['filename'] = current(self::$params);
				}
				self::$baseHref .= self::$route['filename'].'/';
			}

			// set mainpage --- mainpage/subpage
			if(is_dir(VIEWDIR.DS.self::$route['method'])) {
				if(current(self::$params)) {
					self::$route['filename'] = current(self::$params);
					array_shift(self::$params);
				} else {
					self::$route['filename'] = Config::get('defaultMethod');
				}
			}

			// set call
			self::$route['call'] = [self::$route['controller'], self::$route['method']];
		}

		// custom routes
		$isCustomRoute = false;
		if(!empty(self::$customRoutes)) {
			foreach(self::$customRoutes as $customRouteUrlPattern => $customRoute) {
				if(preg_match("#^".$customRouteUrlPattern."$#", '/'.self::$url, $matches)) {
					array_shift($matches);

					$customRouteCall 		= $customRoute['call'];
					$customRouteCallParams 	= $customRoute['callParams'];
					$customRouteLayout 		= $customRoute['layout'];

					if(!empty($customRouteCallParams)) $customRouteCallParams = array_combine($customRouteCallParams, $matches);
					$matches = array_merge($matches, [
						'url' => self::$url,
						'route' => self::$route,
					]);

					self::$baseHref = '/';
					if(is_array($customRouteCall)) {
						self::$route['controller'] = strtolower(str_replace([CONTROLLERNS, Config::get('defaultControllerSuffix')], "", "\\".$customRouteCall[0]));
					}
					self::$route['layout'] = $customRouteLayout;
					self::$route['call'] = $customRouteCall;
					self::$route['callParams'] = $matches;
					self::$route['callReturn'] = (is_callable(self::$route['call']) ? call_user_func(self::$route['call'], ...array_values(self::$route['callParams'])) : null);
					self::$route['method'] = is_array($customRouteCall) ? $customRouteCall[1] : Config::get('defaultMethod');
					self::$route['filename'] = is_array($customRouteCall) ? str_replace("_", "-", $customRouteCall[1]) : Config::get('defaultFilename');
					self::$baseHref .= self::$route['filename'].'/';

					if(is_array(self::$route['callReturn'])) {
						if(!empty(self::$params[0]) && self::$params[0]==self::$route['callReturn']['method']) {
							array_shift(self::$params);
						}
						self::$route = array_merge(self::$route, self::$route['callReturn']);
						self::$route['callReturn'] = "";
						self::$baseHref = '/'.self::$route['filename'].'/';
					} else $isCustomRoute = true;

					break;
				}
			}
		}

		// add directory and language
		self::$route['filename'] = (strpos(self::$route['filename'], DS)===false ? (is_dir(VIEWDIR.DS.self::$route['method']) ? self::$route['method'].DS : self::$route['controller'].DS) : "").self::$route['language'].DS.self::$route['filename'];
		
		// resolve controller for call
		if(is_array(self::$route['call'])) {
			self::$route['call'] = [self::resolveController(self::$route['call'][0]), self::$route['call'][1]];
			self::$route['callReturn'] = (is_callable(self::$route['call']) ? call_user_func(self::$route['call'], ...array_values(self::$route['callParams'])) : null);
		}

		// check 404
		if(is_callable(self::$route['call']) && (is_file(VIEWDIR.DS.self::$route['filename'].'.php') || !empty(self::$route['callReturn']) || self::$request->isAjax() || $isCustomRoute)) self::$notfound = false;
		else self::setNotFound();

		// if(isset($_GET['dev'])) dump(self::$route);
	}

	private static function resolveController($controller = "", $returnClassObject = true) : Controller|string|false {
		if(!empty($controller)) {
			if(class_exists($controller)) {
				return ($returnClassObject ? new ($controller) : $controller);
			} else {
				$controller = ucfirst($controller);
				$controllerSuffix = Config::get('defaultControllerSuffix');
				if(class_exists(CONTROLLERNS.$controller.$controllerSuffix)) {
					return ($returnClassObject ? new (CONTROLLERNS.$controller.$controllerSuffix) : CONTROLLERNS.$controller.$controllerSuffix);
				}
			}
		}
		return false;
	}
	private static function setLayout($name = "") : bool {
		if(!empty($name)) {
			self::$route['layout'] = $name;
			return true;
		}
		return false;
	}

	// setter
	public static function setNotFound(bool $value = true) : bool {
		if($value) {
			// reset filename and method
			self::$route['method'] = "_notfound";
			self::$route['filename'] = self::$route['method'];
			self::$baseHref = '/';

			// set default route
			self::setLayout(Config::get('defaultLayout'));

			// reset call
			self::$route['call'][1] = self::$route['method'];
		}
		return self::$notfound = $value;
	}

	// getter
	public static function isNotFound() : bool {
		return self::$notfound;
	}
	public static function getLayout() {
		return self::$route['layout'] ?? Config::get('defaultLayout');
	}
	public static function getLanguage() {
		return self::$route['language'] ?? Config::get('defaultLanguage');
	}
	public static function getController() {
		return self::$route['controller'] ?? Config::get('defaultController');
	}
	public static function getMethod() {
		return self::$route['method'] ?? Config::get('defaultMethod');
	}
	public static function getFilename() {
		return self::$route['filename'] ?? Config::get('defaultFilename');
	}
	public static function getCall() {
		return self::$route['call'];
	}
	public static function getCallParams() {
		return self::$route['callParams'];
	}
	public static function getCallReturn() {
		return self::$route['callReturn'] ?? null;
	}
	public static function getParams() {
		return self::$params;
	}
	public static function getUrl() {
		return self::$url;
	}
	public static function getRootUrl() {
		return self::$baseHref;
	}
	public static function getUser() {
		return self::$user;
	}

	// routing
	/**
	 * Add a custom route
	 *
	 * @param string $url
	 * @param string|null $layout
	 * @param array|\Closure $call
	 * @return false|array
	 * 
	 * # url syntax:
	 *
	 * /url => matches exact /url\
	 * /url/{var} => matches exact /url/param and passes $var=param as call parameter\
	 * /url/* => matches /url, /url/ and /url/param(/...)
	 * 
	 * # Examples:
	 * ```
	 * Router::route('/url', 'frontend', [Controller::class, 'method']);
	 * Router::route('/url', 'frontend', function() { return Viewpath|Content; });
	 * Router::route('/url/{param1}[.../{param}]', 'backend', [Controller::class, 'method']);
	 * Router::route('/url/{param1}[.../{param}]', 'backend', function($param1 = null, ...) { return Viewpath|Content; });
	 * Router::route('/url/*', 'frontend', [Controller::class, 'method']);
	 * ```
	 */
	public static function route(string $url, string $layout = null, array|\Closure $call) : false|array {
		$layout = (!is_null($layout) && !empty($layout) && !is_array($layout) && strpos($layout, '/')!==0 ? $layout : Config::get('defaultLayout'));
		$callParams = [];

		// url
		if(!empty($url) && strpos($url, '/')===0) {
			// check for variables
			$url = str_replace(["/", "\/*"], ["\/", "(?:\/(?:[\w\/]+)?)?"], $url);
			if(preg_match_all('/{([a-zA-Z_-]+)}/', $url, $matches)) {
				$variablePlaceholders = $matches[0];
				$callParams = $matches[1];
				foreach($variablePlaceholders as $key => $placeholder) {
					$url = str_replace($placeholder, "([a-zA-Z_-]+)", $url);
				}
			}
		}

		if(!empty($url)) {
			if(isset(self::$customRoutes[$url])) {
				error_log("--- Doppelte Route mit URL: ".$url);
			}
			self::$customRoutes[$url] = [
				'call' => $call,
				'callParams' => $callParams,
				'layout' => $layout,
			];
			return self::$customRoutes;
		}
		return false;
	}
	public static function redirect($location = "", $code = 301) {
		if(!empty($location)) {
			http_response_code($code);
			if(mb_substr($location, 0, 1)=='/') {
				header('Location: '.$location);
			} else {
				header('Location: '.self::getRootUrl().$location);
			}
			exit;
		} else {
			return false;
		}
	}

	// security
	private static function getAuthorizationHeader() {
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}
	public static function getBearerToken() {
		$headers = self::getAuthorizationHeader();
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		return null;
	}

}
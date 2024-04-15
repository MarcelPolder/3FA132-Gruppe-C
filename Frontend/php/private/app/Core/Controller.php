<?php
namespace Webapp\Core;

use Webapp\Lib\ResourceManager;

class Controller {
	
	protected 	array $data;
	protected	array $params;
	protected 	?\Webapp\Core\Model $model;
	protected 	?\Webapp\Lib\User $user;

	function __construct($data = []) {
		$this->data = $data;
		$this->params = Router::getParams();

		// model
		$modelName = MODELNS.ucfirst(Router::getController());
		if(class_exists($modelName)) $this->model = new $modelName();
		else $this->model = new Model();

		// user
		$this->user = Router::getUser();
		$this->data['user'] = $this->user->data();
		$this->data['userLoggedIn'] = $this->user?->loginCheck();

		// navigation
		$this->data['navActive'] = Router::getLayout().".".(Router::getController()!=Config::get('defaultController') && Router::getController()!=Router::getLayout() ? Router::getController()."." : "").Router::getMethod();

		// global assets
		$this->preload('/css/material-symbols/material-symbols-rounded.woff2');

		$this->addCss(
			"/css/material-symbols/rounded.css",
			order: 'global',
		);
		$this->addCss(
			"/css/flatpickr/flatpickr.css",
			order: 'global',
		);
		$this->addCss(
			'/css/external/mhCookie.css',
			order: 'global',
			when: Config::get('rights.datenschutz.cookies'),
		);
		$this->addCss(
			'/css/layout/'.Router::getLayout().'/main.css',
			order: 'global',
		);

		$this->addHeadElement('<style>.flpckr-clear::before {content: "'.__('flatpickr.clear').'";}</style>');

		$this->addJs(
			'/js/layout/'.Router::getLayout().'/werbebanner-ad.js',
			id: 'werbebannerAd',
			order: 'global',
			when: Config::get('adblockChecker'),
		);
		$this->addJs(
			'/js/jquery.js',
			id: 'jQueryScript',
			order: 'global',
		);
		$this->addJs(
			'/js/flatpickr.js',
			order: 'global',
		);
		$this->addJs(
			'/js/flatpickr/l10n/'.Router::getLanguage().'.js',
			order: 'global',
		);
		$this->addJs(
			'/js/external/mhLightbox.js',
			order: 'global',
		);
		$this->addJs(
			'/js/external/mhCookie.js',
			id: 'mhCookie_script',
			data: [
				'seite' => 'datenschutz',
				'tools' => [
					(Config::get('rights.datenschutz.google_maps') ? 'googleMaps' : ''),
					(Config::get('rights.datenschutz.google_analytics') ? 'googleAnalytics' : ''),
					(Config::get('rights.datenschutz.google_adsense') ? 'googleAdsense' : ''),
					(Config::get('rights.datenschutz.google_recaptcha') ? 'googleRecaptcha' : ''),
				]
			],
			order: 'global',
			when: Config::get('rights.datenschutz.cookies'),
		);
		$this->addJs(
			'/js/layout/'.Router::getLayout().'/scripts.js',
			order: 'global',
		);
		$this->addJs(
			'/js/layout/'.Router::getLayout().'/darkmode.js',
			order: 'global',
			when: Config::get('darkmodeCheck'),
		);
		$this->addJs(
			'https://www.google.com/recaptcha/api.js',
			order: 'global',
			when: Config::get('rights.datenschutz.google_recaptcha'),
		);
	}

	public function getData(): array {
		return $this->data;
	}

	public function getModel(): ?\Webapp\Core\Model {
		return $this->model;
	}

	public function getParams(): array {
		return $this->params;
	}


	public final function _found(): void {}
	public final function _notfound(): void {
		if(Request::getInstance()->isAjax()) {
			http_response_code(404);
			exit;
		}
	}


	public function addCss(
		string $css,
		string $type = 'text/css',
		bool $cache = true,
		array $data = [],
		string $order = 'local',
		array &$dataAppend = null,
		bool $when = true
	): void {
		if(!empty($css) && $when) {
			$potentialModule = ResourceManager::isNodeModule($css, true);
			$potentialLink = strpos($css, "http")===0 && get_httpResponseCode($css)=="200";
			if(!is_file(FRONTEND.$css) && !$potentialModule && !$potentialLink) return;
			if($cache && !$potentialLink) $css = $css."?h=".md5(($potentialModule ? $potentialModule['filemtime'] : filemtime(FRONTEND.$css)));

			$element = [
				'href' => $css,
				'type' => $type,
				'data' => $data,
			];
			
			if($order=='local') {
				$orderKey = count($this->data['css'] ?? []) + 99;
			} else if($order=='global') {
				$orderKey = count($this->data['css'] ?? []);
			}

			if(!is_null($dataAppend)) $dataAppend[$orderKey] = $element;
			else $this->data['css'][$orderKey] = $element;
		}
	}
	public function addJs(
		string $js,
		string $type = 'text/javascript',
		bool $defer = true,
		bool $cache = true,
		string $id = "",
		array $data = [],
		string $order = 'local',
		array &$dataAppend = null,
		bool $when = true
	): void {
		if(!empty($js) && $when) {
			$potentialModule = ResourceManager::isNodeModule($js, true);
			$potentialLink = strpos($js, "http")===0 && get_httpResponseCode($js)=="200";
			if(!is_file(FRONTEND.$js) && !$potentialModule && !$potentialLink) return;
			if($cache && !$potentialLink) $js = $js."?h=".md5(($potentialModule ? $potentialModule['filemtime'] : filemtime(FRONTEND.$js)));

			$element = [
				'src' => $js,
				'type' => $type,
				'defer' => $defer,
				'id' => $id,
				'data' => $data,
			];

			if($order=='local') {
				$orderKey = count($this->data['js'] ?? []) + 99;
			} else if($order=='global') {
				$orderKey = count($this->data['js'] ?? []);
			}

			if(!is_null($dataAppend)) $dataAppend[$orderKey] = $element;
			else $this->data['js'][$orderKey] = $element;
		}
	}

	protected function addHeadElement(string $html, ?array &$dataAppend = null): void {
		if(!empty($html)) {
			if(!is_null($dataAppend)) $dataAppend[] = $html;
			else $this->data['headElements'][] = $html;
		}
	}
	protected function preload(string $url): void {
		if(!empty($url)) {
			$preloadData = [];

			$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
			switch($ext) {
				case 'css':
					$preloadData['style'] = ['url' => $url];
					break;
				case 'js':
					$preloadData['script'] = ['url' => $url];
					break;
				case 'otf':
				case 'woff':
				case 'woff2':
					$preloadData['font'] = [
						'url' => $url,
						'crossorigin' => true,
						'type' => 'font/'.$ext,
					];
					break;
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'gif':
				case 'webp':
				case 'bmp':
				case 'tif':
				case 'svg':
					$preloadData['image'] = ['url' => $url];
					break;
			}

			foreach($preloadData as $preloadType => $preloadItem) {
				$this->addHeadElement('<link rel="preload"'.(!empty($preloadItem['type']) ? ' type="'.$preloadItem['type'].'"' : '').' href="'.str_replace(DS, '/', $preloadItem['url']).'" as="'.$preloadType.'"'.(!empty($preloadItem['crossorigin']) && $preloadItem['crossorigin'] ? " crossorigin" : "").'>');
			}
		}
	}

}
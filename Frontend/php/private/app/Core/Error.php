<?php
namespace Webapp\Core;

class Error extends \Exception {

	public function __toString() {
		return "Fehler: \"".$this->getMessage()."\". (Datei: ".$this->getFile().", Zeile: ".$this->getLine().")";
	}

	public function render() {
		try {
			if(!empty($this->getCode()) && $this->getCode()>=200 && $this->getCode()<600) http_response_code($this->getCode());

			// language
			setlocale(LC_ALL, Config::get('languageCodes')[Router::getLanguage()].'.utf-8');
			Lang::load(Router::getLanguage());

			$controllerClass = new (CONTROLLERNS.ucfirst(Config::get('defaultController')).Config::get('defaultControllerSuffix'));
			$controllerObjectData = $controllerClass->getData();

			// darkmode
			$controllerObjectData['darkmode'] = Config::get('darkmodeCheck');
			if($controllerObjectData['darkmode']) {
				$controllerObjectData['darkmodeActive'] = (isset($_COOKIE['darkmode']) && $_COOKIE['darkmode']=='true' ? true : false);
			} else if(isset($_COOKIE['darkmode']) && $_COOKIE['darkmode']=='true') {
				setcookie("darkmode", false, -1);
			}

			$controllerObjectData['user'] = Router::getUser()->data();
			$controllerObjectData['lang'] = Router::getLanguage();
			$controllerObjectData['titel'] = __('error.title', '')." ".$this->getCode()." ".__(Router::getLayout().'.'.'siteTitlePrefix')." ".__(Router::getLayout().'.'.'siteTitle');
			$controllerObjectData['beschreibung'] = __(Router::getLayout().'.'.'siteDescription');
			$controllerObjectData['keywords'] = __(Router::getLayout().'.'.'siteKeywords');
			$controllerObjectData['googleAnalytics'] = Config::get('googleAnalytics');
			$controllerObjectData['adblockChecker'] = Config::get('adblockChecker');
			$controllerObjectData['content'] = $this->html($this->getMessage());
			$layout = Config::get('defaultErrorLayout');
			$layout = !empty($layout) && is_file(VIEWDIR.DS.$layout.'.php') ? $layout : Router::getLayout();
			$layoutPath = VIEWDIR.DS.$layout.'.php';
			$layoutViewObject = new View($layoutPath);
			echo $layoutViewObject->render($controllerObjectData);
		} catch(Error $error) {
			echo "<pre>";
			echo $error->getCode().": ".$error->getMessage()."<br><br>";
			$trace = $error->getTrace();
			array_unshift($trace, [
				'file' => $error->getFile(),
				'line' => $error->getLine(),
			]);
			print_r($trace);
			echo "</pre>";
		}
	}

	public static function html($string = "", $classes = "error-bg") {
		$icon = 'error';
		if(strpos($classes, 'success') !== false) $icon = 'check_circle';
		if(strpos($classes, 'info') !== false) $icon = 'info';
		if(strpos($classes, 'help') !== false) $icon = 'help';
		if(strpos($classes, 'mail') !== false) $icon = 'mail';
		return "<div class='".$classes."'><div class='inner no-padding'><i class='material-symbols-rounded filled'>".$icon."</i><span>".$string."</span></div></div>";
	}

	public static function json(string $msg = "", array $data = [], string $type = "error", int $status = 500, bool $returnEncoded = true) {
		$response = [
			'type' => $type,
			'msg' => $msg,
			'status' => $status
		];
		if(!empty($data)) $response['data'] = $data;
		return ($returnEncoded ? json_encode($response) : $response);
	}

}
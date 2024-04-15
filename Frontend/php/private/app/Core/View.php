<?php
namespace Webapp\Core;

class View {
	
	private 	$path,
				$data = [];

	function __construct($path = null) {
		if(is_null($path)) $path = self::getDefaultViewPath();
		if(!is_file($path)) {
			Router::setNotFound();
			return;
		}
		$this->path = $path;
	}

	private static function getDefaultViewPath() {
		$viewName = Router::getFilename().'.php';
		return VIEWDIR.DS.$viewName;
	}
	
	public static function getPath($filename = "") {
		if(!empty($filename) && is_file(VIEWDIR.DS.Router::getController().DS.$filename.".php")) {
			return VIEWDIR.DS.Router::getController().DS.$filename.".php";
		}
		return "";
	}

	public function render($data = []) {
		$this->data = $data;
		$content = "";

		if(is_file($this->path)) {
			ob_start();
			require_once $this->path;
			$content = ob_get_clean();
		}

		return $content;
	}

	public function getCss() {
		$fileName = str_replace(Router::getLanguage().DS, "", Router::getFilename());
		$cssName = $fileName.'.css';
		return is_file(CSSDIR.DS.$cssName) ? str_replace(DS, "/", CSSDIR_PUBLIC.DS.$cssName) : "";
	}

	public function getJs() {
		$fileName = str_replace(Router::getLanguage().DS, "", Router::getFilename());
		$jsName = $fileName.'.js';
		return is_file(JSDIR.DS.$jsName) ? str_replace(DS, "/", JSDIR_PUBLIC.DS.$jsName) : "";
	}

}

<?php
namespace Webapp\Lib;

use Webapp\Core\Error;
use Webapp\Core\Request;

class ResourceManager {

	protected static string $mediaType;
	protected static string $mediaPath;
	protected static bool $notFound = true;
	protected static array $mediaTypes = [
		'json' => 'application/json',
		'woff2' => 'font/woff2',
		'css' => 'text/css',
		'js' => 'text/javascript',
	];

	public static function run(): void {
		$requestUrl = Request::getInstance()->getGet()['url'] ?? "";
		if(!empty($requestUrl)) {
			$nodeModule = static::isNodeModule($requestUrl, true);
			if($nodeModule) {
				static::outputResource($nodeModule);
			} else throw new Error('Diese Ressource wurde nicht auf diesem Server gefunden.', 404);
		}
	}

	public static function outputResource(array $module) {
		$content = @file_get_contents(NODEMODULES.DS.$module['package_name'].DS.$module['request_path_minfied']) ?? "";
		$content = (empty($content) ? file_get_contents(NODEMODULES.DS.$module['package_name'].DS.$module['request_path']) : $content);
		foreach($module['headers'] as $key => $value) {
			header($key.": ".$value);
		}
		echo $content;
		exit;
	}

	public static function isNodeModule(string $url, bool $returnModule = false): array|bool {
		$nodeModule = static::resolveNodeModule($url);
		if($nodeModule) {
			$isFile = is_file($nodeModule['path']);
			return ($returnModule ? ($isFile ? $nodeModule : false) : $isFile);
		}
		return false;
	}

	private static function resolveNodeModule(string $url): array|false {
		$nodeModule = [
			'headers' => [
				'Content-Type' => "",
			]
		];
		$matches = [];
		$pattern = '/('.implode('|', array_keys(static::$mediaTypes)).')\/([\w\d\/\-\_\.]+\.('.implode('|', array_keys(static::$mediaTypes)).'))/';
		if(preg_match($pattern, $url, $matches)) {
			$pathParams = explode("/", $matches[0]);
			$mediaType = pathinfo($matches[2])['extension'];
			$nodeModule['headers']['Content-Type'] = static::$mediaTypes[$mediaType];

			$nodeModule['package_name'] = str_replace('.'.$mediaType, "", $pathParams[1]);
			array_shift($pathParams);
			array_shift($pathParams);

			$packageConfig = json_decode(@file_get_contents(NODEMODULES.DS.$nodeModule['package_name'].DS.'package.json') ?? '[]', true);
			
			$packageMainFilePath = ($mediaType == "css" ? $packageConfig['style'] ?? $packageConfig['main'] ?? '' : $packageConfig['main'] ?? '');
			
			if(!empty($pathParams) && $pathParams[0] == 'jsdelivr' && $mediaType == "js") {
				$packageMainFilePath = $packageConfig['jsdelivr'];
				array_shift($pathParams);
			}

			$packageMainFileName = basename($packageMainFilePath);
			$packageDistDirectory = str_replace($packageMainFileName, '', $packageMainFilePath);
			$nodeModule['request_path'] = (!empty($packageDistDirectory) ? $packageDistDirectory : "").(!empty($pathParams) ? implode(DS, $pathParams) : $packageMainFileName);
			$nodeModule['request_path_minified'] = (strpos($nodeModule['request_path'], '.min.') === false && in_array($mediaType, ['js', 'css']) ? str_replace('.'.$mediaType, '.min.'.$mediaType, $nodeModule['request_path']) : $nodeModule['request_path']);
			$nodeModule['path'] = is_file(NODEMODULES.DS.$nodeModule['package_name'].DS.$nodeModule['request_path_minified']) ? NODEMODULES.DS.$nodeModule['package_name'].DS.$nodeModule['request_path_minified'] : (is_file(NODEMODULES.DS.$nodeModule['package_name'].DS.$nodeModule['request_path']) ? NODEMODULES.DS.$nodeModule['package_name'].DS.$nodeModule['request_path'] : "");
			$nodeModule['filemtime'] = !empty($nodeModule['path']) ? filemtime($nodeModule['path']) : 0;

			switch ($mediaType) {
				case 'woff2':
					$nodeModule['headers']['Accept-Ranges'] = 'bytes';
					break;
				default:
					break;
			}

			return $nodeModule;
		}
		
		return false;
	}

	public static function resolve(): void {
		$requestUrl = Request::getInstance()->getGet()['url'] ?? "";

		if(empty($requestUrl)) throw new Error("Diese Ressource wurde nicht auf diesem Server gefunden.", 404);

		$pattern = "/(".implode("|", array_keys(static::$mediaTypes)).")\/([\w\d\/\-\_\.]+\.(".implode("|", array_keys(static::$mediaTypes))."))/";
		$matches = [];
		
		if(preg_match($pattern, $requestUrl, $matches)) {
			$fileType = pathinfo($matches[2])['extension'];
			$pathParams = explode("/", $matches[0]);
			static::$mediaType = static::$mediaTypes[$fileType] ?? "";
	
			$packageName = str_replace('.'.$fileType, "", $pathParams[1]);
			$packagePath = NODEMODULES.DS.$packageName;
			unset($pathParams[0]);
			unset($pathParams[1]);

			if(is_dir($packagePath)) {
				$packageJson = @file_get_contents($packagePath.DS."package.json");
				$packageJson = json_decode($packageJson ?? "[]", true);
	
				if(!empty($packageJson)) {
					if($fileType == "css") {
						$main = $packageJson['style'] ?? $packageJson['main'] ?? "";
					} else {
						$main = $packageJson['main'] ?? "";
					}
					if(!empty($main)) {
						$mainFileName = basename($main);
						$mainFilePath = (
							!empty($pathParams)
							? $packagePath.DS.str_replace($mainFileName, implode(DS, $pathParams), $main)
							: $packagePath.DS.$main
						);
					} else {
						$mainFilePath = !empty($pathParams) ? $packagePath.DS.implode(DS, $pathParams) : $packagePath;
					}
					$mainFilePathMinified = str_replace('.'.$fileType, ".min.".$fileType, $mainFilePath);
					static::$mediaPath = (is_file($mainFilePathMinified) ? $mainFilePathMinified : $mainFilePath);
					static::$notFound = !is_file(static::$mediaPath);
				}
			}
		}
		if(static::$notFound) throw new Error("Diese Ressource wurde nicht auf diesem Server gefunden.", 404);
		static::returnMedia();
		return;
	}

	public static function returnMedia() {
		$content = file_get_contents(static::$mediaPath);
		if(static::$mediaType=='font/woff2') header("Accept-Ranges: bytes");
		header("Content-Type: ".static::$mediaType);
		if(!in_array(static::$mediaType, ['font/woff2', 'application/json'])) header("Content-Length: ".mb_strlen($content));
		echo $content;
		exit;
	}
}
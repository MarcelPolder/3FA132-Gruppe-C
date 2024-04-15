<?php
namespace Webapp\Lib;

class Updater {
	private static string $url = "http://update.rossamedia.de/";

	public static function update() {
		$versionConfig = self::getCurrentVersion();
		$currentVersion = $versionConfig['version'];
		$apiKey = $versionConfig['api_key'];
		$registered = (int) $versionConfig['registered'];
		$lastChecked = (int) $versionConfig['last_checked'];

		if(empty($apiKey)) {
			$register = self::send("register", [
				'domain' => HOSTNAME,
				'version' => $currentVersion,
				'api_key' => $apiKey,
			]);
			if(!empty($register) && $register['status']) {
				if($register['action']=='register') {
					$apiKey = $register['api_key'];
					$versionConfig['api_key'] = $apiKey;
					$versionConfig['registered'] = time();
					$versionConfig['last_checked'] = time();
					self::setCurrentVersion($versionConfig);
				}
			}
		} else if((time()-$lastChecked)>86400) {
			$check = self::send("check", [
				'domain' => HOSTNAME,
				'version' => $currentVersion,
				'api_key' => $apiKey,
			]);
			if(!empty($check) && $check['status'] && !empty($check['download'])) {
				$updated = false;

				$download = $check['download'];
				$update = self::send($download, [
					'domain' => HOSTNAME,
					'api_key' => $apiKey,
				]);
				if(!empty($update) && !empty($update['data'])) {
					$archiveContent = $update['data'];

					$archiveTmpFolder = ROOT.DS.'tmp';
					if(!is_dir($archiveTmpFolder)) mkdir($archiveTmpFolder);
					$archiveFile = $archiveTmpFolder.DS.basename($download);
					file_put_contents($archiveFile, $archiveContent);

					if(is_file($archiveFile)) {
						$zip = new \ZipArchive;
						if($zip->open($archiveFile)) {
							for($i=0; $i<$zip->numFiles; $i++) {
								$file = $zip->getNameIndex($i);
									$file = ROOT.substr($file, strpos($file, '/', 1));
								
								if(is_file($file) && filemtime($file)<=$registered) {
									$fileContent = $zip->getFromIndex($i);
									
									file_put_contents($file, $fileContent);
								}
							}
							$zip->close();
						}

						unlink($archiveFile);
						rmdir($archiveTmpFolder);

						$updated = true;
					}
				}

				if($updated) {
					$versionConfig['version'] = $check['latest'];
					$versionConfig['last_checked'] = time();
					self::setCurrentVersion($versionConfig);

					header("Refresh: 0");
					exit;
				}
			} else {
				$versionConfig['last_checked'] = time();
				self::setCurrentVersion($versionConfig);
			}
		}
	}

	public static function getCurrentVersion(): array {
		return json_decode(@file_get_contents(ROOT.DS.'.version') ?? [], true) ?? [];
	}

	private static function setCurrentVersion($versionConfig): bool {
		return (bool) file_put_contents(ROOT.DS.'.version', json_encode($versionConfig, JSON_PRETTY_PRINT));
	}

	private static function send($url, $data = []): array {
		$return = [];

		if(!empty($url)) {
			if(strpos($url, self::$url)===false && strpos($url, DS)!==0) $url = self::$url.DS.$url;
			if(strpos($url, self::$url)===false) $url = self::$url.$url;

			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => [
					'Accept: application/json,application/zip,application/octet-stream',
				],
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $data,
			]);
			$response = curl_exec($curl);
			curl_close($curl);

			if(!empty($response)) {
				$return = json_decode($response, true);
				if(empty($return)) $return = $response;
			}
		}

		return (!empty($return) ? (is_array($return) ? $return : [
			'data' => $return,
		]) : []);
	}
}
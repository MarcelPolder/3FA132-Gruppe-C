<?php
namespace Webapp\Lib;

use Webapp\Core\Error;

class MediaManager {

	/**
	 * Get the maximum file count that can be uploaded at once.
	 *
	 * @return int
	 */
	public static function getMaxFiles(): int {
		return ini_get('max_file_uploads');
	}

	/**
	 * Get the maximum file size from configuration.
	 *
	 * @return int
	 */
	public static function getMaxFileSize(): int {
		$maxFileSize = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
		if(strpos($maxFileSize, 'G')!==false) {
			$maxFileSize = (int) $maxFileSize * 1024 * 1024 * 1024;
		} else if(strpos($maxFileSize, 'M')!==false) {
			$maxFileSize = (int) $maxFileSize * 1024 * 1024;
		} else if(strpos($maxFileSize, 'K')!==false) {
			$maxFileSize = (int) $maxFileSize * 1024;
		} else {
			$maxFileSize = (int) $maxFileSize;
		}
		return $maxFileSize;
	}

	/**
	 * Upload files to a directory while specifying the name, allowed mime types and file extensions or image properties.
	 *
	 * @param array $files
	 * @param string $uploadDir
	 * @param array $fileName
	 * @param array $allowedTypes
	 * @param int $imageMaxWidth
	 * @param int $jpgQuality
	 * @return array
	 */
	public static function upload(array $files, string $uploadDir, array $fileName = [], $overrideFileIfExists = true, $allowedTypes = [
		'jpg' => 'image/jpg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
	], $imageMaxWidth = 1200, $jpgQuality = 80, $ignoreImageDetection = false): array {
		$response['status'] = true;
		
		$maxFiles = self::getMaxFiles();
		$maxSize = self::getMaxFileSize();

		if(strpos($uploadDir, FRONTEND)===false) $uploadDir = FRONTEND.DS.$uploadDir;

		if(!is_dir($uploadDir)) {
			$oldMask = umask(0);
			$createDir = mkdir($uploadDir, 0750, true);
			umask($oldMask);
			if(!$createDir) {
				$response['status'] = false;
				$response['error'] = Error::json("Das Verzeichnis zum Hochladen der Dateien konnte nicht erstellt werden.", status: 409, returnEncoded: false);
				return $response;
			}
		}

		if(!empty($files) && count($files)<=$maxFiles) {
			$sumFileSize = 0;
			foreach($files as $key => $file) {
				if($file['error']==UPLOAD_ERR_OK) $sumFileSize += $file['size'];
			}
			if($sumFileSize > $maxSize) {
				$response['status'] = false;
				$response['error'] = Error::json("Die Dateien sind insgesamt zu groß. (Maximal: ".($maxSize/1024/1024)." MB)", status: 409, returnEncoded: false);
			} else {
				foreach($files as $key => $file) {
					$name = $file['name'];
					$tmp_name = $file['tmp_name'];
					$type = $file['type'];
					$error = $file['error'];
					$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
					if($error!=UPLOAD_ERR_OK) {
						$response['status'] = false;
						$response['error'][$key] = Error::json("Die Datei ".$name." ist beschädigt. Bitte verusche es erneut.", status: 409, returnEncoded: false);
						continue;
					}
					if(!in_array($type, array_values($allowedTypes)) || !in_array($ext, array_keys($allowedTypes))) {
						$response['status'] = false;
						$response['error'][$key] = Error::json("Die Datei ".$name." hat kein erlaubtes Dateiformat.", status: 409, returnEncoded: false);
						continue;
					}
					if(!file_exists($tmp_name)) {
						$response['status'] = false;
						$response['error'][$key] = Error::json("Die Datei ".$name." ist nicht vorhanden. Bitte verusche es erneut.", status: 409, returnEncoded: false);
						continue;
					}

					// upload
					$uploadFileName = DS.trim($uploadDir, DS).DS.(!empty($fileName) && !empty($fileName[$key]) ? basename($fileName[$key], '.'.$ext) : date("YmdHis")."_".$key."_".mt_rand(10000,99999)).".".$ext;
					if(is_file($uploadFileName) && !$overrideFileIfExists) {
						$uploadFileName = str_replace('.'.$ext, (!empty($fileName) && !empty($fileName[$key]) ? '-' : '_').mt_rand(10000,99999).'.'.$ext, $uploadFileName);
					}

					if(!$ignoreImageDetection && getimagesize($tmp_name)!==false) {
						list($bild_w_orig, $bild_h_orig) = getimagesize($tmp_name);
						if(!empty($imageMaxWidth)) {
							$bild_max_wh = $imageMaxWidth;
							if($bild_w_orig > $bild_h_orig) {
								if($bild_w_orig > $bild_max_wh) {
									$bild_w = $bild_max_wh;
									$bild_h = ($bild_h_orig / $bild_w_orig) * $bild_w;
								} else {
									$bild_w = $bild_w_orig;
									$bild_h = $bild_h_orig;
								}
							} elseif($bild_h_orig >= $bild_w_orig) {
								if($bild_h_orig > $bild_max_wh) {
									$bild_h = $bild_max_wh;
									$bild_w = ($bild_w_orig / $bild_h_orig) * $bild_h;
								} else {
									$bild_h = $bild_h_orig;
									$bild_w = $bild_w_orig;
								}
							}
						} else {
							$bild_w = $bild_w_orig;
							$bild_h = $bild_h_orig;
						}
						if($ext=='jpg' || $ext=='jpeg') {
							$src = imagecreatefromjpeg($tmp_name);
							if($src!==false) {
								$tmp = imagecreatetruecolor($bild_w, $bild_h);
								imagecopyresampled($tmp, $src, 0, 0, 0, 0, $bild_w, $bild_h, $bild_w_orig, $bild_h_orig);
								imagejpeg($tmp, $uploadFileName, $jpgQuality);

								imagedestroy($src);
								imagedestroy($tmp);
							} else {
								$response['status'] = false;
								$response['error'][$key] = Error::json("Die Datei ".$name." konnte nicht hochgeladen werden.");
								continue;
							}
						} else if($ext=='png') {
							$src = imagecreatefrompng($tmp_name);
							if($src!==false) {
								imagealphablending($src, true);
								$tmp = imagecreatetruecolor($bild_w, $bild_h);
								imagealphablending($tmp, false);
								imagesavealpha($tmp, true);
								imagecopyresampled($tmp, $src, 0, 0, 0, 0, $bild_w, $bild_h, $bild_w_orig, $bild_h_orig);
								imagepng($tmp, $uploadFileName);

								imagedestroy($src);
								imagedestroy($tmp);
							} else {
								$response['status'] = false;
								$response['error'][$key] = Error::json("Die Datei ".$name." konnte nicht hochgeladen werden.");
								continue;
							}
						}
					} else {
						if(!move_uploaded_file($tmp_name, $uploadFileName)) {
							$response['status'] = false;
							$response['error'][$key] = Error::json("Die Datei ".$name." konnte nicht hochgeladen werden.");
						}
					}

					if($response['status']) {
						$response['files'][$key] = [
							'path' => $uploadFileName,
							'name' => basename($uploadFileName),
						];
					}
				}
			}
			return $response;
		}
		return ['status' => false, 'error' => ['Keine oder zu viele Datei(en) angegeben. (Maximal: '.$maxFiles.')']];
	}

	/**
	 * Generate a base64 string or an base64 array from an array of files.
	 *
	 * @param array $files
	 * @param bool $prependBase64DataString
	 * @return string|array|false
	 */
	public static function image2base64(array $files = [], bool $prependBase64DataString = true): string|array|false {
		if(!empty($files)) {
			$return = null;
			if(is_array($files)) {
				if(count($files)>1) {
					foreach($files as $file) {
						if(strpos($file['type'], "image/")===false) return false;

						if(is_file($file['tmp_name'])) $return[] = ($prependBase64DataString ? "data:".$file['type'].";base64," : "").base64_encode(file_get_contents($file['tmp_name']));
					}
				} else if(strpos($files[0]['type'], "image/")!==false) {
					if(is_file($files[0]['tmp_name'])) $return = ($prependBase64DataString ? "data:".$files[0]['type'].";base64," : "").base64_encode(file_get_contents($files[0]['tmp_name']));
				}
			} else if(is_file($files['tmp_name']) && strpos($files['type'], "image/")!==false) {
				$return = ($prependBase64DataString ? "data:".$files['type'].";base64," : "").base64_encode(file_get_contents($files['tmp_name']));
			}
			return $return;
		}
		return false;
	}

	/**
	 * Returns an array of files (and directories) in a given path. The Path must be a subdirectory of the servers document root.
	 *
	 * @param string $path
	 * @param array $allowed_extensions
	 * @param bool $allow_directories
	 * @param \Closure|null $custom_check
	 * @return array|false
	 */
	public static function readFilesInDirectory(string $path, array $allowed_extensions = [], bool $allow_directories = true, \Closure $custom_check = null): array|false {
		if(strpos($path, FRONTEND)===false) $path = FRONTEND.DS.trim($path, DS);
		if(substr($path, -1)==DS) $path = rtrim($path, DS);
		if(!empty($path) && is_dir($path) && strpos($path, "..")===false) {
			$ordner = $path;
			$bilder = [];
			foreach(scandir($ordner) as $key => $bild) {
				if($bild=='.' || $bild=='..') continue;
				else {
					$ext = strtolower(pathinfo($ordner.DS.$bild, PATHINFO_EXTENSION));
					$isDir = (is_dir($ordner.DS.$bild) ? true : false);
					if(empty($allowed_extensions) || in_array($ext, $allowed_extensions) || ($allow_directories && $isDir)) {
						$bilder[$key] = [];
						$bilder[$key]['name'] = $bild;
						$bilder[$key]['path'] = $ordner.DS.$bild;
						$bilder[$key]['public'] = str_replace(FRONTEND, "", $ordner).DS.$bild;
						$bilder[$key]['is_dir'] = $isDir;
						if(!is_null($custom_check)) {
							$custom_attributes = $custom_check($bild, $key);
							if(!empty($custom_attributes)) {
								if(is_array($custom_attributes)) $bilder[$key] = array_merge($bilder[$key], $custom_attributes);
								else $bilder[$key][] = $custom_attributes;
							}
						}
					}
				}
			}
			if($allow_directories) usort($bilder, fn($bild) => (int) (!$bild['is_dir']));
			return $bilder;
		}
		return false;
	}	
}
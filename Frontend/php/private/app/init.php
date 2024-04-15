<?php
use Webapp\Core\Config;
use Webapp\Core\Session;

require_once VENDORDIR.DS.'autoload.php';
require_once CONFIGDIR.DS.'Config.php';

// Definitions - Paths
define('SCHEME', (Config::get('forceHttps') || isset($_SERVER['HTTPS']) ? 'https://' : 'http://'));
define('URL', SCHEME.($_SERVER['SERVER_NAME'] ?? ""));
define('HOSTNAME', parse_url(URL)['host'] ?? "");
define('SUBDOMAIN', (substr_count(HOSTNAME, ".")>1 ? strstr(HOSTNAME, ".", true) : ""));
define('DOMAIN', str_replace((!empty(SUBDOMAIN) ? SUBDOMAIN."." : ""), "", HOSTNAME));
define('TLD', substr(strstr(substr(HOSTNAME, -10), "."), 1));
define('FRONTEND', ROOT.DS.'public');
define('CSSDIR', FRONTEND.DS.'css');
define('CSSDIR_PUBLIC', DS.'css');
define('JSDIR', FRONTEND.DS.'js');
define('JSDIR_PUBLIC', DS.'js');
define('IMGDIR', FRONTEND.DS.'img');
define('IMGDIR_PUBLIC', DS.'img');

// Session
$sessionConfig = [
	'name' => 'SID',
	'cookie_httponly' => true,
	'cookie_samesite' => 'Lax',
	'sid_length' => 48,
];
if(Config::get('forceHttps') || isset($_SERVER['HTTPS'])) $sessionConfig['cookie_secure'] = true;
if(Config::get('sessionEnabled')) {
	session_start($sessionConfig);
} else if (!empty(Session::getCookie('SID'))) {
	session_start($sessionConfig);
	$params = session_get_cookie_params();
	setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	session_destroy();
}

// Error Handler
if(Config::get('dev')) {
	$whoopsRun = new \Whoops\Run;
	if (\Whoops\Util\Misc::isAjaxRequest()) {
		$whoopsRun->pushHandler(new \Whoops\Handler\JsonResponseHandler);
	} else {
		$whoopsHandler = new \Whoops\Handler\PrettyPageHandler;
		$whoopsHandler->setPageTitle("Whoops! Da ist uns wohl ein Fehler unterlaufen...");
		$whoopsHandler->setEditor('vscode');
		$whoopsRun->pushHandler($whoopsHandler);
	}
	$whoopsRun->register();
}

// Functions
function __($key, $defaultValue = '') {
	return \Webapp\Core\Lang::get($key, $defaultValue);
}

function rURL() {
	return \Webapp\Core\Router::getRootUrl();
}

function createImg(string $img = null, $altTag = "", $titleTag = "", $width="", $height="", $lazyLoading = true) : string {
	if(!is_null($img)) {
		return '<picture>
			<img src="'.$img.(Config::get('dev') ? "?t=".mt_rand(10000,99999) : "").'"'.(!empty($altTag) ? ' alt="'.$altTag.'"' : '').(!empty($titleTag) ? ' title="'.$titleTag.'"' : '').(!empty($width) ? '  width="'.$width.'"' : '').(!empty($height) ? '  height="'.$height.'"' : '').($lazyLoading ? '  loading="lazy"' : '').'>
		</picture>';
	} else {
		return "";
	}
}
function createColorSchemeImg(string $lightmodeImg = null, string $darkmodeImg = null, bool $darkmode = false, bool $isDarkmode = null, $altTag = "", $titleTag = "", $width="", $height="", $lazyLoading = true) : string {
	if(!is_null($lightmodeImg) && !is_null($darkmodeImg) && !is_null($isDarkmode)) {
		return '<picture>
			'.($darkmode ? '<source srcset="'.$darkmodeImg.(Config::get('dev') ? "?t=".mt_rand(10000,99999) : "").'" media="(prefers-color-scheme: dark)">
			<source srcset="'.$lightmodeImg.(Config::get('dev') ? "?t=".mt_rand(10000,99999) : "").'" media="(prefers-color-scheme: light)">' : '').'
			<img src="'.($isDarkmode ? $darkmodeImg : $lightmodeImg).(Config::get('dev') ? "?t=".mt_rand(10000,99999) : "").'"'.(!empty($altTag) ? ' alt="'.$altTag.'"' : '').''.(!empty($titleTag) ? ' title="'.$titleTag.'"' : '').' data-color-scheme-switch-src="'.($isDarkmode ? $lightmodeImg : $darkmodeImg).'"'.(!empty($width) ? '  width="'.$width.'"' : '').(!empty($height) ? '  height="'.$height.'"' : '').($lazyLoading ? ' loading="lazy"' : '').'>
		</picture>';
	} else {
		return "";
	}
}
function createColorSchemeToggle(bool $isLightmode = null) : string {
	if(!is_null($isLightmode)) {
		return '<div class="theme-toggle">
			<i class="material-symbols-rounded'.($isLightmode ? " active" : "").'">dark_mode</i>
			<input type="checkbox" class="theme-toggle-cbx"'.($isLightmode ? " checked" : "").'>
			<label for="theme-toggle-cbx" class="theme-toggle-switch"></label>
			<i class="material-symbols-rounded'.($isLightmode ? "" : " active").'">light_mode</i>
		</div>';
	} else {
		return "";
	}
}

/**
 * Reorder an $_FILES array.
 *
 * @param array $files
 * @return array|false
 */
function filesArrayOrder(array $files = []): array|false {
	if(!empty($files)) {
		$return = [];
		foreach($files as $fileInputName => $fileArray) {
			if(!empty($fileArray['name'])) {
				$isMultidimensional = is_array($fileArray['name']);
				if(!$isMultidimensional) {
					$fileArray['name'] = basename($fileArray['name']);
					$return[$fileInputName] = [$fileArray];
					continue;
				}
				$keys = array_keys($fileArray);
				foreach($fileArray['name'] as $key => $name) {
					if(empty($name)) continue;
					$fileArray['name'][$key] = basename($fileArray['name'][$key]);
					foreach($keys as $keyName) {
						$return[$fileInputName][$key][$keyName] = $fileArray[$keyName][$key];
					}
				}
			}
		}
		return $return;
	}
	return false;
}

function dump($value = null) {
	$request = \Webapp\Core\Request::getInstance();
	if(!empty($value)) {
		if(!$request->isAjax()) echo "<div class='dump'><pre>";
		print_r($value);
		if(!$request->isAjax()) echo "</pre></div>";
	} else {
		if(!$request->isAjax()) echo "<div class='dump'><pre>";
		var_dump($value);
		if(!$request->isAjax()) echo "</pre></div>";
	}
}
function dd($value = null) {
	dump($value);
	die();
}
function ddd($value = null) {
	throw new \Exception($value);
}

function get_httpResponseCode(string $url, bool $returnHeaders = false) : int|array {
	$context = stream_context_create([
		'http' => [
			'method' => 'GET',
			'max_redirects' => 5,
			'User-Agent: '.DOMAIN,
		],
	]);
	$headers = @get_headers($url, true, context: $context);

	if(!empty($headers) && preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $headers[0], $response)) {
		$httpStatusCode = intval($response[1]);
	} else $httpStatusCode = 0;

	return $returnHeaders ? [
		'code' => $httpStatusCode,
		'headers' => $headers,
	] : $httpStatusCode;
}

function checkUrl($url = "") {
	if(!empty($url)) {
		if(strpos($url, "http")!==0) {
			$urlNoSSL = "http://".$url;
			$url = "https://".$url;
		}

		$httpStatus = get_httpResponseCode($url, true);
		if(!empty($httpStatus) && $httpStatus['code']===0) {
			$httpStatus = get_httpResponseCode($urlNoSSL, true);
			$url = $urlNoSSL;
		}

		if($httpStatus['code'] >= 200 && $httpStatus['code'] <= 299) return $url;

		if(!isset($httpStatus['headers']['Location'])) $httpStatus['headers']['Location'] = [$url];
		if(!is_array($httpStatus['headers']['Location'])) $httpStatus['headers']['Location'] = [$httpStatus['headers']['Location']];

		if(($httpStatus['code'] == 301) || ($httpStatus['code'] == 302)) {
			$last_location = $httpStatus['headers']['Location'][count($httpStatus['headers']['Location'])-1];
			$httpfind = false;
			foreach(array_reverse($httpStatus['headers']['Location']) as $loc) {
				if(strpos($loc, 'http') === 0) {
					$httpfind = true;
					if($last_location != $loc) $last_location = rtrim($loc,'/') .'/'. ltrim($last_location, '/');				
					break;
				}
			}
			if(!$httpfind) $last_location = rtrim($url,'/') .'/'. ltrim($last_location, '/');
		} else {
			$last_location = "";
		}

		return $last_location;
	}
}

function isJson($string) {
	@json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}
function isSerialized($string) {
	return (@unserialize($string) !== false || $string == 'b:0;');
}

/**
 * Checks if keys are included in an array.
 *
 * @param array|string $keys
 * @param array $array
 * @return bool
 */
function array_keys_exists(array|string $keys, array $array): bool {
	if(is_string($keys)) $keys = [$keys];
	return !array_diff_key(array_flip($keys), $array);
}

/**
 * Gibt ein formatiertes Datum inkl. Zeit in der eingestellten Sprache zurück.
 *
 * @param string $pattern
 * @param string $dateFormat
 * @param string $timeFormat
 * @param string $timestamp
 * @param string $language
 * @return String|false
 * 
 * # Pattern
 * 
 * | Symbol | Meaning | Pattern | Example Output |
|:---:|:---|:---|:---|
| G | era designator | G, GG, or GGG GGGG GGGGG | AD Anno Domini A |
| y | year | yy y or yyyy | 96 1996 |
| Y | year of “Week of Year” | Y | 1997 |
| u | extended year | u | 4601 |
| U | cyclic year name, as in Chinese lunar calendar | U | 甲子 |
| r | related Gregorian year | r | 1996 |
| Q | quarter | Q QQ QQQ QQQQ QQQQQ | 2 02 Q2 2nd quarter 2 |
| q | stand-alone quarter | q qq qqq qqqq qqqqq | 2 02 Q2 2nd quarter 2 |
| M | month in year | M MM MMM MMMM MMMMM | 9 09 Sep September S |
| L | stand-alone month in year | L LL LLL LLLL LLLLL | 9 09 Sep September S |
| w | week of year | w ww | 27 27 |
| W | week of month | W | 2 |
| d | day in month | d dd | 2 02 |
| D | day of year | D | 189 |
| F | day of week in month | F | 2 (2nd Wed in July) |
| g | modified julian day | g | 2451334 |
| E | day of week | E, EE, or EEE EEEE EEEEE EEEEEE | Tue Tuesday T Tu |
| e | local day of week example: if Monday is 1st day, Tuesday is 2nd ) | e or ee eee eeee eeeee eeeeee | 2 Tue Tuesday T Tu |
| c | stand-alone local day of week | c or cc ccc cccc ccccc cccccc | 2 Tue Tuesday T Tu |
| a | am/pm marker | a | pm |
| h | hour in am/pm (1~12) | h hh | 7 07 |
| H | hour in day (0~23) | H HH | 0 00 |
| k | hour in day (1~24) | k kk | 24 24 |
| K | hour in am/pm (0~11) | K KK | 0 00 |
| m | minute in hour | m mm | 4 04 |
| s | second in minute | s ss | 5 05 |
| S | fractional second - truncates (like other time fields) to the count of letters when formatting. Appends zeros if more than 3 letters specified. Truncates at three significant digits when parsing. | S SS SSS SSSS | 2 23 235 2350 |
| A | milliseconds in day | A | 61201235 |
| z | Time Zone: specific non-location | z, zz, or zzz zzzz | PDT Pacific Daylight Time |
| Z | Time Zone: ISO8601 basic hms? / RFC 822 Time Zone: long localized GMT (=OOOO) TIme Zone: ISO8601 extended hms? (=XXXXX) | Z, ZZ, or ZZZ ZZZZ ZZZZZ | -0800 GMT-08:00 -08:00, -07:52:58, Z |
| O | Time Zone: short localized GMT Time Zone: long localized GMT (=ZZZZ) | O OOOO | GMT-8 GMT-08:00 |
| v | Time Zone: generic non-location (falls back first to VVVV) | v vvvv | PT Pacific Time or Los Angeles Time |
| V | Time Zone: short time zone ID Time Zone: long time zone ID Time Zone: time zone exemplar city Time Zone: generic location (falls back to OOOO) | V VV VVV VVVV | uslax America/Los_Angeles Los Angeles Los Angeles Time |
| X | Time Zone: ISO8601 basic hm?, with Z for 0 Time Zone: ISO8601 basic hm, with Z Time Zone: ISO8601 extended hm, with Z Time Zone: ISO8601 basic hms?, with Z Time Zone: ISO8601 extended hms?, with Z | X XX XXX XXXX XXXXX | -08, +0530, Z -0800, Z -08:00, Z -0800, -075258, Z -08:00, -07:52:58, Z |
| x | Time Zone: ISO8601 basic hm?, without Z for 0 Time Zone: ISO8601 basic hm, without Z Time Zone: ISO8601 extended hm, without Z Time Zone: ISO8601 basic hms?, without Z Time Zone: ISO8601 extended hms?, without Z | x xx xxx xxxx xxxxx | -08, +0530 -0800 -08:00 -0800, -075258 -08:00, -07:52:58 |
| ' | escape for text | ' | (nothing) |
| ' ' | two single quotes produce one | ' ' | ’ |
 * 
 * _Note: Any characters in the pattern that are not in the ranges of [‘a’..’z’] and [‘A’..’Z’] will be treated as quoted text. For instance, characters like ':', '.', ' ', '#' and '@' will appear in the resulting time text even they are not enclosed within single quotes. The single quote is used to ‘escape’ letters. Two single quotes in a row, whether inside or outside a quoted sequence, represent a ‘real’ single quote._
 * _Note: A pattern containing any invalid pattern letter results in a failing UErrorCode result during formatting or parsing._
 * 
 * 
 */
function formatTs($pattern = "", $dateFormat = "", $timeFormat = "", $timestamp = "", $language = "") {
	if(empty($timestamp)) $timestamp = time();
	if(empty($dateFormat)) $dateFormat = "medium";
	if(empty($language)) $language = \Webapp\Core\Config::get('languageCodes')[\Webapp\Core\Config::get('defaultLanguage')];

	if(extension_loaded('intl')) {
		switch($dateFormat) {
			case 'full':
				$dateFormat = IntlDateFormatter::FULL;
				break;
			case 'long':
				$dateFormat = IntlDateFormatter::LONG;
				break;
			case 'medium':
				$dateFormat = IntlDateFormatter::MEDIUM;
				break;
			case 'short':
				$dateFormat = IntlDateFormatter::SHORT;
				break;
			default:
				$dateFormat = IntlDateFormatter::NONE;
				break;
		}
		switch($timeFormat) {
			case 'full':
				$timeFormat = IntlDateFormatter::FULL;
				break;
			case 'long':
				$timeFormat = IntlDateFormatter::LONG;
				break;
			case 'medium':
				$timeFormat = IntlDateFormatter::MEDIUM;
				break;
			case 'short':
				$timeFormat = IntlDateFormatter::SHORT;
				break;
			default:
				$timeFormat = IntlDateFormatter::NONE;
				break;
		}
		$formatter = new IntlDateFormatter($language, $dateFormat, $timeFormat);
		if(!empty($pattern)) $formatter->setPattern($pattern);
		return $formatter->format($timestamp);
	} else {
		return "";
	}
}

function escape($value, $url = false) {
	if(is_array($value)) return escapeArr($value);
	if($url) {
		$value = str_replace(
			["À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "ß", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "ÿ", "Ā", "ā", "Ă", "ă", "Ą", "ą", "Ć", "ć", "Ĉ", "ĉ", "Ċ", "ċ", "Č", "č", "Ď", "ď", "Đ", "đ", "Ē", "ē", "Ĕ", "ĕ", "Ė", "ė", "Ę", "ę", "Ě", "ě", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ģ", "ģ", "Ĥ", "ĥ", "Ħ", "ħ", "Ĩ", "ĩ", "Ī", "ī", "Ĭ", "ĭ", "Į", "į", "İ", "ı", "Ĳ", "ĳ", "Ĵ", "ĵ", "Ķ", "ķ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ŀ", "ŀ", "Ł", "ł", "Ń", "ń", "Ņ", "ņ", "Ň", "ň", "ŉ", "Ō", "ō", "Ŏ", "ŏ", "Ő", "ő", "Œ", "œ", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ŝ", "ŝ", "Ş", "ş", "Š", "š", "Ţ", "ţ", "Ť", "ť", "Ŧ", "ŧ", "Ũ", "ũ", "Ū", "ū", "Ŭ", "ŭ", "Ů", "ů", "Ű", "ű", "Ų", "ų", "Ŵ", "ŵ", "Ŷ", "ŷ", "Ÿ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "ſ", "ƒ", "Ơ", "ơ", "Ư", "ư", "Ǎ", "ǎ", "Ǐ", "ǐ", "Ǒ", "ǒ", "Ǔ", "ǔ", "Ǖ", "ǖ", "Ǘ", "ǘ", "Ǚ", "ǚ", "Ǜ", "ǜ", "Ǻ", "ǻ", "Ǽ", "ǽ", "Ǿ", "ǿ", "?"],
			["A", "A", "A", "A", "A", "A", "AE", "C", "E", "E", "E", "E", "I", "I", "I", "I", "D", "N", "O", "O", "O", "O", "O", "O", "U", "U", "U", "U", "Y", "s", "a", "a", "a", "a", "a", "a", "ae", "c", "e", "e", "e", "e", "i", "i", "i", "i", "n", "o", "o", "o", "o", "o", "o", "u", "u", "u", "u", "y", "y", "A", "a", "A", "a", "A", "a", "C", "c", "C", "c", "C", "c", "C", "c", "D", "d", "D", "d", "E", "e", "E", "e", "E", "e", "E", "e", "E", "e", "G", "g", "G", "g", "G", "g", "G", "g", "H", "h", "H", "h", "I", "i", "I", "i", "I", "i", "I", "i", "I", "i", "IJ", "ij", "J", "j", "K", "k", "L", "l", "L", "l", "L", "l", "L", "l", "l", "l", "N", "n", "N", "n", "N", "n", "n", "O", "o", "O", "o", "O", "o", "OE", "oe", "R", "r", "R", "r", "R", "r", "S", "s", "S", "s", "S", "s", "S", "s", "T", "t", "T", "t", "T", "t", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "W", "w", "Y", "y", "Y", "Z", "z", "Z", "z", "Z", "z", "s", "f", "O", "o", "U", "u", "A", "a", "I", "i", "O", "o", "U", "u", "U", "u", "U", "u", "U", "u", "U", "u", "A", "a", "AE", "ae", "O", "o", ""],
			$value
		);
		$value = str_replace(
			[
				"&bdquo;",
				"&rdquo;",
				"&ldquo;",
				"&lsquo;",
				"&auml;",
				"&ouml;",
				"&uuml;",
				"&szlig;",
				"&euro;",
				"&amp;",
				"&lt;",
				"&gt;",
				"&quot;",
				"&copy;",
				"&bull;",
				"&trade;",
				"&reg;",
				"&sect;",
				"&ndash;",
				"&mdash;",
				" - ",
				" – ",
				":",
				".",
				",",
				"_",
				"(",
				")",
				"|",
				";",
				"„",
				"”",
				"“",
				"‘",
				"ä",
				"ö",
				"ü",
				"ß",
				"€",
				"&",
				"<",
				">",
				"©",
				"•",
				"™",
				"®",
				"§",
				"@",
				" / ",
				"/",
				"\\",
				" ",
			],
			[
				"",
				"",
				"",
				"",
				"ae",
				"oe",
				"ue",
				"ss",
				"Euro",
				"und",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"-",
				"-",
				"",
				"",
				"",
				"-",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"ae",
				"oe",
				"ue",
				"ss",
				"Euro",
				"und",
				"",
				"",
				"Copyright",
				"",
				"TradeMark",
				"Registered TradeMark",
				"",
				"",
				"",
				"",
				"",
				"-",
			],
			trim(strtolower($value))
		);
		$value = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $value);
		$value = strtolower($value);
		$value = preg_replace('/(\-\'{1,})/', '-', $value);
		$value = preg_replace('/(\-{2,})/', '-', $value);
	}
	$value = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
	return $value;
}
function escapeArr($array) {
	$return = [];
	foreach($array as $key => $value) {
		$return[$key] = escape($value);
	}
	return $return;
}
function escapeByReference(&$value) {
	$value = escape($value);
}

function isMultidimensionalArray($array): bool {
	return !(count($array) == count($array, COUNT_RECURSIVE));
}
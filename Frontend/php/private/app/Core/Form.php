<?php
namespace Webapp\Core;

class Form {

	private $data = [],
			$errors = [],
			$customError = [],
			$customSuccess = [],
			$formHtml = "",
			$formGrid = [],
			$formCacheElements = [],
			$formUseInputFile = false;

	private static 	$instance = null,
					$renderCounter = 0,
					$validated = false;
	
	function __construct() {
		$this->data = Request::getInstance()->getPost();
		$files = Request::getInstance()->getFiles();
		if(!empty($files)) $this->data['files'] = $files;
	}

	/**
	 * Gets/Creates an Instance of Form.
	 *
	 * @return Form
	 */
	public static function getInstance(): self {
		if(is_null(self::$instance)) self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Check if a name is set in posted form data and auto validates the form.
	 *
	 * @param string $value
	 * @param bool $autoValidate
	 * @return bool
	 */
	public function is($value = "", bool $autoValidate = true): bool {
		if(isset($this->data[$value]) && $autoValidate) return $this->validate();
		return isset($this->data[$value]) ? true : false;
	}

	/**
	 * Get all form data.
	 *
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * Get the current amount of forms on the page.
	 *
	 * @return int
	 */
	public static function getRenderCounter(): int {
		return self::$renderCounter;
	}

	/**
	 * Get all form errors caught by validation.
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Adds an error.
	 *
	 * @param string $key
	 * @param array|string $error
	 * @return void
	 */
	private function addError(string $key, array|string $error): void {
		$this->errors[($this->data['form_id'] ?? 0)][$key] = $error;
	}

	/**
	 * Adds success output to a form.
	 *
	 * @param string $msg
	 * @return void
	 */
	public function success(string $msg, $createHtmlWrapper = true): void {
		if(empty($this->errors)) {
			$this->customSuccess[($this->data['form_id'] ?? 0)][] = ($createHtmlWrapper ? Error::html($msg, "success-bg") : $msg)."<br>";
			$this->clearCache();
		}
	}

	/**
	 * Adds error output to a form.
	 *
	 * @param string $msg
	 * @return void
	 */
	public function error(string $msg, $createHtmlWrapper = true): void {
		$this->customError[($this->data['form_id'] ?? 0)][] = ($createHtmlWrapper ? Error::html($msg) : $msg)."<br>";
	}

	/**
	 * Outputs any success or error messages at the current location.
	 *
	 * @return void
	 */
	public function outputResponses(): void {
		$post = (!empty($this->data) ? $this->data : Request::getInstance()->getPost());

		if(!empty($this->customSuccess) && !empty($this->customSuccess[($post['form_id'] ?? 0)])) {
			foreach($this->customSuccess[($post['form_id'] ?? 0)] as $success) {
				echo $success;
			}
		} else if(!empty($this->customError) && !empty($this->customError[($post['form_id'] ?? 0)])) {
			foreach($this->customError[($post['form_id'] ?? 0)] as $error) {
				echo $error;
			}
		}
		if(!empty($this->errors) && !empty($this->errors[($post['form_id'] ?? 0)]) && (count($this->errors[($post['form_id'] ?? 0)])>1 || array_search("token", array_column($this->errors[($post['form_id'] ?? 0)], "reason"))===false)) {
			echo Error::html(__('form.error'))."<br>";
		}
	}

	/**
	 * Validates form post data.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		if(!self::$validated) {
			$cache = $this->loadCache();
			$postData = $this->data;
			if(!empty($cache)) {
				foreach($cache['children'] as $key => $cacheElement) {
					$postValueName = $cacheElement['name'];

					if(strpos($postValueName, "[")!==false) {
						$postValue = "";

						$postValueArr = explode("[", $postValueName);
						$postValueArrName = $postValueArr[0];
						preg_match_all("/\[([a-zA-Z_\-]*)\]/", str_replace($postValueArrName, "", $postValueName), $postValueArr);
						if($postValueArr!==false) $postValueArr = (array) $postValueArr[1];
						else $postData = [];

						if(!empty($postData[$postValueArrName])) {
							if(!isMultidimensionalArray($postData[$postValueArrName])) {
								$postValue = array_shift($postData[$postValueArrName]);
							} else {
								$postDataTmp = $postData[$postValueArrName];
								$postValue = (function() use ($postValueArr, &$postDataTmp, &$postData, $postValueArrName) {
									$deepness = 1;
									foreach($postValueArr as $postValueItem) {
										if(empty($postValueItem) && isset($postDataTmp[0])) {
											$postDataTmp = array_shift($postDataTmp);
											if($deepness==1) {
												array_shift($postData[$postValueArrName]);
												$deepness++;
											}
										} else if(isset($postDataTmp[$postValueItem])) {
											$postDataTmp = $postDataTmp[$postValueItem];
											if($deepness==1) {
												unset($postData[$postValueArrName][$postValueItem]);
												$deepness++;
											}
										}
									}
									return $postDataTmp;
								})();
							}
						} else {
							$postValue = (isset($cacheElement['type']) && $cacheElement['type']==FormInputType::File->value && !empty($postData['files']) && isset($postData['files'][$postValueName]) && !empty($postData['files'][$postValueName]) ? $postData['files'][$postValueName][0]['name'] : "");
						}
					} else {
						$postValue = (isset($postData[$postValueName]) ? $postData[$postValueName] : (isset($cacheElement['type']) && $cacheElement['type']==FormInputType::File->value && !empty($postData['files']) && isset($postData['files'][$postValueName]) && !empty($postData['files'][$postValueName]) ? $postData['files'][$postValueName][0]['name'] : ""));
					}

					// token
					if(isset($cacheElement['token']) && $cacheElement['token']) {
						if(!Token::check($cacheElement['name'], $postValue)) {
							$this->addError($cacheElement['name'], [
								'reason' => 'token',
								'element' => $cacheElement,
								'value' => $postValue,
							]);
							$this->clearCache();
						}
					}
					// captcha
					if(isset($cacheElement['captcha']) && $cacheElement['captcha']) {
						$captcha = new \Webapp\Lib\Captcha();
						if(!$captcha->verify($postValue)) {
							$this->addError($cacheElement['name'], [
								'reason' => 'captcha',
								'element' => $cacheElement,
								'value' => $postValue,
							]);
						}
					}
					// required
					if(isset($cacheElement['required']) && $cacheElement['required']) {
						if(empty($postValue)) $this->addError($cacheElement['name'], [
							'reason' => 'required',
							'element' => $cacheElement,
							'value' => $postValue,
						]);
					}
					//  accept
					if(!empty($postData['files']) && !empty($cacheElement['accept'])) {
						$accept = explode(",", $cacheElement['accept']);
						if(!empty($postData['files'][$postValueName])) {
							foreach($postData['files'][$postValueName] as $file) {
								$type = $file['type'];
								$ext = ".".pathinfo($file['name'], PATHINFO_EXTENSION);
								if(!in_array($type, $accept) && !in_array($ext, $accept)) {
									$this->addError($cacheElement['name'], [
										'reason' => 'accept',
										'element' => $cacheElement,
										'files' => [
											'name' => $file['name'],
											'type' => $file['type'],
										],
									]);
								}
							}
						}
					}
					if(!empty($postValue)) {
						if($cacheElement['tag']=='input') {
							// types
							if($cacheElement['type']==FormInputType::Color->value) {
								$postValueCheck = ltrim($postValue, '#');
								if(!ctype_xdigit($postValueCheck) || (mb_strlen($postValueCheck)!=6 && mb_strlen($postValueCheck)!=3)) {
									$this->addError($cacheElement['name'], [
										'reason' => 'color',
										'element' => $cacheElement,
										'value' => $postValue,
									]);
								}
							}
							if($cacheElement['type']==FormInputType::Date->value || $cacheElement['type']==FormInputType::DatetimeLocal->value) {
								if(!(bool) strtotime($postValue) || ($postValue!=date("Y-m-d", strtotime($postValue)) && $postValue!=date("Y-m-d\TH:i", strtotime($postValue)))) {
									$this->addError($cacheElement['name'], [
										'reason' => 'date',
										'element' => $cacheElement,
										'value' => $postValue,
									]);
								}
							}
							if($cacheElement['type']==FormInputType::Email->value && !filter_var($postValue, FILTER_VALIDATE_EMAIL)) {
								$this->addError($cacheElement['name'], [
									'reason' => 'email',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
							if($cacheElement['type']==FormInputType::Month->value) {
								if(!(bool) strtotime($postValue."-01") || $postValue."-01"!=date("Y-m-d", strtotime($postValue."-01"))) {
									$this->addError($cacheElement['name'], [
										'reason' => 'month',
										'element' => $cacheElement,
										'value' => $postValue,
									]);
								}
							}
							if($cacheElement['type']==FormInputType::Number->value && !is_numeric($postValue)) {
								$this->addError($cacheElement['name'], [
									'reason' => 'number',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
							if($cacheElement['type']==FormInputType::Text->value && !is_string($postValue)) {
								$this->addError($cacheElement['name'], [
									'reason' => 'text',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
							if($cacheElement['type']==FormInputType::Time->value) {
								if(!(bool) strtotime($postValue) || $postValue!=date("H:i", strtotime($postValue))) {
									$this->addError($cacheElement['name'], [
										'reason' => 'time',
										'element' => $cacheElement,
										'value' => $postValue,
									]);
								}
							}
							if($cacheElement['type']==FormInputType::Url->value) {
								$postValueCheck = filter_var($postValue, FILTER_SANITIZE_URL);
								if(!filter_var($postValueCheck, FILTER_VALIDATE_URL)) {
									$this->addError($cacheElement['name'], [
										'reason' => 'url',
										'element' => $cacheElement,
										'value' => $postValue,
									]);
								}
							}
							if($cacheElement['type']==FormInputType::Week->value) {
								$year = substr($postValue, 0, 4);
								$weeksInYear = date("W", strtotime(date($year."-12-t")));
								$week = substr($postValue, -2);
								if($week>$weeksInYear) {
									$this->addError($cacheElement['name'], [
										'reason' => 'week',
										'element' => $cacheElement,
										'value' => $postValue,
									]);
								}
							}
							// min
							if(!empty($cacheElement['min'])) {
								if($postValue<$cacheElement['min']) $this->addError($cacheElement['name'], [
									'reason' => 'min',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
							// max
							if(!empty($cacheElement['max'])) {
								if($postValue>$cacheElement['max']) $this->addError($cacheElement['name'], [
									'reason' => 'max',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
							// step
							if(
								(!empty($cacheElement['step']) || 
								$cacheElement['type']==FormInputType::Number->value || 
								$cacheElement['type']==FormInputType::Range->value) &&
								is_numeric($postValue)
							) {
								$step = (!empty($cacheElement['step']) ? floatval($cacheElement['step']) : 1);
								$division = floor($postValue / $step);
								$multiple = $step * $division;
								$rest = ($postValue*10) - ($multiple*10);
								$rest = $rest / 10;
								if($rest>0) $this->addError($cacheElement['name'], [
									'reason' => 'step',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
							// pattern
							if(!empty($cacheElement['pattern'])) {
								if(!preg_match("/^".$cacheElement['pattern']."$/", $postValue)) $this->addError($cacheElement['name'], [
									'reason' => 'pattern',
									'element' => $cacheElement,
									'value' => $postValue,
								]);
							}
						}
						// minlength
						if(!empty($cacheElement['minlength'])) {
							if(mb_strlen($postValue)<$cacheElement['minlength']) $this->addError($cacheElement['name'], [
								'reason' => 'minlength',
								'element' => $cacheElement,
								'value' => $postValue,
							]);
						}
						// maxlength
						if(!empty($cacheElement['maxlength'])) {
							if(mb_strlen($postValue)>$cacheElement['maxlength']) $this->addError($cacheElement['name'], [
								'reason' => 'maxlength',
								'element' => $cacheElement,
								'value' => $postValue,
							]);
						}
					}
				}
			}
			self::$validated = true;
			return empty($this->errors);
		} else {
			throw new Error("Die doppelte Validierung eines Formulars ist nicht erlaubt.<br><i>Hinweis: Die Methode is() validiert automatisch, sofern nicht anders spezifiziert.</i>");
		}
		return false;
	}

	/**
	 * Packs an element, content, textBeofre and textAfter to a correct ordered array.
	 *
	 * @param array $element
	 * @param array $content
	 * @param string $textBefore
	 * @param string $textAfter
	 * @return false|array
	 */
	private function packageElementContent(array $element = [], array|string $content = [], string $textBefore = "", string $textAfter = ""): false|array {
		if(!empty($element)) {
			$return = [];
			$return['element'] = $element;
			if(!empty($content)) $return['content'] = $content;
			if(!empty($textBefore)) $return['textBefore'] = $textBefore;
			if(!empty($textAfter)) $return['textAfter'] = $textAfter;
			if(in_array($element['tag'], ['input', 'textarea', 'select'])) {
				$this->formCacheElements[] = $element;
			}
			return $return;
		}
		return false;
	}
	/**
	 * Generates an element with attributes and content.
	 *
	 * @param array $elements
	 * @param array $parentOrder
	 * @return bool
	 */
	private function generateElements(array $elements, array $parentOrder = []): bool {
		if(!empty($elements) && is_array($elements)) {
			$return = true;
			foreach($elements as $elementKey => $element) {
				if(!empty($element)) {
					if(is_string($element)) {
						$this->addHtml($element);
					} else if(is_array($element) && !empty($element['element'])) {
						$elementError = false;

						$elementItem = $element['element'];
						$elementContent = (!empty($element['content']) ? $element['content'] : "");

						if(empty($parentOrder)) {
							$childOrder = $this->getChildOrder($element, $elementKey);
						} else {
							$childOrder = $parentOrder;
						}

						if(!empty($elementItem['tag'])) {
							$elementTag = $elementItem['tag'];
								unset($elementItem['tag']);
							$elementEndTag = $elementItem['useEndTag'] ?? false;
								unset($elementItem['useEndTag']);
							if(!empty($elementItem['datalist'])) {
								$elementDatalist = $elementItem['datalist'];
									unset($elementItem['datalist']);
							}
							if(!empty($elementItem['gridId'])) {
								$elementGridId = $elementItem['gridId'];
									unset($elementItem['gridId']);
							}
							if(isset($elementItem['token'])) {
								unset($elementItem['token']);
							}
							if(isset($elementItem['captcha'])) {
								unset($elementItem['captcha']);
							}

							if($elementTag!='label' && (!empty($element['textBefore']) || !empty($element['textAfter'])) && ($elementTag!='input' || ($elementTag=='input' && $elementItem['type']!=FormInputType::Radio->value && $elementItem['type']!=FormInputType::Checkbox->value))) {
								$textCount = (!empty($element['textBefore']) ? mb_strlen($element['textBefore']) : 0);
								$textCount += (!empty($element['textAfter']) ? mb_strlen($element['textAfter']) : 0);
								$elementItem['style'] = "width:calc(100% - ".($textCount * 12)."px)";
							}
							if(!empty($element['textBefore'])) {
								$this->addHtml('<span>'.$element['textBefore'].'</span>');
							}

							// error handling
							if(!empty($this->errors) && !empty($elementItem['name']) && !empty($this->errors[self::$renderCounter]) && !empty($this->errors[self::$renderCounter][$elementItem['name']]) && (!isset($elementItem['type']) || $elementItem['type']!=FormInputType::Hidden->value)) {
								if(isset($elementItem['class'])) {
									$elementItem['class'] .= " fieldError";
								} else {
									$elementItem['class'] = "fieldError";
								}
								$error = $this->errors[self::$renderCounter][$elementItem['name']];
								$errorText = __('form.error.'.$error['reason']);
								if(!empty($errorText)) {
									$elementItem['title'] = $errorText;
									if($error['reason']=='accept' || $error['reason']=='pattern') $errorText .= " (Erlaubt: ".$error['element'][$error['reason']].")";
									if($error['reason']=='min') $errorText .= " (Minimum: ".$error['element'][$error['reason']].")";
									if($error['reason']=='max') $errorText .= " (Maximum: ".$error['element'][$error['reason']].")";
									if($error['reason']=='step') $errorText .= " (Vielfaches von: ".$error['element'][$error['reason']].")";
									if($error['reason']=='minlength') $errorText .= " (Mindestanzahl: ".$error['element'][$error['reason']].")";
									if($error['reason']=='maxlength') $errorText .= " (Maximalanzahl: ".$error['element'][$error['reason']].")";
								}
								$elementError = true;
								$this->addHtml('<div class="fieldErrorWrapper">');
							}

							$elementStartTag = '<'.$elementTag;
								foreach($elementItem as $attribute => $attributeValue) {
									if(!is_bool($attributeValue)) {
										if(!empty($attributeValue) && is_string($attributeValue) && is_array(json_decode($attributeValue, true))) {
											$elementStartTag .= " ".$attribute."='".$attributeValue."'";
										} else {
											$elementStartTag .= ' '.$attribute.'="'.$attributeValue.'"';
										}
									} else if($attributeValue) {
										$elementStartTag .= ' '.$attribute;
									}
								}
							$elementStartTag .= ">";
							$this->addHtml($elementStartTag);

								if(in_array("grid", $childOrder) && !empty($elementItem['class']) && strpos($elementItem['class'], 'grid')===0) {
									if(!empty($elementContent)) {
										$surroundingElement = [
											'useEndTag' => true,
											'tag' => 'div',
										];
										$elementContentTmp = [];
										$elementContentIdx = 0;
										foreach($elementContent as $elementContentItem) {
											if(is_string($elementContentItem)) {
												$elementContentTmp[] = $elementContentItem;
											} else {
												if(!empty($elementContentItem['element']['span'])) {
													$surroundingElement['class'] = $this->formGrid[$elementGridId]['prefix'].$elementContentItem['element']['span'];
													$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$elementContentItem['element']['span'].$this->formGrid[$elementGridId]['suffixTabletPortrait'];
													$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$elementContentItem['element']['span'].$this->formGrid[$elementGridId]['suffixTabletLandscape'];
													$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$elementContentItem['element']['span'].$this->formGrid[$elementGridId]['suffixDesktop'];
													unset($elementContentItem['element']['span']);
												} else {
													if($this->formGrid[$elementGridId]['splitEvenly']) {
														$surroundingElement['class'] = $this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['left'];
														$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['leftTabletPortrait'].$this->formGrid[$elementGridId]['suffixTabletPortrait'];
														$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['leftTabletLandscape'].$this->formGrid[$elementGridId]['suffixTabletLandscape'];
														$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['leftDesktop'].$this->formGrid[$elementGridId]['suffixDesktop'];
													} else {
														if(($elementContentIdx % 2 != 0)) {
															$surroundingElement['class'] = $this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['right'];
															$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['rightTabletPortrait'].$this->formGrid[$elementGridId]['suffixTabletPortrait'];
															$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['rightTabletLandscape'].$this->formGrid[$elementGridId]['suffixTabletLandscape'];
															$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['rightDesktop'].$this->formGrid[$elementGridId]['suffixDesktop'];
														} else {
															$surroundingElement['class'] = $this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['left'];
															$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['leftTabletPortrait'].$this->formGrid[$elementGridId]['suffixTabletPortrait'];
															$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['leftTabletLandscape'].$this->formGrid[$elementGridId]['suffixTabletLandscape'];
															$surroundingElement['class'] .= " ".$this->formGrid[$elementGridId]['prefix'].$this->formGrid[$elementGridId]['leftDesktop'].$this->formGrid[$elementGridId]['suffixDesktop'];
														}
													}
												}
												$elementContentTmp[] = $this->packageElementContent($surroundingElement, [$elementContentItem]);
												$elementContentIdx++;
											}
										}
										$elementContent = $elementContentTmp;
									}
								}

								if(!empty($elementContent)) {
									if(is_string($elementContent)) {
										$this->addHtml($elementContent);
									} else {
										$this->generateElements($elementContent, $childOrder);
									}
								}

							if(isset($elementEndTag) && $elementEndTag) $this->addHtml('</'.$elementTag.'>');

							// error handling
							if($elementError) {
								$elementErrorClass = "";
								if(!empty($element['textBefore'])) $elementErrorClass .= " right";
								if(!empty($element['textAfter'])) $elementErrorClass .= " left";
								$this->addHtml('<span class="fieldErrorTooltip'.$elementErrorClass.'">'.$errorText.'</span>');
							}

							if(!empty($element['textAfter'])) {
								$this->addHtml('<span>'.$element['textAfter'].'</span>');
							}

							// error handling
							if($elementError) {
								$this->addHtml("</div>");
							}

							if(!empty($elementDatalist)) {
								$this->generateElements($elementDatalist);
							}
						}
					}
				} else {
					$return = false;
				}
			}
			return $return;
		}
		return false;
	}
	/**
	 * Returns the tag-order of all children in content.
	 *
	 * @param array|string $content
	 * @param string $key
	 * @return array
	 */
	private function getChildOrder(array|string $content = [], string $key = null): array {
		if(!empty($content) && is_array($content) && !is_null($key)) {
			$return = [];

			$contentElement = $content['element'] ?? "";
			$contentContent = (!empty($content['content']) ? $content['content'] : "");

			if(!empty($contentElement['class']) && strpos($contentElement['class'], 'grid')===0) $return[] = 'grid';
			if(!empty($contentElement['tag']) && $contentElement['tag']=='label') $return[] = 'label';
			if(!empty($contentContent) && is_array($contentContent)) {
				foreach($contentContent as $contentContentItem) {
					$contentReturn = $this->getChildOrder($contentContentItem, $key);
					if(!empty($contentReturn)) {
						foreach($contentReturn as $contentReturnItem) {
							$return[] = $contentReturnItem;
						}
					}
				}
			}

			return $return;
		}
		return [];
	}
	/**
	 * Add html to the form.
	 *
	 * @param string $html
	 * @return bool
	 */
	private function addHtml(string $html = ""): bool {
		if(!empty($html)) {
			$this->formHtml .= $html;
			return true;
		}
		return false;
	}
	/**
	 * Save cache for specific form and the children.
	 *
	 * @param array $children
	 * @return void
	 */
	private function cache(array $children = []): void {
		$formId = md5(Router::getFilename()).'_'.self::$renderCounter;
		Session::add('forms', [
			$formId => [
				'ts' => strtotime("now"),
				'children' => $children,
			],
		]);
		$this->formCacheElements = [];
	}
	/**
	 * Load the specific form from the cache.
	 *
	 * @return array
	 */
	private function loadCache(): array {
		if(isset($this->data['form_id'])) {
			$formId = md5(Router::getFilename()).'_'.$this->data['form_id'];
			$cache = (Session::exists('forms') ? Session::get('forms') : []);
			return (!empty($cache[$formId]) ? $cache[$formId] : []);
		}
		return [];
	}

	public function clearCache(): bool {
		if(Session::exists('forms')) {
			Session::delete('forms');
			$this->data = [];
			return true;
		}
		return false;
	}

	/**
	 * Generates and outputs or returns a ```<form>``` element with the specified content.\
	 * If ```$return``` is set to ```true```, no response above the form will be outputted, use ```outputResponses()``` at the desired place.
	 *
	 * @param array $children
	 * @param string $action
	 * @param string $method
	 * @param FormEnctype|null $enctype
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param bool $novalidate
	 * @param string|null $target
	 * @param string|null $autocomplete
	 * @param string|null $name
	 * @param string|null $rel
	 * @param array $attributes
	 * @param bool $return
	 * @return string|null
	 */
	public function render(
		array $children,

		string $action='',
		string $method='POST',
		FormEnctype $enctype=null,
		string $id=null,
		string|array $classes=null,

		bool $novalidate=false,
		string $target=null,
		string $autocomplete=null,
		string $name=null,
		string $rel=null,

		string|array $attributes=[],

		bool $return = false,
	): ?string {
		if($this->formUseInputFile && is_null($enctype)) $enctype = FormEnctype::FormData;

		// error/success handling
		if(!$return && !empty($this->customSuccess) && !empty($this->customSuccess[self::$renderCounter])) {
			foreach($this->customSuccess[self::$renderCounter] as $success) {
				$this->addHtml($success);
			}
		}
		if(!$return && !empty($this->customError) && !empty($this->customError[self::$renderCounter])) {
			foreach($this->customError[self::$renderCounter] as $error) {
				$this->addHtml($error);
			}
		}
		if(!$return && !empty($this->errors) && !empty($this->errors[self::$renderCounter]) && (count($this->errors)>1 || array_search("token", array_column($this->errors, "reason"))===false)) {
			$this->addHtml(Error::html(__('form.error'))."<br>");
		}

		$elementStartTag = '<form action="'.$action.'" method="'.$method.'"';
			if(!is_null($enctype)) $elementStartTag .= ' enctype="'.$enctype->value.'"';
			if(!is_null($id)) $elementStartTag .= ' id="'.$id.'"';
			if(!is_null($classes)) {
				if(is_array($classes)) $elementStartTag .= ' class="'.(implode(" ", $classes)).'"';
				else $elementStartTag .= ' class="'.$classes.'"';
			}
			if($novalidate) $elementStartTag .= ' novalidate"';
			if(!is_null($target)) $elementStartTag .= ' target="'.$target.'"';
			if(!is_null($autocomplete)) $elementStartTag .= ' autocomplete="'.$autocomplete.'"';
			if(!is_null($name)) $elementStartTag .= ' name="'.$name.'"';
			if(!is_null($rel)) $elementStartTag .= ' rel="'.$rel.'"';
			if(!empty($attributes)) {
				if(is_array($attributes)) {
					foreach($attributes as $attributeKey => $attributeValue) {
						$elementStartTag .= ' '.$attributeKey."=\"".$attributeValue."\"";
					}
				} else if(is_string($attributes)) {
					$attributes = explode(" ", $attributes);
					if(!empty($attributes)) {
						foreach($attributes as $attributeKeyValue) {
							$attributeKeyValue = explode("=", $attributeKeyValue);
							if(!empty($attributeKeyValue)) {
								$attributeKey = trim($attributeKeyValue[0], "'\"");
								$attributeValue = trim($attributeKeyValue[1], "'\"");
								$elementStartTag .= ' '.$attributeKey."=\"".$attributeValue."\"";
							}
						}
					}
				}
			}
		$elementStartTag .= '>';

		$this->addHtml($elementStartTag);
		if(!empty($children)) {
			array_unshift($children, $this->input(
				type: FormInputType::Hidden,
				name: 'form_id',
				value: self::$renderCounter,
			));
			$this->generateElements($children);
			if(!empty($this->formCacheElements)) $this->cache($this->formCacheElements);
		}
		$this->addHtml('</form>');

		self::$renderCounter++;

		$html = $this->formHtml;
		$this->formHtml = "";

		if(!$return) { echo $html; return null; }
		else return $html;
	}
	/**
	 * Generates and returns a `<div class="grid">` element.
	 *
	 * @param array $children
	 * @param int $span
	 * @param bool|null $splitEvenly
	 * @param int|null $left
	 * @param int|null $right
	 * @param int|null $leftMobile
	 * @param int|null $rightMobile
	 * @param int|null $leftTabletPortrait
	 * @param int|null $rightTabletPortrait
	 * @param int|null $leftTabletLandscape
	 * @param int|null $rightTabletLandscape
	 * @return false|array
	 */
	public function grid(
		array $children,

		int $span=12,
		bool $splitEvenly=null,
		int $left=null,
		int $right=null,
		int $leftTabletPortrait=null,
		int $rightTabletPortrait=null,
		int $leftTabletLandscape=null,
		int $rightTabletLandscape=null,
		int $leftDesktop=null,
		int $rightDesktop=null,

		string $id=null,
		string|array $classes=null,

		string|array $attributes=[],
	): false|array {
		if(!empty($children)) {
			$gridId = mt_rand(1000000000, 9999999999);

			// Mobile
			if(is_null($left) && is_null($right) && $span>0) $left = $right = $span;
			if(is_null($left) && !is_null($right)) $left = ($right!=$span ? $span - $right : $right);
			if(is_null($right)) $right = ($left!=$span ? $span - $left : $left);

			// TabletPortrait
			if(is_null($leftTabletPortrait)) $leftTabletPortrait = $left;
			if(is_null($rightTabletPortrait)) $rightTabletPortrait = $right;
			if(!is_null($leftTabletPortrait)) $rightTabletPortrait = ($leftTabletPortrait!=$span ? $span - $leftTabletPortrait : $leftTabletPortrait);
			if(!is_null($rightTabletPortrait)) $leftTabletPortrait = ($rightTabletPortrait!=$span ? $span - $rightTabletPortrait : $rightTabletPortrait);

			// TabletLandscape
			if(is_null($leftTabletLandscape)) $leftTabletLandscape = $leftTabletPortrait;
			if(is_null($rightTabletLandscape)) $rightTabletLandscape = $rightTabletPortrait;
			if(!is_null($leftTabletLandscape)) $rightTabletLandscape = ($leftTabletLandscape!=$span ? $span - $leftTabletLandscape : $leftTabletLandscape);
			if(!is_null($rightTabletLandscape)) $leftTabletLandscape = ($rightTabletLandscape!=$span ? $span - $rightTabletLandscape : $rightTabletLandscape);

			// Desktop
			if(is_null($leftDesktop)) $leftDesktop = $leftTabletLandscape;
			if(is_null($rightDesktop)) $rightDesktop = $rightTabletLandscape;
			if(!is_null($leftDesktop)) $rightDesktop = ($leftDesktop!=$span ? $span - $leftDesktop : $leftDesktop);
			if(!is_null($rightDesktop)) $leftDesktop = ($rightDesktop!=$span ? $span - $rightDesktop : $rightDesktop);

			$this->formGrid[$gridId] = [
				'span' => $span,
				'splitEvenly' => $splitEvenly,
				'left' => $left,
				'right' => $right,
				'leftTabletPortrait' => $leftTabletPortrait,
				'rightTabletPortrait' => $rightTabletPortrait,
				'leftTabletLandscape' => $leftTabletLandscape,
				'rightTabletLandscape' => $rightTabletLandscape,
				'leftDesktop' => $leftDesktop,
				'rightDesktop' => $rightDesktop,
				'prefix' => "col-",
				'suffixTabletPortrait' => "-tp",
				'suffixTabletLandscape' => "-tl",
				'suffixDesktop' => "-d",
			];

			$element = [
				'useEndTag' => true,
				'tag' => 'div',
				'class' => 'grid',
				'gridId' => $gridId,
			];
			if(!empty($id)) $element['id'] = $id;
			if(!empty($classes)) $element['class'] .= " ".(is_array($classes) ? implode(" ", $classes) : $classes);

			if(!empty($attributes)) {
				if(is_array($attributes)) {
					foreach($attributes as $attributeKey => $attributeValue) {
						$element[$attributeKey] = $attributeValue;
					}
				} else if(is_string($attributes)) {
					$attributes = explode(" ", $attributes);
					if(!empty($attributes)) {
						foreach($attributes as $attributeKeyValue) {
							$attributeKeyValue = explode("=", $attributeKeyValue);
							if(!empty($attributeKeyValue)) {
								$attributeKey = trim($attributeKeyValue[0], "'\"");
								$attributeValue = trim($attributeKeyValue[1], "'\"");
								$element[$attributeKey] = $attributeValue;
							}
						}
					}
				}
			}

			return $this->packageElementContent($element, $children);
		}
		return false;
	}
	/**
	 * Generates and returns a `<label>` element.
	 *
	 * @param array $children
	 * @param string $title
	 * @param string|null $for
	 * @param bool $titleAfterChildren
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param array $attributes
	 * @return false|array
	 */
	public function label(
		array $children = [],
		string $title='',
		string $for=null,
		bool $titleAfterChildren = false,

		string $id=null,
		string|array $classes=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'label',
		];
		if(!empty($for)) $element['for'] = $for;
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		if(!empty($title)) {
			if($titleAfterChildren) {
				$children[] = "<span>".$title."</span>";
			} else {
				array_unshift($children, "<span>".$title."</span>");
			}
		}

		return $this->packageElementContent($element, $children);
	}
	/**
	 * Generates an token input element.
	 *
	 * @param object $token
	 * @return false|array
	 */
	public function token(object $token): false|array {
		return (!empty($token) && is_object($token) ? $this->input(
			type: FormInputType::Hidden,
			name: $token->name,
			value: $token->value,
			token: true,
		) : false);
	}
	/**
	 * Generates and returns a <input> element.
	 *
	 * @param FormInputType $type
	 * @param string $name
	 * @param string|null $value
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param string|null $accept
	 * @param string|null $alt
	 * @param string|null $autocomplete
	 * @param bool $autofocus
	 * @param string|null $capture
	 * @param bool $checked
	 * @param string|null $dirname
	 * @param bool $disabled
	 * @param string|null $form
	 * @param string|null $formaction
	 * @param string|null $formmethod
	 * @param string|null $formenctype
	 * @param bool $formnovalidate
	 * @param string|null $formtarget
	 * @param string|null $inputmode
	 * @param array $datalist
	 * @param int|null $min
	 * @param int|null $max
	 * @param int|null $minlength
	 * @param int|null $maxlength
	 * @param bool $multiple
	 * @param string|null $pattern
	 * @param string|null $placeholder
	 * @param bool $readonly
	 * @param bool $required
	 * @param int|null $size
	 * @param string $src
	 * @param string|int|float|null $step
	 * @param int|null $tabindex
	 * @param string|null $title
	 * @param string $textBefore
	 * @param string $textAfter
	 * @param bool $token
	 * @param bool $captcha
	 * @param array $attributes
	 * @return false|array
	 */
	public function input(
		FormInputType $type,
		string $name,
		string $value=null,
		string $id=null,
		string|array $classes=null,

		string $accept=null,
		string $alt=null,
		string $autocomplete=null,
		bool $autofocus=false,
		string $capture=null,
		bool $checked=false,
		string $dirname=null,
		bool $disabled=false,
		string $form=null,
		string $formaction=null,
		string $formmethod=null,
		string $formenctype=null,
		bool $formnovalidate=false,
		string $formtarget=null,
		string $inputmode=null,
		array $datalist=[],
		int $min=null,
		int $max=null,
		int $minlength=null,
		int $maxlength=null,
		bool $multiple=false,
		string $pattern=null,
		string $placeholder=null,
		bool $readonly=false,
		bool $required=false,
		int $size=null,
		string $src='',
		string|int|float $step=null,
		int $tabindex=null,
		string $title=null,

		string $textBefore="",
		string $textAfter="",

		bool $token = false,
		bool $captcha = false,

		string|array $attributes=[],
	): false|array {
		$element = [
			'tag' => 'input',
			'type' => $type->value,
			'name' => $name,
		];
		if(!is_null($value)) $element['value'] = $value;
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);

		// all types
		if($disabled) $element['disabled'] = true;
		if(!empty($form)) $element['form'] = $form;
		if(!empty($inputmode) && ($inputmode=='none' || $inputmode=='text' || $inputmode=='tel' || $inputmode=='url' || $inputmode=='email' || $inputmode=='numeric' || $inputmode=='decimal' || $inputmode=='search')) {
			$element['inputmode'] = $inputmode;
		}
		if(!is_null($tabindex) && is_int($tabindex)) $element['tabindex'] = $tabindex;
		if(!empty($title)) $element['title'] = $title;

		// specific types
		if($type==FormInputType::Hidden) {
			if($token) $element['token'] = $token;
		}
		if($type==FormInputType::Text) {
			if($captcha) $element['captcha'] = $captcha;
		}
		if($type==FormInputType::File) {
			if($multiple) $element['name'] .= (strpos($element['name'], "[]")!==false ? "" : "[]");
			if(!empty($accept)) $element['accept'] = $accept;
			if(!empty($capture)) $element['capture'] = $capture;
			$this->formUseInputFile = true;
		}
		if($type==FormInputType::Image) {
			if(!empty($alt)) $element['alt'] = $alt;
			if(!empty($src)) $element['src'] = $src;
		}
		if($type==FormInputType::Hidden || $type==FormInputType::Text || $type==FormInputType::Search || $type==FormInputType::Url || $type==FormInputType::Tel || $type==FormInputType::Email || $type==FormInputType::Date || $type==FormInputType::Month || $type==FormInputType::Week || $type==FormInputType::Time || $type==FormInputType::DatetimeLocal || $type==FormInputType::Number || $type==FormInputType::Range || $type==FormInputType::Color || $type==FormInputType::Password) {
			if(!empty($autocomplete)) $element['autocomplete'] = $autocomplete;
		}
		if($type==FormInputType::Button || $type==FormInputType::Checkbox || $type==FormInputType::Color || $type==FormInputType::Date || $type==FormInputType::DatetimeLocal || $type==FormInputType::Email || $type==FormInputType::File || $type==FormInputType::Image || $type==FormInputType::Month || $type==FormInputType::Number || $type==FormInputType::Password || $type==FormInputType::Range || $type==FormInputType::Reset || $type==FormInputType::Search || $type==FormInputType::Submit || $type==FormInputType::Tel || $type==FormInputType::Text || $type==FormInputType::Time || $type==FormInputType::Url || $type==FormInputType::Week) {
			if($autofocus) $element['autofocus'] = true;
		}
		if($type==FormInputType::Radio || $type==FormInputType::Checkbox) {
			if($checked) $element['checked'] = true;
		}
		if($type==FormInputType::Text || $type==FormInputType::Search) {
			if(!empty($dirname)) $element['dirname'] = $dirname;
		}
		if($type==FormInputType::Image || $type==FormInputType::Submit) {
			if(!empty($formaction)) $element['formaction'] = $formaction;
			if(!empty($formmethod)) $element['formmethod'] = $formmethod;
			if(!empty($formenctype)) $element['formenctype'] = $formenctype;
			if($formnovalidate) $element['formnovalidate'] = true;
			if(!empty($formtarget)) $element['formtarget'] = $formtarget;
		}
		if(
			$type==FormInputType::Text &&
			$type==FormInputType::Search &&
			$type==FormInputType::Url &&
			$type==FormInputType::Tel &&
			$type==FormInputType::Email &&
			$type==FormInputType::Number &&
			$type==FormInputType::Month &&
			$type==FormInputType::Week &&
			$type==FormInputType::Date &&
			$type==FormInputType::DatetimeLocal &&
			$type==FormInputType::Time &&
			$type==FormInputType::Range &&
			$type==FormInputType::Color &&
			!empty($datalist) &&
			!empty($datalist['element']) &&
			!empty($datalist['element']['id'])
		) {
			$element['list'] = $datalist['element']['id'];
			$element['datalist'] = [$datalist];
		}
		if($type==FormInputType::File || $type==FormInputType::Email) {
			if($multiple) $element['multiple'] = true;
		}
		if($type==FormInputType::Date || $type==FormInputType::Month || $type==FormInputType::Week || $type==FormInputType::Time || $type==FormInputType::DatetimeLocal || $type==FormInputType::Number || $type==FormInputType::Range) {
			if(!is_null($min) && is_int($min)) $element['min'] = $min;
			if(!is_null($max) && is_int($max)) $element['max'] = $max;
		}
		if($type==FormInputType::Text || $type==FormInputType::Search || $type==FormInputType::Url || $type==FormInputType::Tel || $type==FormInputType::Email || $type==FormInputType::Password) {
			if(!is_null($minlength) && is_int($minlength)) $element['minlength'] = $minlength;
			if(!is_null($maxlength) && is_int($maxlength)) $element['maxlength'] = $maxlength;
			if(!empty($pattern)) $element['pattern'] = $pattern;
		}
		if($type==FormInputType::Text || $type==FormInputType::Search || $type==FormInputType::Url || $type==FormInputType::Tel || $type==FormInputType::Email || $type==FormInputType::Password || $type==FormInputType::Number) {
			if(!empty($placeholder)) $element['placeholder'] = $placeholder;
		}
		if($type==FormInputType::Text || $type==FormInputType::Search || $type==FormInputType::Url || $type==FormInputType::Tel || $type==FormInputType::Email || $type==FormInputType::Date || $type==FormInputType::Month || $type==FormInputType::Week || $type==FormInputType::Time || $type==FormInputType::DatetimeLocal || $type==FormInputType::Number || $type==FormInputType::Password) {
			if($readonly) $element['readonly'] = true;
		}
		if($type==FormInputType::Text || $type==FormInputType::Search || $type==FormInputType::Url || $type==FormInputType::Tel || $type==FormInputType::Email || $type==FormInputType::Date || $type==FormInputType::Month || $type==FormInputType::Week || $type==FormInputType::Time || $type==FormInputType::DatetimeLocal || $type==FormInputType::Number || $type==FormInputType::Password || $type==FormInputType::Checkbox || $type==FormInputType::Radio || $type==FormInputType::File) {
			if($required) $element['required'] = true;
		}
		if($type==FormInputType::Email || $type==FormInputType::Password || $type==FormInputType::Tel || $type==FormInputType::Url || $type==FormInputType::Text) {
			if(!is_null($size) && is_int($size)) $element['size'] = $size;
		}
		if($type==FormInputType::Date || $type==FormInputType::Month || $type==FormInputType::Week || $type==FormInputType::DatetimeLocal || $type==FormInputType::Number || $type==FormInputType::Range) {
			if(!empty($step) || (!is_null($step) && is_int($step) || is_float($step))) $element['step'] = $step;
		}

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		// set value after post
		if(isset($this->data[$name]) && !isset($element['token']) && !isset($element['captcha']) && $element['name']!='form_id' && $element['type']!=FormInputType::File->value && $element['type']!=FormInputType::Password->value && $this->data['form_id']==self::$renderCounter) {
			if($element['type']==FormInputType::Checkbox->value && $this->data[$name]=='on') {
				$element['checked'] = true;
			} else if($element['type']==FormInputType::Radio->value && $this->data[$name]==$value) {
				$element['checked'] = true;
			} else if($element['type']!=FormInputType::Radio->value) {
				$element['value'] = $this->data[$name];
			}
		}

		return $this->packageElementContent($element, textBefore: $textBefore, textAfter: $textAfter);
	}
	/**
	 * Generates and returns a <textarea> element.
	 *
	 * @param string $name
	 * @param string|null $value
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param string|null $autocomplete
	 * @param bool $autofocus
	 * @param int|null $cols
	 * @param int|null $rows
	 * @param bool $disabled
	 * @param string|null $form
	 * @param int|null $maxlength
	 * @param int|null $minlength
	 * @param string|null $placeholder
	 * @param bool $readonly
	 * @param bool $required
	 * @param bool|string|null $spellcheck
	 * @param string|null $wrap
	 * @param array $attributes
	 * @return false|array
	 */
	public function textarea(
		string $name,
		string $value=null,
		string $id=null,
		string|array $classes=null,

		string $autocomplete=null,
		bool $autofocus=false,
		int $cols=null,
		int $rows=null,
		bool $disabled=false,
		string $form=null,
		int $maxlength=null,
		int $minlength=null,
		string $placeholder=null,
		bool $readonly=false,
		bool $required=false,
		bool|string $spellcheck=null,
		string $wrap=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'textarea',
			'name' => $name,
		];
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);
		if(!empty($autocomplete)) $element['autocomplete'] = $autocomplete;
		if($autofocus) $element['autofocus'] = true;
		if(!is_null($cols) && $cols>0) $element['cols'] = $cols;
		if(!is_null($rows) && $rows>0) $element['rows'] = $rows;
		if($disabled) $element['disabled'] = true;
		if(!empty($form)) $element['form'] = $form;
		if(!is_null($maxlength) && is_int($maxlength)) $element['maxlength'] = $maxlength;
		if(!is_null($minlength) && is_int($minlength)) $element['minlength'] = $minlength;
		if(!empty($placeholder)) $element['placeholder'] = $placeholder;
		if($readonly) $element['readonly'] = true;
		if($required) $element['required'] = true;
		if(!empty($spellcheck) && ($spellcheck==true || $spellcheck=='true' || $spellcheck==false || $spellcheck=='false' || $spellcheck=='default')) $element['spellcheck'] = $spellcheck;
		if(!empty($wrap) && ($wrap=='hard' || $wrap=='soft' || $wrap=='off')) $element['wrap'] = $wrap;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		// set value after post
		if(isset($this->data[$name]) && $this->data['form_id']==self::$renderCounter) {
			$value = $this->data[$name];
		}

		return $this->packageElementContent($element, (!empty($value) ? $value : ""));
	}
	/**
	 * Generates and returns a <select> element.
	 *
	 * @param array $options
	 * @param string $name
	 * @param string|null $insertEmptyOption
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param string|null $autocomplete
	 * @param bool $autofocus
	 * @param bool $disabled
	 * @param string|null $form
	 * @param bool $multiple
	 * @param bool $required
	 * @param int|null $size
	 * @param array $attributes
	 * @return false|array
	 */
	public function select(
		array $options,

		string $name,
		string $insertEmptyOption=null,
		string $id=null,
		string|array $classes=null,

		string $autocomplete=null,
		bool $autofocus=false,
		bool $disabled=false,
		string $form=null,
		bool $multiple=false,
		bool $required=false,
		int $size=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'select',
			'name' => $name,
		];
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);
		if(!empty($autocomplete)) $element['autocomplete'] = $autocomplete;
		if($autofocus) $element['autofocus'] = true;
		if($disabled) $element['disabled'] = true;
		if(!empty($form)) $element['form'] = $form;
		if($multiple) $element['multiple'] = true;
		if($required) $element['required'] = true;
		if(!empty($size)) $element['size'] = $size;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		// set value after post
		$selectDefaultValue = true;
		if(isset($this->data[$name]) && $this->data['form_id']==self::$renderCounter) {
			$value = $this->data[$name];
			foreach($options as $key => $option) {
				$optionElement = $option['element'];
				if($optionElement['value']==$value) {
					$options[$key]['element']['selected'] = true;
					$selectDefaultValue = false;
				}
			}
		}

		if(!empty($insertEmptyOption)) {
			array_unshift($options, $this->option(
				text: $insertEmptyOption,
				value: '',
				disabled: true,
				selected: $selectDefaultValue,
				hidden: true,
			));
		}

		return $this->packageElementContent($element, $options);
	}
	/**
	 * Generates and returns a <optgroup> element.
	 *
	 * @param string $label
	 * @param array $options
	 * @param bool $disabled
	 * @param array $attributes
	 * @return false|array
	 */
	public function optgroup(
		string $label,
		array $options,

		bool $disabled=false,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'optgroup',
			'label' => $label,
		];
		if($disabled) $element['disabled'] = true;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element, $options);
	}
	/**
	 * Generates and returns a <option> element.
	 *
	 * @param string $text
	 * @param string|null $label
	 * @param string|null $value
	 * @param bool $disabled
	 * @param bool $selected
	 * @param bool $hidden
	 * @param array $attributes
	 * @return false|array
	 */
	public function option(
		string $text,
		string $label=null,
		string $value=null,

		bool $disabled=false,
		bool $selected=false,
		bool $hidden=false,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'option',
		];
		if(!empty($label)) $element['label'] = $label;
		if(!is_null($value)) $element['value'] = $value;
		if(is_null($value)) $element['value'] = $text;
		if($disabled) $element['disabled'] = true;
		if($selected) $element['selected'] = true;
		if($hidden) $element['hidden'] = true;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element, $text);
	}
	/**
	 * Generates and returns a <button> element.
	 *
	 * @param FormButtonType $type
	 * @param string $name
	 * @param string $value
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param bool $autofocus
	 * @param bool $disabled
	 * @param string|null $form
	 * @param string|null $formaction
	 * @param FormEnctype|null $formenctype
	 * @param string|null $formmethod
	 * @param bool|null $formnovalidate
	 * @param string|null $formtarget
	 * @param bool $required
	 * @param array $attributes
	 * @return false|array
	 */
	public function button(
		FormButtonType $type,
		string $name,
		string $value,
		string $id=null,
		string|array $classes=null,

		bool $autofocus=false,
		bool $disabled=false,
		string $form=null,
		string $formaction=null,
		FormEnctype $formenctype=null,
		string $formmethod=null,
		bool $formnovalidate=null,
		string $formtarget=null,
		bool $required=false,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'button',
			'type' => $type->value,
			'name' => $name,
		];
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);
		if($autofocus) $element['autofocus'] = true;
		if($disabled) $element['disabled'] = true;
		if(!empty($form)) $element['form'] = $form;
		if(!empty($formaction)) $element['formaction'] = $formaction;
		if(!empty($formenctype)) $element['formenctype'] = $formenctype->value;
		if(!empty($formmethod)) $element['formmethod'] = $formmethod;
		if($formnovalidate) $element['formnovalidate'] = true;
		if(!empty($formtarget)) $element['formtarget'] = $formtarget;
		if($required) $element['required'] = true;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element, $value);
	}
	/**
	 * Generates and returns a <fieldset> element.
	 *
	 * @param array $legend
	 * @param array $children
	 * @param string|null $name
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param bool $disabled
	 * @param string|null $form
	 * @param array $attributes
	 * @return false|array
	 */
	public function fieldset(
		array $legend,
		array $children,

		string $name=null,
		string $id=null,
		string|array $classes=null,

		bool $disabled=false,
		string $form=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'fieldset',
		];
		if(!empty($name)) $element['name'] = $name;
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);
		if(!empty($form)) $element['form'] = $form;
		if($disabled) $element['disabled'] = true;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}
		array_unshift($children, $legend);

		return $this->packageElementContent($element, $children);
	}
	/**
	 * Generates and returns a <legend> element.
	 *
	 * @param string|array $child
	 * @param array $attributes
	 * @return false|array
	 */
	public function legend(
		string|array $child,

		string|array $attributes=[],
	): false|array {
		if(is_array($child)) $child = [$child];

		$element = [
			'useEndTag' => true,
			'tag' => 'legend',
		];

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element, $child);
	}
	/**
	 * Generates and returns a <output> element.
	 *
	 * @param string|null $name
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param string|null $for
	 * @param string|null $form
	 * @param array $attributes
	 * @return false|array
	 */
	public function output(
		string $name=null,
		string $id=null,
		string|array $classes=null,

		string $for=null,
		string $form=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'output',
		];
		if(!empty($name)) $element['name'] = $name;
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);
		if(!empty($for)) $element['for'] = $for;
		if(!empty($form)) $element['form'] = $form;

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element);
	}
	/**
	 * Generates and returns a <datalist> element.
	 *
	 * @param string $id
	 * @param array $options
	 * @param string|null $name
	 * @param string|array|null $classes
	 * @param array $attributes
	 * @return false|array
	 */
	public function datalist(
		string $id,
		array $options,

		string $name=null,
		string|array $classes=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'datalist',
		];
		if(!empty($name)) $element['name'] = $name;
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element, $options);
	}

	/**
	 * Generate a grid item with html or children as content. Only used for children of grid.
	 *
	 * @param string|array $content
	 * @param int|null $span
	 * @param string|null $id
	 * @param string|array|null $classes
	 * @param array $attributes
	 * @return false|array
	 */
	public function gridItem(
		string|array $content,

		int $span=null,

		string $id=null,
		string|array $classes=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'div',
		];

		if(!empty($span)) $element['span'] = $span;
		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		return $this->packageElementContent($element, (is_array($content) && !isset($content['element']) ? $content : [$content]));
	}

	/**
	 * Generates and returns a <input> element with captcha.
	 *
	 * 
	 * @return false|array
	 */
	public function captcha(
		string|array $name,
		int $length = 0,

		string $id=null,
		string|array $classes=null,

		string|array $attributes=[],
	): false|array {
		$element = [
			'useEndTag' => true,
			'tag' => 'div',
		];

		if(!empty($id)) $element['id'] = $id;
		if(!empty($classes)) $element['class'] = (is_array($classes) ? implode(" ", $classes) : $classes);

		if(!empty($attributes)) {
			if(is_array($attributes)) {
				foreach($attributes as $attributeKey => $attributeValue) {
					$element[$attributeKey] = $attributeValue;
				}
			} else if(is_string($attributes)) {
				$attributes = explode(" ", $attributes);
				if(!empty($attributes)) {
					foreach($attributes as $attributeKeyValue) {
						$attributeKeyValue = explode("=", $attributeKeyValue);
						if(!empty($attributeKeyValue)) {
							$attributeKey = trim($attributeKeyValue[0], "'\"");
							$attributeValue = trim($attributeKeyValue[1], "'\"");
							$element[$attributeKey] = $attributeValue;
						}
					}
				}
			}
		}

		$captcha = new \Webapp\Lib\Captcha();
		$captchaImg = "<img src='".$captcha->generate($length)."' alt='Captcha Bild' onClick='var el=this;ajax(\"/ajax/captcha?t=\"+Date.now(), null, null, data => { el.src = data; });'>";

		$content = $this->input(
			type: FormInputType::Text,
			name: $name,
			required: true,
			captcha: true,
			maxlength: $captcha->getLength(),
			minlength: $captcha->getLength(),
		);

		return $this->packageElementContent($element, [$captchaImg, $content]);
	}

}

enum FormEnctype: string {
	case Text = 'text/plain';
	case FormData = 'multipart/form-data';
	case FormUrlEncode = 'application/x-www-form-urlencoded';
}

enum FormInputType: string {
	case Button = 'button';
	case Checkbox = 'checkbox';
	case Color = 'color';
	case Date = 'date';
	case DatetimeLocal = 'datetime-local';
	case Email = 'email';
	case File = 'file';
	case Hidden = 'hidden';
	case Image = 'image';
	case Month = 'month';
	case Number = 'number';
	case Password = 'password';
	case Radio = 'radio';
	case Range = 'range';
	case Reset = 'reset';
	case Search = 'search';
	case Submit = 'submit';
	case Tel = 'tel';
	case Text = 'text';
	case Time = 'time';
	case Url = 'url';
	case Week = 'week';
}

enum FormButtonType: string {
	case Button = 'button';
	case Reset = 'reset';
	case Submit = 'submit';
}
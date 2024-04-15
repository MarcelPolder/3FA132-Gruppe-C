<?php
namespace Webapp\Lib;

use Webapp\Core\Session;

class Captcha {

	private $permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
	private $captchaTextLength = 6;
	private $captchaSessionName = 'captchaText';
	private $captchaText = '';
	private $captchaImageData = '';

	public function getLength() {
		return $this->captchaTextLength;
	}
	public function generate($captchaTextLength = 0, $addImageType = true) {
		if($captchaTextLength!==0) $this->captchaTextLength = $captchaTextLength;
		$this->captchaText = $this->generateString($this->permitted_chars);
		$this->captchaImageData = $this->generateImage($this->captchaText);
		Session::set($this->captchaSessionName, $this->captchaText);
		return ($addImageType ? "data:image/png;base64,".base64_encode($this->captchaImageData) : $this->captchaImageData);
	}
	public function regenerate() {
		$length = mb_strlen(Session::get($this->captchaSessionName));
		return $this->generate($length);
	}
	public function verify($postText = "") {
		if(!empty($postText) && strtolower($postText)===strtolower(Session::get($this->captchaSessionName))) {
			return true;
		}
		return false;
	}

	private function generateString($input, $strength = 0) {
		if($strength===0) $strength = $this->captchaTextLength;
		$input_length = strlen($input);
		$random_string = '';
		for($i = 0; $i < $strength; $i++) {
			$random_character = $input[mt_rand(0, $input_length - 1)];
			$random_string .= $random_character;
		}
	  
		return $random_string;
	}
	private function generateImage($captchaText = "", $width = 150, $height = 40) {
		if(!empty($captchaText)) {
			if($this->getLength()>=10 && $width<200) $width = $this->getLength() * 24;

			$image = imagecreatetruecolor($width, $height);
			imageantialias($image, true);
			
			$colors = [];
			$red = rand(125, 175);
			$green = rand(125, 175);
			$blue = rand(125, 175);
			for($i = 0; $i < 5; $i++) {
				$colors[] = imagecolorallocate($image, $red - 20*$i, $green - 20*$i, $blue - 20*$i);
			}

			imagefill($image, 0, 0, $colors[0]);
			for($i = 0; $i < 10; $i++) {
				imagesetthickness($image, rand(2, 10));
				$line_color = $colors[rand(1, 4)];
				imagerectangle($image, rand(-8, $width - 8), rand(-8, 8), rand(-8, $width + 8), rand($height - 24, $height + 8), $line_color);
			}

			$black = imagecolorallocate($image, 0, 0, 0);
			$white = imagecolorallocate($image, 255, 255, 255);
			$textcolors = [$black, $white];

			$fonts = [FRONTEND.DS.'fonts'.DS.'captcha'.DS.'arial.ttf', FRONTEND.DS.'fonts'.DS.'captcha'.DS.'consolas.ttf'];

			for($i = 0; $i < $this->captchaTextLength; $i++) {
				$letter_space = ($width - 8) / $this->captchaTextLength;
				$initial = 8;

				imagettftext($image, rand(16, 24), rand(-15, 15), $initial + $i*$letter_space, rand(28, $height-4), $textcolors[rand(0, 1)], $fonts[array_rand($fonts)], $captchaText[$i]);
			}

			ob_start();
			imagepng($image);
			$imageData = ob_get_clean();

			return $imageData;
		}
	}

}
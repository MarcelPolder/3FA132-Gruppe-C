<?php
namespace Webapp\Core;

class Request {

	private static ?self $instance = null;

	protected 	$post = [],
				$get = [],
				$files = [],
				$request = [],
				$ajax = false,
				$main = false;

	public static function getInstance(): self {
		if(is_null(self::$instance)) self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		if(count($_POST)) {
			$this->post = $_POST;
			array_walk_recursive($this->post, 'escapeByReference');
		}

		if(count($_GET)) {
			$this->get = $_GET;
			array_walk_recursive($this->get, 'escapeByReference');
		}

		if(count($_REQUEST)) {
			$this->request = $_REQUEST;
			array_walk_recursive($this->request, 'escapeByReference');
		}

		if(count($_FILES)) {
			$this->files = filesArrayOrder($_FILES);
			array_walk_recursive($this->files, 'escapeByReference');
		}

		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->ajax = true;
		}

		if(!empty($_SERVER['HTTP_ACCEPT']) && !$this->ajax) $this->main = true;
	}

	public function getPost() {
		return $this->post;
	}

	public function getGet() {
		return $this->get;
	}

	public function getRequest() {
		return $this->request;
	}

	public function getFiles() {
		return $this->files;
	}

	public function isAjax() {
		return $this->ajax;
	}

	public function isMain() {
		return $this->main;
	}

}
<?php
// 
//  SiphonRequest.php
//  siphon
//  
//  Created by Colin Bate on 2007-02-28.
//  Copyright 2007 rhuvium Information Services. All rights reserved.
// 

/**
 * Default port for SSL connections.
 **/
define('SIPHON_DEFAULT_HTTPS_PORT', 443);

/**
 * Default port for regular HTTP connections.
 **/
define('SIPHON_DEFAULT_HTTP_PORT', 80);

/**
 * Parses an incoming URL request to get all the interesting parts out.
 *
 * @package siphon
 * @author Colin Bate
 **/
class SiphonRequest {
	private $url;
	private $service_name;
	private $extra_path;
	private $secure;
	private $auth;
	public $data;
	private $method;
	private static $curr = null;
	
	/**
	 * Creates the URL object.
	 *
	 * @return SiphonURL
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function __construct() {
		$this->secure = false;
		$this->service_name = '';
		$this->auth = array();
		$this->extra_path = array();
		$this->url = '';
		$this->data = '';
		$this->method = '';
	}
	
	/**
	 * Creates the initialized URL object.
	 *
	 * @return SiphonURL
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function initialize() {
		if (self::$curr == null) {
			$surl = new SiphonRequest();
			$surl->parse();
			self::$curr = $surl;
		}
		return self::$curr;
	}
	
	/**
	 * Parses the environment into the object.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function parse() {
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$this->secure = true;
		}
		if (($path_info = Siphon::getPathInfo()) !== false) {
			$this->service_name = array_shift($path_info);
			$this->extra_path = $path_info;
		}
		if (Siphon::validate($_SERVER['PHP_AUTH_USER'])) {
			$this->auth['username'] = $_SERVER['PHP_AUTH_USER'];
			$this->auth['password'] = $_SERVER['PHP_AUTH_PW'];
			$this->auth['type'] = $_SERVER['AUTH_TYPE'];
		}
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->getPostData(); 
	}
	
	/**
	 * Returns the name of the service.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function service() {
		return self::$curr->service_name;
	}
	
	/**
	 * Returns the POST data if present.
	 *
	 * @return mixed
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Determines whether or not there is authentication info.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function hasAuth() {
		return (count($this->auth) > 0);
	}
	
	/**
	 * Returns the truth about whether this is a secure connection.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function isSecure() {
		return $this->secure;
	}
	
	/**
	 * Returns the service name.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function serviceName() {
		return $this->service_name;
	}
	
	/**
	 * Returns the extra path info.
	 *
	 * @return array
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function extraPath() {
		return $this->extra_path;
	}
	
	/**
	 * Determines whether the QUERY_STRING matches the parameter.
	 *
	 * @return boolean
	 * @param string $match_str
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function matchQuery($match_str) {
		return (strpos($_SERVER['QUERY_STRING'], $match_str) !== false);
	}
	
	/**
	 * Returns whether or not there is extra path.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function hasExtraPath() {
		return (count($this->extra_path) > 0);
	}
	
	/**
	 * Returns the original URL as it was called.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getEndpointURL() {
		if ($this->url != '') return $this->url;
		$this->url = SiphonRequest::getCanonicalServerName($this->secure) . $_SERVER['SCRIPT_NAME'] . '/' . $this->service_name;
		return $this->url;
	}
	
	/**
	 * Returns the current server url with protocol and port number.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function getCanonicalServerName($secure = 0) {
		$sp = $_SERVER['SERVER_PORT'];
		if ($secure === 0 && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$secure = true;
		}
		$port = ($secure && $sp == SIPHON_DEFAULT_HTTPS_PORT) || (!$secure && $sp == SIPHON_DEFAULT_HTTP_PORT) ? '' : ":$sp";
		$proto = $secure ? 'https' : 'http';
		return ("$proto://" . $_SERVER['HTTP_HOST'] . $port);
	}
	
	/**
	 * Returns the truth about whether this script was posted to.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function wasPOST() {
		return ($this->method == 'POST');
	}
	
	/**
	 * Returns whether this script was requested via HTTP GET.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function wasGET() {
		return ($this->method == 'GET');
	}
	
	/**
	 * Returns true if the request method matches $method.
	 *
	 * @return boolean
	 * @param string $method
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function wasMethod($method) {
		return ($this->method == $method);
	}
	
	/**
	 * Returns true if the HTTP request method allows data to be sent.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function couldHaveData() {
		return ($this->method == 'POST' || $this->method == 'PUT');
	}
	
	/**
	 * Returns the POST data.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function getPostData() {
		global $HTTP_RAW_POST_DATA;
		if (!$this->couldHaveData()) return;
		if (!Siphon::validate($_SERVER['CONTENT_LENGTH'], "/^[0-9]+$/") || $_SERVER['CONTENT_LENGTH'] == 0) return;
		if (isset($HTTP_RAW_POST_DATA)) {
			$this->data =& $HTTP_RAW_POST_DATA;
			return;
		}
		$this->data = file_get_contents('php://input');
	}
	
} // END class SiphonRequest
?>
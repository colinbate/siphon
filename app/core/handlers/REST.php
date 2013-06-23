<?php
// 
//  REST.php
//  siphon
//  
//  Created by Colin Bate on 2007-01-13.
//  Copyright 2007 rhuvium Information Services. All rights reserved.
// 
/**
 * Handles XML-based REST requests.
 *
 * @package siphon
 * @author Colin Bate
 **/
class REST {
	private $procs;
	private $required;
	private $optional;
	private $output;
	
	/**
	 * Creates an empty REST handler.
	 *
	 * @return REST
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function __construct() {
		$this->procs = array();
		$this->required = array();
		$this->optional = array();
	}
	
	/**
	 * Registers a method or function to be handled by this REST handler.
	 *
	 * @return void
	 * @param string $method
	 * @param mixed $callback
	 * @param array $required
	 * @param array $optional
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function addHandler($method, $callback, $required = false, $optional = false, $output = false) {
		$this->procs[$method] = $callback;
		if ($required === false) $required = array();
		if ($optional === false) $optional = array();
		if ($output   === false) $output   = array();
		$this->required[$method] = $required;
		$this->optional[$method] = $optional;
		$this->output[$method] = $output;
	}
	
	/**
	 * Services the request in a RESTful way.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function service() {
		$req =& SiphonRequest::initialize();
		$xtra = $req->extraPath();
		$mname = array_shift($xtra);
		$params = $this->findParameters($mname, $xtra);
		if ($params === false) {
			// Not all parameters are present.
			$ret = self::error(SiphonErrors::NOT_ENOUGH_PARAMETERS);
		} else {
			$ret = $this->invoke($mname, $params);
		}
		echo $ret;
		// echo "<pre>\n";
		// print_r($this);
		// echo "</pre>\n";
	}
	
	/**
	 * Return the error structure of a REST request.
	 *
	 * @return string
	 * @param int $error_num
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function error($error_num) {
		$lang = SiphonErrors::$language;
		$msg = SiphonErrors::errorMessage($error_num);
		$error_msg =<<<EOXML
<?xml version="1.0"?>
<siphon>
	<error>
		<code>$error_num</code>
		<message lang="$lang">$msg</message>
	</error>
</siphon>
EOXML;
		return $error_msg;
	}
	
	/**
	 * Returns an array of parameters to pass to the function.
	 *
	 * @return array
	 * @param string $method
	 * @param array $path
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function findParameters($method, $path) {
		// Be robust: basically if there are any named parameters from the query
		//    string, insert them at the correct position in an array, then any
		//    leftovers in the path info can fill in the gaps if any.
		
		$robust = array();
		// Required parameters, without these, there is an error.
		self::debug('Checking required parameters:');
		self::debug($this->required[$method]);
		foreach ($this->required[$method] as $param) {
			if (isset($_GET[$param])) {
				$robust[] = $_GET[$param];
				self::debug("Found ($param) in GET: {$_GET[$param]}");
			} else {
				if (count($path) > 0) {
					self::debug("Found ($param) in PATH: {$path[0]}");
					$robust[] = array_shift($path);
				} else {
					// Error: Required parameters not present.
					self::debug("Did not find ($param)... error.");
					return false;
				}
			}
		}
		
		// Optional parameters, have a default value, can be missing.
		self::debug('Checking optional parameters:');
		self::debug($this->optional[$method]);
		foreach ($this->optional[$method] as $param) {
			if (isset($_GET[$param[0]])) {
				$robust[] = $_GET[$param[0]];
				self::debug("Found ($param[0]) in GET: {$_GET[$param[0]]}");
			} else {
				if (count($path) > 0) {
					self::debug("Found ($param[0]) in PATH: {$path[0]}");
					$robust[] = array_shift($path);
				} else {
					// If an optional value is not found, we can use the default, so that
					// parameters can be skipped if not named.
					$robust[] = $param[1];
					self::debug("Did not find ($param[0]) using default: $param[1]");
				}
			}
		}
		
		if (count($robust) < count($this->required[$method])) {
			// Not all required params are here, although we should have caught this already.
			return false;
		}
		return $robust;
	}
	
	/**
	 * Invokes a REST method.
	 *
	 * @return mixed
	 * @param string $method
	 * @param array $params
	 * @param array $opparams
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function invoke($method, $params) {
		@header("Content-type: text/xml");
		if (array_key_exists($method, $this->procs) && is_callable($this->procs[$method])) {
			$retval = call_user_func_array($this->procs[$method], $params);
			if (substr($retval, 0, 6) == '<?xml ') {
				return $retval;
			} else {
				return '<' . '?xml version="1.0"?' . ">\n<{$method}Response>" . $this->markup($retval, $this->output[$method]) . "</{$method}Response>";
			}
		} else {
			// Error: Method not defined or callable.
			return self::error(SiphonErrors::UNDEFINED_METHOD);
		}
	}
	
	/**
	 * Wraps the value in XML markup.
	 *
	 * @return string
	 * @param mixed $variable
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function markup($variable, $output, $ns = '') {
		$this->debug('In markup...');
		$this->debug("  variable:");
		$this->debug($variable);
		$this->debug("  output:");
		$this->debug($output);
		$ret = '';
		$wrap = $type = '';
		if ($ns != '') $ns .= ':';
		if (is_array($output)) {
			foreach ($output as $w=>$t) {
				$wrap = $w;
				$type = $t;
			}
		} else {
			$wrap = 'return';
			$type = $output;
		}
		$this->debug("  wrap=$wrap, type=$type");
		if (substr($type, 0, 4) == 'tns:') {
			// Complex.
			$this->debug("Complex type");
			if (is_array($variable)) {
				if (substr($type, 4, 6) == 'Struct') {
					// Assoc. Array
					$xml = "<{$ns}struct>\n";
					foreach ($variable as $key=>$val) {
						$xml .= "  <{$ns}$key>$val</{$ns}$key>\n";
					}
					$xml .= "</{$ns}struct>\n";
				} else {
					// Numerical Array
					$xml = "<{$ns}array>\n";
					foreach ($variable as $val) {
						$xml .= "  <{$ns}data>$val</{$ns}data>\n";
					}
					$xml .= "</{$ns}array>\n";
				}
			} else {
				// Not sure what it might be. Try a straight assignment
				$xml = $variable;
			}
		} else {
			// Simple.
			$this->debug("Simple type");
			$xml = $variable;
		}
		$this->debug(" end xml=$xml");
		$ret .= "<$ns$wrap>$xml</$ns$wrap>\n";
		return $ret;
	}
	
	/**
	 * Prints out debugging messages.
	 *
	 * @return void
	 * @param string $msg
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function debug($msg) {
		return;
		if (is_array($msg) || is_object($msg)) {
			echo "<pre>\n";
			print_r($msg);
			echo "</pre>\n";
		} else {
			echo "$msg<br>\n";
		}
	}
} // END class REST
?>
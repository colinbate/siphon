<?php
// 
//  Siphon.php
//  siphon
//  
//  Created by Colin Bate on 2006-11-19.
//  Copyright (c) 2006 rhuvium Information Services. All rights reserved.
// 

/* Define the line that will be sent with responses in the Server:/X-Server: header line. */
define('SIPHON_SERVER_ID', 'Siphon/0.9');

define('SIPHON_CLASS_EXTENSION', '.php');

// Add key Siphon directories to the search path.
$_siphon_root_dir = str_replace(DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Siphon.php', '', __FILE__);
$_doc_root = $_SERVER['DOCUMENT_ROOT'];
$_siphon_url_path = str_replace($_doc_root, '', $_siphon_root_dir);

$_siphon_curr_inc_path = ini_get('include_path');
$_siphon_curr_inc_path .= PATH_SEPARATOR . $_siphon_root_dir . DIRECTORY_SEPARATOR . 'core'
						. PATH_SEPARATOR . $_siphon_root_dir . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'handlers'
						. PATH_SEPARATOR . $_siphon_root_dir . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'wadl'
						. PATH_SEPARATOR . $_siphon_root_dir . DIRECTORY_SEPARATOR . 'services';
ini_set('include_path', $_siphon_curr_inc_path);

// Only needed for DEVELOPMENT Environment.
// TODO: Remove when moving.
define('SIPHON_BASE_URL', SiphonRequest::getCanonicalServerName($_SERVER['HTTPS'] == 'on') . '/siphon/');

if (!defined('SIPHON_BASE_URL'))
	define('SIPHON_BASE_URL', SiphonRequest::getCanonicalServerName($_SERVER['HTTPS'] == 'on') . $_siphon_url_path . DIRECTORY_SEPARATOR);
if (!defined('SIPHON_BASE_PATH'))
	define('SIPHON_BASE_PATH', $_siphon_root_dir . DIRECTORY_SEPARATOR);

define('SIPHON_SERVICE_DIR', SIPHON_BASE_PATH . 'services' . DIRECTORY_SEPARATOR);
define('SIPHON_TEMPLATE_DIR', SIPHON_BASE_PATH . 't' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR);
//require_once('Service.php');
$_siphon_service = '';
@header("Server: " . SIPHON_SERVER_ID);
@header("X-Server: " . SIPHON_SERVER_ID);
require_once('handlers' . DIRECTORY_SEPARATOR . 'nusoap.php');

/**
 * Identifies that this service is using SOAP.
 **/
define('SIPHON_SOAP', 1);

/**
 * Identifies that this service is using XMLRPC.
 **/
define('SIPHON_XMLRPC', 2);

/**
 * Identifies that this service is using a REST mechanism.
 **/
define('SIPHON_REST', 4);

/**
 * Loads the classes as needed.
 *
 * @return void
 * @author Colin Bate <colin@rhuvium.com>
 **/
function __autoload($classname) {
	if (Siphon::fileOnPath($classname . SIPHON_CLASS_EXTENSION)) {
		require_once($classname . SIPHON_CLASS_EXTENSION);
	} else {
		require_once('SiphonErrors.php');
		SiphonErrors::page(SiphonErrors::getException(SiphonErrors::MISSING_COMPONENT));
	}
}

/**
 * Library Class for system
 *
 * @package siphon
 * @author Colin Bate
 **/
class Siphon {
	/**
	 * Validates variables.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function validate($variable, $regex="", $range="") {
		$rval = false;
		if (isset($variable)) {
			if (trim($variable) != '') {
				if ($regex == '') {
					$rval = true;
				} else {
					if (preg_match($regex, $variable)) {
						if ($range == '') {
							$rval = true;
						} else {
							list($lower, $upper) = explode('|', $range);
							if ($variable >= $lower && $variable <= $upper) {
								$rval = true;
							}
						}
					}
				}
			}
		}
		return $rval;
	}
	
	/**
	 * Returns the full path of a file if it is on the include path.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function fileOnPath($file) {
		$paths = explode(PATH_SEPARATOR, get_include_path());
		foreach ($paths as $path) {
			// Formulate the absolute path
			$fullpath = $path . DIRECTORY_SEPARATOR . $file;
			if (file_exists($fullpath)) {
				return $fullpath;
			}
		}
		return false;
	}
	
	/**
	 * Parses and returns the path info from the URI.
	 *
	 * @return array
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function getPathInfo($pieces = 0) {
		if (Siphon::validate($_SERVER['PATH_INFO'])) {
			$path = substr($_SERVER['PATH_INFO'], 1);
			if ($path == '') return false;
			$pl = strlen($path);
			if ($path{$pl-1} == '/') {
				// Check to ensure that $path doesn't have a trailing slash.
				$path = substr($path, 0, $pl - 1);
			}
			if ($pieces != 0) {
				return explode('/', $path, $pieces);
			} else {
				return explode('/', $path);
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Determines whether the function is RESTful.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function isRESTStyle() {
		
	}
	
	/**
	 * Returns an array of the possible operations a service provides.
	 *
	 * @return array
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function getOperations($class) {
		$s_ops = get_class_methods($class);
		$b_ops = get_class_methods('Service');
		return array_diff($s_ops, $b_ops, array('__construct', '__toString', '__destruct', '__call', '__set', '__get', $class));
	}
	
	/**
	 * Tries to determine whether or not the XML data is an XMLRPC call.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function isXMLRPC(&$data) {
		return (stripos($data, '<methodcall>') !== false);
	}
	
	/**
	 * Setup the nusoap server to handle requests.
	 *
	 * @return soap_server
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function setupSoapServer(&$service) {
		// Create SOAP server object
		$soap = new soap_server();

		// Setup WSDL file
		$soap->configureWSDL($service->class_name, $service->url . '?wsdl');
		$soap->wsdl->schemaTargetNamespace = $service->url . '?wsdl';
		
		// Setup datatypes.
		$soap->wsdl->addComplexType('ArrayOfstring', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'string[]')), 'xsd:string');
		$soap->wsdl->addComplexType('ArrayOfint', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'int[]')), 'xsd:int');
		$soap->wsdl->addComplexType('ArrayOffloat', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'float[]')), 'xsd:float');

		$service->bindToServer($soap, SIPHON_SOAP);
		return $soap;
	}
	
	/**
	 * Returns a list of services available in this Siphon install.
	 *
	 * @return array
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function findServices() {
		$service_dir = SIPHON_BASE_PATH . 'services';
		$potential = scandir($service_dir);
		$services = array();
		foreach ($potential as $item) {
			if (substr($item, 0, 1) != '.' && is_dir($service_dir . '/' . $item)) {
				$services[] = $item;
			}
		}
		return $services;
	}
} // END public class Siphon
?>
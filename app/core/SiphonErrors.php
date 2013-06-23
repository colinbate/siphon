<?php
// 
//  SiphonErrors.php
//  siphon
//  
//  Created by Colin Bate on 2007-03-05.
//  Copyright 2007 rhuvium Information Services. All rights reserved.
// 

/**
 * Container class for error related methods and functions.
 *
 * @package siphon
 * @author Colin Bate
 **/
class SiphonErrors {
	// Error constants.
	const PARSE_ERROR = 1;
	const UNDEFINED_METHOD = 2;
	const NOT_ENOUGH_PARAMETERS = 4;
	const INVALID_SERVICE = 8;
	const MISSING_COMPONENT = 16;
	
	// Class variables
	public static $language = 'en';
	
	/**
	 * Hidden constructor.
	 *
	 * @return SiphonErrors
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function __construct() { }
	
	/**
	 * Sets the language of the written error message if multiple languages are defined.
	 *
	 * @return boolean
	 * @param string $lang
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function setLanguage($lang) {
		if ($lang != 'en' && $lang != 'es') {
			return false;
		}
		self::$language = $lang;
		return true;
	}
	
	/**
	 * Returns a PHP Exception based on the error code.
	 *
	 * @return Exception
	 * @param int $code
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function getException($code) {
		return new Exception(self::errorMessage($code), $code);
	}
	
	/**
	 * Returns the error string associated with an error number.
	 *
	 * @return string
	 * @param int $error_num
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function errorMessage($error_num) {
		$en = array(self::PARSE_ERROR           => 'Could not parse the request.',
					self::UNDEFINED_METHOD      => 'Method name provided does not exist or cannot be called.',
					self::NOT_ENOUGH_PARAMETERS => 'Not enough parameters provided for the method.',
					self::INVALID_SERVICE       => 'This service does not exist on this server.',
					self::MISSING_COMPONENT     => 'A required file from within Siphon is missing.');
		$es = array(self::PARSE_ERROR           => 'La petición no se pudo analizar sintácticamente.',
					self::UNDEFINED_METHOD      => 'El método no existe o no puede ser llamado.',
					self::NOT_ENOUGH_PARAMETERS => 'No se proporcionaron suficientes parámetros para el método.',
					self::INVALID_SERVICE       => 'Este servicio no existe en este servidor.',
					self::MISSING_COMPONENT     => 'Un archivo necesario de Siphon está extraviado.');
		$errors = array('en' => $en, 'es' => $es);
		return $errors[self::$language][$error_num];
	}
	
	/**
	 * Displays an error page in the most appropriate format.
	 *
	 * @return void
	 * @param Exception $e
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function page($e) {
		$error_message = $e->getMessage();
		@include(SIPHON_TEMPLATE_DIR . 'error.inc');
		exit();
	}
	
} // END class SiphonErrors
?>
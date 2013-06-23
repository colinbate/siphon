<?php
// 
//  Test.php
//  siphon
//  
//  Created by Colin Bate on 2007-03-26.
//  Copyright 2007 rhuvium Information Services. All rights reserved.
// 

/**
 * Provides a set of test methods for use as operations.
 *
 * @package siphon
 * @author Colin Bate
 **/
class Test {
	/**
	 * Returns a string describing how the service is being accessed.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function how() {
		$r = 'I have no idea.';
		if (SiphonService::$access == SIPHON_SOAP) {
			$r = 'SOAP';
		} elseif (SiphonService::$access == SIPHON_XMLRPC) {
			$r = 'XML-RPC';
		} elseif (SiphonService::$access == SIPHON_REST) {
			$r = 'REST';
		}
		return $r;
	}
	
	/**
	 * Analyses the complex structure.
	 *
	 * @return {first:string,last:string}
	 * @param {first:string,last:string} $complex
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function flip($complex) {
		$tmp = $complex['first'];
		$complex['first'] = $complex['last'];
		$complex['last'] = $tmp;
		return $complex;
	}
	
	/**
	 * Returns an XML structure.
	 *
	 * @return string
	 * @param string $name
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function name__rest($name) {
		return "<name>$name</name>\n";
	}
	
} // END class Test
?>
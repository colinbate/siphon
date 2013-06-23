<?php
//
//  XMLRPC.inc
//  siphon
//
//  Created by Colin Bate on 2006-10-04.
//  Copyright (c) 2006 rhuvium Information Services. All rights reserved.
//
//  Code originally written for echo! my content management platform,
//  but put to good use here, in Siphon.
//

/**
 * Class that handles XMLRPC interpretation.
 *
 * @package siphon
 * @author Colin Bate <colin@rhuvium.com>
 **/
class XMLRPC {
	var $error_string;
	var $error_line;
	var $depth;
	var $procs;
	var $data;
	var $method;
	var $params;
	var $_var;
	var $_value;
	var $_param;
	var $_datadepth;
	var $required;
	var $optional;
	
	/**
	 * Constructor to initialize some arrays, etc.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function XMLRPC() {
		$this->procs = array();
	}
	
	/**
	 * Converts a PHP variable into an XML string for sending.
	 *
	 * @return string
	 * @param var the php variable to convert
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function varToXML($var) {
		if (XMLRPC::is_obj($var, 'eBase64')) {
			return "<value><base64>" . $var->getEncoded() . "</base64></value>";
		}
		if (XMLRPC::is_obj($var, 'eDateIso')) {
			return "<value><dateTime.iso8601>" . $var->getDateString() . "</dateTime.iso8601></value>\n";
		}
		if (XMLRPC::is_struct($var)) {
			$ret = "<value>\n";
			$ret .= "  <struct>\n";
			foreach ($var as $name=>$val) {
				$ret .= "    <member>\n";
				$ret .= "      <name>$name</name>\n";
				$ret .= "      " . XMLRPC::varToXML($val);
				$ret .= "    </member>\n";
			}
			$ret .= "  </struct>\n";
			$ret .= "</value>\n";
			return $ret;
		}
		if (is_array($var)) {
			$ret = "<value>\n";
			$ret .= "  <array>\n";
			$ret .= "    <data>\n";
			foreach ($var as $i) {
				$ret .= "      " . XMLRPC::varToXML($i);
			}
			$ret .= "    </data>\n";
			$ret .= "  </array>\n";
			$ret .= "</value>\n";
			return $ret;
		} else if (is_bool($var)) {
			return "<value><boolean>$var</boolean></value>\n";
		} else if (is_numeric($var)) {
			$var = ($var + 1) - 1;
			if (is_int($var)) {
				return "<value><int>$var</int></value>\n";
			} else if (is_double($var)) {
				return "<value><double>$var</double></value>\n";
			}
		}
		$var = str_replace('&', '&amp;', $var);
		$var = str_replace('<', '&lt;', $var);
		return "<value><string>$var</string></value>\n";
	}
	
	/**
	 * Determined whether an array is a struct.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function is_struct($var) {
		return (is_array($var) && !is_numeric(implode('', array_keys($var))));
	}

	/**
	 * Checks that an object is an object and of a certain class.
	 *
	 * @return boolean
	 * @param obj the object to check
	 * @param check the name of class that is chould be
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function is_obj(&$obj, $check=false) {
		return (is_object($obj) && (!$check || strtolower($check) == strtolower(get_class($obj))));
	}
	
	/**
	 * Handle the start elements in the XML data.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function startElement($parser, $name, $attrs) {
		
		$this->data = '';
		
		if ($name == 'PARAMS') {
			$this->params = array();
			$this->_param = 0;
		} else if ($name == 'STRUCT') {
			$this->params[$this->_param] .= "array(";
		} else if ($name == 'PARAM') {
			$this->params[$this->_param] = '';
		} else if ($name == 'ARRAY') {
			$this->params[$this->_param] .= "array(";
		} else if ($name == 'STRING') {
			$this->_var = 'STRING';
		} else if ($name == 'DATA') {
			$this->_datadepth[] = ($this->depth + 1);
		}

		$this->depth++;
	}
	
	/**
	 * Handle the end tags of the XML elements.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function endElement($parser, $name) {
		
		if ($name == 'METHODNAME') {
			$this->method = $this->data;
		} else if ($name == 'PARAM') {
			$this->_param++;
		} else if ($name == 'I4' || $name == 'INT') {
			$this->params[$this->_param] .= intval($this->data);
		} else if ($name == 'DOUBLE') {
			$this->params[$this->_param] .= doubleval($this->data);
		} else if ($name == 'BOOLEAN') {
			if ($this->data[0] == 'f' || $this->data[0] == 'F') $this->data = 0;
			$this->params[$this->_param] .= ((boolean)$this->data) ? 'true' : 'false';
		} else if ($name == 'BASE64') {
			$this->data = str_replace('\\', '\\\\', $this->data);
			$this->data = str_replace('\'', '\\\'', $this->data);
			$this->params[$this->_param] .= "base64_decode('$this->data')";
		} else if ($name == 'DATETIME.ISO8601') {
			$this->data = str_replace('\\', '\\\\', $this->data);
			$this->data = str_replace('\'', '\\\'', $this->data);
			$this->data = str_replace('T', ' ', $this->data);
			$this->params[$this->_param] .= "strtotime('$this->data')";
		} else if ($name == 'MEMBER') {
			$this->params[$this->_param] .= ', ';
		} else if ($name == 'STRUCT') {
			$this->params[$this->_param] .= ')';
		} else if ($name == 'STRING') {
			$this->data = str_replace('\\', '\\\\', $this->data);
			$this->data = str_replace('\'', '\\\'', $this->data);
			$this->params[$this->_param] .= "'$this->data'";
			$this->_var = '';
		} else if ($name == 'NAME') {
			$this->data = str_replace('\\', '\\\\', $this->data);
			$this->data = str_replace('\'', '\\\'', $this->data);
			$this->params[$this->_param] .= "'$this->data' => ";
		} else if ($name == 'ARRAY') {
			$this->params[$this->_param] .= ')';
		} else if ($name == 'VALUE') {
			if ($this->data != '') {
				$this->data = str_replace('\\', '\\\\', $this->data);
				$this->data = str_replace('\'', '\\\'', $this->data);
				$this->params[$this->_param] .= "'$this->data'";
			}
			$cdd = count($this->_datadepth);
			//$this->params[$this->_param] .= '/* d=' . $this->depth . ' */';
			if ($cdd > 0 && $this->depth == ($this->_datadepth[$cdd - 1]) + 1) {
				$this->params[$this->_param] .= ', ';
			}
		} else if ($name == 'DATA') {
			array_pop($this->_datadepth);
		}
		
		$this->depth--;
		$this->data = '';
	}
	
	/**
	 * Handle the character data in the XML stream.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function charData($parser, $cdata) {
		if ($this->_var != 'STRING') {
			$cdata = trim($cdata);
		}
		$this->data .= $cdata;
	}
	
	/**
	 * Indents the output to the current depth.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function indent() {
		for ($i = 0; $i < $this->depth; $i++) {
			echo "  ";
		}		
	}

	/**
	 * Sets the object that will handle the methods.
	 *
	 * @return void
	 * @param remote the procedure callback to add.
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function addHandler($method, $handler, $required = false, $optional = false) {
		$this->procs[$method] = $handler;
		if ($required === false) $required = array();
		if ($optional === false) $optional = array();
		$this->required[$method] = $required;
		$this->optional[$method] = $optional;
	}
	
	/**
	 * Return the list of currently registered handlers.
	 *
	 * @return array
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function getSupportedMethods() {
		return array_keys($this->procs);
	}
	
	/**
	 * Generates the XML for a fault message.
	 *
	 * @return string
	 * @param code the numeric error code
	 * @param msg the textual error message
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function getFault($code, $msg = false) {
		$smsg = SiphonErrors::errorMessage($code);
		if ($smsg != '' && $msg === false) $msg = $smsg;
		if ($msg === false || $msg == '') {
			$msg = 'An error occured.';
		}
		$ret = '<' . '?xml version="1.0"?' . '>' . "\n";
		$ret .= "<methodResponse>\n";
		$ret .= "  <fault>\n";
		$ret .= "    <value>\n";
		$ret .= "      <struct>\n";
		$ret .= "        <member>\n";
		$ret .= "          <name>faultCode</name>\n";
		$ret .= "          <value><int>$code</int></value>\n";
		$ret .= "        </member>\n";
		$ret .= "        <member>\n";
		$ret .= "          <name>faultString</name>\n";
		$ret .= "          <value><string>$msg</string></value>\n";
		$ret .= "        </member>\n";
		$ret .= "      </struct>\n";
		$ret .= "    </value>\n";
		$ret .= "  </fault>\n";
		$ret .= "</methodResponse>\n";
		return $ret;
	}
	
	/**
	 * Wraps the result variable in XML container.
	 *
	 * @return void
	 * @param res the XML variable representation to wrap
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function wrapResult($res) {
		$ret = '<' . '?xml version="1.0"?' . '>' . "\n";
		$ret .= "<methodResponse>\n";
		$ret .= "  <params>\n";
		$ret .= "    <param>\n";
		$ret .= '      ' . $res;
		$ret .= "    </param>\n";
		$ret .= "  </params>\n";
		$ret .= "</methodResponse>\n";
		return $ret;
	}
	
	/**
	 * Converts an XML-RPC documents into an actual method call.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function xmlMethodCall($xml) {
		$this->data = '';
		$this->method = '';
		$this->params = array();
		
		$xml_parser = xml_parser_create();
		xml_set_object($xml_parser, $this);
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, 'charData');
		$ret = '';
		if (!xml_parse($xml_parser, $xml, true)) {
			$this->error_string = xml_error_string(xml_get_error_code($xml_parser));
			$this->error_line   = xml_get_current_line_number($xml_parser);
			$ret = $this->getFault(1, $this->error_string);
			xml_parser_free($xml_parser);
			return $ret;
		}
		
		//echo "Call: {$this->method} (\n";
		for ($i = 0; $i < count($this->params); $i++) {
			//echo "  {$this->params[$i]}\n";
			$e = '$x = ' . $this->params[$i] . ';';
			eval($e);
			$this->params[$i] = $x;
			//print_r($this->params[$i]);
			//echo "\n";
		}

		if (count($this->params) < count($this->required[$this->method])) {
			$ret = $this->getFault(SiphonErrors::NOT_ENOUGH_PARAMETERS);
		} else if (array_key_exists($this->method, $this->procs) && is_callable($this->procs[$this->method])) {
			// Call method and encode results as XML to return.
			$retval = call_user_func_array($this->procs[$this->method], $this->params);
			if (is_string($retval) && substr($retval, 0, 5) == '<' . '?xml') {
				$ret = $retval;
			} else {
				$ret = $this->wrapResult($this->varToXML($retval));
			}
		} else {
			// Return a fault XML structure.
			$ret = $this->getFault(SiphonErrors::UNDEFINED_METHOD);
		}
		
		xml_parser_free($xml_parser);
		return $ret;
	}
	
	/**
	 * Returns the error message that may exist if there was a problem.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function getError() {
		if ($this->error_string == '') {
			return 'No errors.';
		}
		return "XML error: $this->error_string at line $this->error_line\n";
	}
	
	/**
	 * Handles the data being sent.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function service(&$data) {
		$xml_response = $this->xmlMethodCall($data);
		// Save output for debugging.
		$xf = fopen('/tmp/siphon-out-xmlrpc.txt', 'a');
		fwrite($xf, date('j F Y H:i:s') . "-----------------\n");
		fwrite($xf, $xml_response);
		fwrite($xf, "\n");
		fclose($xf);
		header('Content-Type: text/xml');
		echo $xml_response;
	}
	
} // END class XMLRPC

/**
 * Date class to encapsulate the ISO8601 Dates.
 *
 * @package echo
 * @author Colin Bate <colin@rhuvium.com>
 **/
class eDateIso {
	var $ts;
	
	function eDateIso($dval) {
		if (is_numeric($dval)) {
			// Likely a timestamp.
			$this->ts = $dval;
		} else {
			$dval = str_replace('T', ' ', $dval);
			$this->ts = strtotime($dval);
		}
	}
	
	/**
	 * Returns the date in ISO8601 format.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function getDateString() {
		return date('Ymd\TH:i:s', $this->ts);
	}
} // END class eDateIso

/**
 * A convenience class around Base 64 content.
 *
 * @package echo
 * @author Colin Bate <colin@rhuvium.com>
 **/
class eBase64 {
	var $content;
	var $encoded;
	
	/**
	 * Constructor for the class.
	 *
	 * @return void
	 * @param content the content to be encoded
	 * @param encoded flag to determine whether content is encoded
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function eBase64($content, $encoded=false) {
		$this->content = $content;
		$this->encoded = $encoded;
	}
	
	/**
	 * Rertrieve the plain text version of the data.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function getPlain() {
		if ($encoded) {
			return base64_decode($this->content);
		} else {
			return $this->content;
		}
	}
	
	/**
	 * Retrieve the base64 encoded data string.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	function getEncoded() {
		if ($encoded) {
			return $this->content;
		} else {
			return base64_encode($this->content);
		}
	}
} // END class eBase64

?>
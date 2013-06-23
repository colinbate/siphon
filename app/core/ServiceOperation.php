<?php
/**
 * Defines the default type to use if no type information is found.
 **/
define('SIPHON_DEFAULT_TYPE', 'xsd:string');

/**
 * Represents a WSDL operation.
 *
 * @package siphon
 * @author Colin Bate
 **/
class ServiceOperation {
	public $name;
	public $location;
	private $service;
	private $input;
	private $output;
	private $params;
	private $desc;
	private $exposed_name;
	private $visibility;
	private $required;
	private $optional;
	public $author;
	
	/**
	 * Private constructor to maintain control over production.
	 *
	 * @return ServiceOperation
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function __construct() {
		
	}
	
	/**
	 * Factory method for the class.
	 *
	 * @return ServiceOperation
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function create($name, SiphonService &$service, $location = false) {
		$op = new ServiceOperation();
		$op->name = $name;
		if (preg_match("/(.*)__(soap|rest|xmlrpc)$/", $name, $match)) {
			$op->exposed_name = $match[1];
			switch ($match[2]) {
				case 'rest':
					$op->visibility = SIPHON_REST;
					break;
				case 'soap':
					$op->visibility = SIPHON_SOAP;
					break;
				case 'xmlrpc':
					$op->visibility = SIPHON_XMLRPC;
					break;
				default:
					$op->visibility = 0;
			}
		} else {
			$op->exposed_name = $name;
			$op->visibility = 0;
		}
		$op->service = $service;
		$op->location = $location;
		$op->input = array();
		$op->output = array();
		$op->params = array();
		$op->required = array();
		$op->optional = array();
		$op->determineIO();
		if ($location === false) {
			$op->location = $service->url . "/$name";
		}
		return $op;
	}
	
	/**
	 * Sets the inputs and output based on reflection and DocComments.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function determineIO() {
		$ref = new ReflectionMethod($this->service->class_name, $this->name);
		
		$plist = $ref->getParameters();
		foreach ($plist as $p) {
			$this->input[$p->getName()] = SIPHON_DEFAULT_TYPE;
			if ($p->isOptional()) {
				$this->optional[] = array($p->getName(), $p->getDefaultValue());
			} else {
				$this->required[] = $p->getName();
			}
		}
		$this->output = array('return' => SIPHON_DEFAULT_TYPE);
		$doc = $ref->getDocComment();
		$doc_bits = explode('*', $doc);
		foreach ($doc_bits as $bit) {
			if (trim($bit) == '' || trim($bit) == '/') continue;
			$tbit = trim($bit);
			if ($tbit{0} == '@') {
				if (($pos = strpos($tbit, ' ')) !== false) {
					$keyword = substr($tbit, 0, $pos);
					$value = substr($tbit, $pos+1);
					if ($keyword == '@return') {
						$this->output = array('return' => $this->getDescType($value));
					} elseif ($keyword == '@param') {
						$tokens = preg_split('/\s+/', $value);
						$this->params[] = $tokens[1];
						$wtype = $this->getDescType($tokens[0]);
						$this->input[str_replace('$', '', $tokens[1])] = $wtype;
					}
				}
			} else {
				if ($this->desc != '') {
					$this->desc .= "\n$tbit";
				} else {
					$this->desc = $tbit;
				}
			}
		}
	}
	
	/**
	 * Return string representation of the operation.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function __toString() {
		$ret = "$this->output <strong>$this->name</strong> (";
		foreach ($this->input as $in) {
			$ret .= "$in, ";
		}
		$ret .= ") [$this->location]";
		return $ret;
	}
	
	/**
	 * Returns the description of this operation.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getDesc() {
		return $this->desc;
	}
	
	/**
	 * Return the input parameters.
	 *
	 * @return array
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getInputs() {
		return $this->input;
	}
	
	/**
	 * Returns whether this operation is only for one of the types of interface.
	 *
	 * @return int
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getVisibility() {
		return $this->visibility;
	}
	
	/**
	 * Returns whether the current operation is visible to the specified type.
	 *
	 * @return boolean
	 * @param int $type
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function isVisible($type) {
		return ($this->visibility == 0 || $this->visibility == $type);
	}
	
	/**
	 * Returns the exposed name of the method.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function exposedName() {
		return $this->exposed_name;
	}
	
	/**
	 * Returns whether a parameter is required or not for this operation.
	 *
	 * @return boolean
	 * @param string $parameter
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function requiredParam($parameter) {
		return (in_array($parameter, $this->required));
	}
	
	/**
	 * Registers the operation with the server.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function register(&$server, $type = SIPHON_SOAP) {
		// method__rest
		if (!$this->isVisible($type)) return false;
		$s_class = $this->service->class_name;
		if ($type == SIPHON_SOAP) {
			return $server->register("$this->exposed_name", $this->input, $this->output, $this->location, "$this->location#$this->exposed_name", $this->service->type, $this->service->uses, $this->desc);
		} elseif ($type == SIPHON_XMLRPC) {
			$server->addHandler("$this->exposed_name", array(SiphonService::$target, $this->name), $this->required, $this->optional);
			return true;
		} elseif ($type == SIPHON_REST) {
			$server->addHandler("$this->exposed_name", array(SiphonService::$target, $this->name), $this->required, $this->optional, $this->output);
			return true;
		}
	}
	
	/**
	 * Returns the WSDL/Schema type corresponding to the doc description.
	 *
	 * @return string
	 * @param string $var
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getDescType($var) {
		if ($var == 'string' || $var == 'mixed') {
			$type = 'xsd:string';
		} elseif ($var == 'int' || $var == 'integer') {
			$type = 'xsd:integer';
		} elseif ($var == 'float' || $var == 'boolean' || $var == 'double') {
			$type = "xsd:$var";
		} elseif (strtolower($var) == 'arrayofstring' || $var == 'string[]' || $var == 'array') {
			$type = 'tns:ArrayOfstring';
		} elseif (strtolower($var) == 'arrayofint' || $var == 'int[]') {
			$type = 'tns:ArrayOfint';
		} elseif (strtolower($var) == 'arrayoffloat' || $var == 'float[]') {
			$type = 'tns:ArrayOffloat';
		} elseif (substr($var, 0, 1) == '{') {
			if (($type = $this->service->isExistingType($var)) === false) {
				$nt = array();
				$tcomp = '';
				$vt = str_replace(array('{', '}'), '', $var);
				$keys = explode(',', $vt);
				foreach ($keys as $key) {
					list($n,$t) = explode(':', $key);
					$nt[$n] = array('name' => $n, 'type' => $this->getDescType($t));
					if ($t == '') $t = 'string';
					$tcomp .= ucfirst($t);
				}
				$type = $this->service->addComplexType($var, $nt, $tcomp);
			}
		} elseif (strpos($var, ':') > 0) {
				$type = $var;
		} else {
			$type = SIPHON_DEFAULT_TYPE;
		}
		return $type;
	}
	
} // END class Operation
?>
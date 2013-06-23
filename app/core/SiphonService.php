<?php
/**
 * The core Siphon Service representation.
 *
 * @package siphon
 * @author Colin Bate
 **/
class SiphonService {
	public $name;
	public $url;
	public $type;
	public $uses;
	private $operations;
	private $complex;
	private $cplid;
	public $desc;
	public $author;
	public $package;
	public $class_name;
	public static $access;
	public static $target = null;
	private static $me = null;
	
	/**
	 * Sets up the basic structure of the Service.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	protected function __construct($url = false) {
		$this->setType('rpc');
		if ($url !== false) $this->setURL($url);
		$this->complex = array();
		$this->cplid = 0;
		$this->determineOperations();
	}
	
	/**
	 * Identify the Service in a Generic manner.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function __toString() {
		$ret = "Service: $this->name [$this->url]<br>\n";
		$ret .= "<ul>\n";
		foreach ($this->operations as $ops) {
			$ret .= "<li>" . $ops->__toString() . "</li>\n";
		}
		$ret .= "</ul>\n";
		return $ret;
	}
	
	/**
	 * Sets the URL
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function setURL($url) {
		$this->url = $url;
	}
	
	/**
	 * Sets the name of the service.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function setName($name) {
		$this->name = $name;
	}
	
	/**
	 * Returns the specified operation.
	 *
	 * @return ServiceOperation
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function getOperation($index) {
		return $this->operations[$index];
	}
	
	/**
	 * Returns the number of operations this service provides.
	 *
	 * @return int
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function numberOfOperations() {
		return count($this->operations);
	}
	
	/**
	 * Sets the operations based on reflection and DocComments.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function determineOperations() {
		$me = get_class(self::$target);
		$this->class_name = $me;
		$ref = new ReflectionClass($me);
		$this->operations = array();
		foreach ($ref->getMethods() as $op) {
			if ($op->isPublic() && !$op->isStatic() && !$op->isConstructor() && substr($op->getName(), 0, 1) != '_') {
				$this->operations[] = ServiceOperation::create($op->getName(), $this);
			}
		}
		$doc = $ref->getDocComment();
		$doc_bits = explode('*', $doc);
		foreach ($doc_bits as $bit) {
			if (trim($bit) == '' || trim($bit) == '/') continue;
			$tbit = trim($bit);
			if ($tbit{0} == '@') {
				if (($pos = strpos($tbit, ' ')) !== false) {
					$keyword = substr($tbit, 0, $pos);
					$value = substr($tbit, $pos+1);
					if ($keyword == '@author') {
						$this->author = $value;
					} elseif ($keyword == '@package') {
						$this->package = $value;
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
	 * Binds the service descriptions to the server.
	 *
	 * @return boolean
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function bindToServer(&$server, $type = SIPHON_SOAP) {
		self::$access = $type;
		if ($type == SIPHON_SOAP) $this->registerTypes($server);
		foreach ($this->operations as $ops) {
			$ops->register($server, $type);
		}
	}
	
	/**
	 * Registers the found complex types with the server.
	 *
	 * @return void
	 * @param string $server
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function registerTypes(&$server) {
		foreach ($this->complex as $newtype) {
			$server->wsdl->addComplexType($newtype['id'], 'complexType', 'struct', 'all', '', $newtype['def']);
		}
	}
	
	/**
	 * Sets the type of the service: (document|rpc)
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function setType($type) {
		if ($type == 'document') {
			$this->type = 'document';
			$this->uses = 'literal';
		} else {
			$this->type = 'rpc';
			$this->uses = 'encoded';
		}
	}
	
	/**
	 * Adds a complex type into the system.
	 *
	 * @return string
	 * @param string $sig
	 * @param array $definition
	 * @param string $comp
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function addComplexType($sig, $definition, $comp='') {
		$this->cplid++;
		$id = str_replace(':', '', "Struct{$comp}{$this->cplid}");
		$this->complex[$sig] = array('id' => $id, 'def' => $definition);
		return "tns:$id";
	}
	
	/**
	 * Checks if a given complex type exists already.
	 *
	 * @return mixed
	 * @param string $sig
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function isExistingType($sig) {
		if (array_key_exists($sig, $this->complex)) {
			return 'tns:' . $this->complex[$sig]['id'];
		}
		return false;
	}
	
	/**
	 * Returns the Service object for the current service.
	 *
	 * @return object
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function createFromRequest() {
		$request = SiphonRequest::initialize();
		$url = $request->getEndpointURL();
		if ($request->serviceName() !== '') {
			$_service_name = $request->serviceName();
			$_siphon_class = ucfirst($_service_name);
			$file_to_require = "$_service_name/$_siphon_class" . SIPHON_CLASS_EXTENSION;
			if (file_exists(SIPHON_SERVICE_DIR . $file_to_require)) {
				require_once($file_to_require);
			} else {
				// This service doesn't exist.
				SiphonErrors::page(SiphonErrors::getException(SiphonErrors::INVALID_SERVICE));
			}
		} else {
			// Handle a serviceless response.
			// Lists all the available services.
			$sl = Siphon::findServices();
			$services = '<ul class="servicelist">';
			$bu = SiphonRequest::getCanonicalServerName() . $_SERVER['SCRIPT_NAME'];
			foreach ($sl as $serv) {
				$services .= "<li><a href=\"$bu/$serv\">$serv</a></li>\n";
			}
			$services .= "</ul>\n";
			@include(SIPHON_TEMPLATE_DIR . 'listing.inc');
			exit();
		}
		try {
			self::$target = new $_siphon_class();
		} catch (Exception $e) {
			echo $e;
			exit();
		}
		self::$me = new SiphonService($url);
		$serv_name = self::$target->name;
		if ($serv_name == '') {
			$serv_name = $_siphon_class;
		}
		self::$me->setName($serv_name);
		if ($request->matchQuery('doc')) {
			self::$me->setType('document');
		}
		return self::$me;
	}
	
	/**
	 * Returns the current service if it has been created.
	 *
	 * @return SiphonService
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public static function getCurrent() {
		if (self::$me == null) {
			return self::createFromRequest();
		}
		return self::$me;
	}
} // END public class SiphonService
?>
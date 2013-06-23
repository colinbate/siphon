<?php
// 
//  WADLGenerator.php
//  siphon
//  
//  Created by Colin Bate on 2007-02-01.
//  Copyright 2007 rhuvium Information Services. All rights reserved.
// 
/**
 * Generates WADL file based on a Service.
 *
 * @package siphon
 * @author Colin Bate
 **/
class WADLGenerator {
	private $service;
	private $premade;
	// TODO: Define the concept of a resource in Siphon.
	/**
	 * Create the generator.
	 *
	 * @return WADLGenerator
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function __construct(&$service) {
		$this->service = $service;
		$this->premade = '';
		// Check for a service.wadl file in the service folder.
		$sname = SiphonRequest::service();
		$wadl_path = SIPHON_BASE_PATH . 'services' . DIRECTORY_SEPARATOR . $sname . DIRECTORY_SEPARATOR . 'service.wadl';
		if (file_exists($wadl_path)) {
			// A premade WADL exists -- use it.
			$this->premade = $wadl_path;
		}
	}
	
	/**
	 * Outputs the WADL file.
	 *
	 * @return void
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function output() {
		//header('Content-type: application/vnd.sun.wadl+xml');
		header('Content-type: text/xml');
		if ($this->premade != '') {
			$bytes = @readfile($this->premade);
			if ($bytes == 0) {
				echo $this->generate();
			}
		} else {
			echo $this->generate();
		}
	}
	
	/**
	 * Returns the entire XML WADL file.
	 *
	 * @return string
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function generate() {
		// Create XML document.
		$wadl = new DOMDocument('1.0', 'UTF-8');
		
		// Create root <application> element.
		$app = $wadl->createElementNS('http://research.sun.com/wadl/2006/10', 'application');
		$wadl->appendChild($app);
		
		// Add overall documentation.
		$doc =& $this->makeElement($wadl, 'doc', array('xml:lang' => 'en', 'title' => $this->service->name), $this->service->desc);
		$app->appendChild($doc);
		
		// Add <grammers>.
		
		// Add <resources>.
		$res =& $this->resources($wadl);
		if ($res != null) $app->appendChild($res);
		
		// Return the whole XML file.
		return $wadl->saveXML();
	}
	
	/**
	 * Create a new element with specified attributes.
	 *
	 * @return DOMElement
	 * @param DOMDocument $dom
	 * @param string $name
	 * @param array $attrs
	 * @param string $content
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function &makeElement(&$dom, $name, $attrs, $content = false) {
		$elem = $dom->createElement($name);
		foreach($attrs as $n=>$v) {
			$elem->setAttribute($n, $v);
		}
		if ($content !== false) {
			$elem->appendChild(new DOMText($content));
		}
		return $elem;
	}
	
	/**
	 * Returns the XML for the resources.
	 *
	 * @return DOMDocument
	 * @param DOMDocument $dom
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function &resources(&$dom) {
		$res = $this->makeElement($dom, 'resources', array('base' => $this->service->url));
		$num_res = $this->service->numberOfOperations();
		if ($num_res == 0) {
			return null;
		}
		for ($i=0; $i < $num_res; $i++) { 
			$op = $this->service->getOperation($i);
			if (!$op->isVisible(SIPHON_REST)) continue;
			$curr_res = $dom->createElement('resource');
			$path_str = $op->exposedName();
			$op_doc = $op->getDesc();
			if ($op_doc != '') {
				$idoc =& $this->makeElement($dom, 'doc', array('xml:lang' => 'en', 'title' => "$path_str Operation"), $op_doc);
				$curr_res->appendChild($idoc);
			}
			$inputs = $op->getInputs();
			foreach($inputs as $varname=>$type) {
				$path_str .= '/{' . $varname . '}';
				$attrs = array('name' => $varname, 'style' => 'template', 'type' => $type);
				if ($op->requiredParam($varname)) {
					$attrs['required'] = 'true';
				}
				$param =& $this->makeElement($dom, 'param', $attrs);
				$curr_res->appendChild($param);
			}
			$curr_res->setAttribute('path', $path_str);
			$curr_res->appendChild($this->methods($dom, $op->exposedName()));
			$res->appendChild($curr_res);
		}
		return $res;
	}
	
	/**
	 * Return the WADL methods
	 *
	 * @return DOMDocument
	 * @param DOMDocument $dom
	 * @param string $opname
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function methods(&$dom, $opname) {
		$meth =& $this->makeElement($dom, 'method', array('name' => 'GET'));
		$meth->appendChild($this->request($dom));
		$meth->appendChild($this->response($dom, $opname));
		return $meth;
	}
	
	/**
	 * Returns the WADL request.
	 *
	 * @return DOMDocument
	 * @param DOMDocument $dom
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function request(&$dom) {
		$req =& $this->makeElement($dom, 'request', array());
		return $req;
	}
	
	/**
	 * Returns the response element.
	 *
	 * @return DOMDocument
	 * @param DOMDocument $dom
	 * @param string $opname
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	private function response(&$dom, $opname) {
		$resp =& $this->makeElement($dom, 'response', array());
		$rep =& $this->makeElement($dom, 'representation', array('mediaType' => 'text/xml', 'element' => "{$opname}Response"));
		$resp->appendChild($rep);
		return $resp;
	}
} // END class WADLGenerator
?>
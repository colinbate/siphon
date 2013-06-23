<?php
require_once('core/Siphon.php');

$request = SiphonRequest::initialize();
$service =& SiphonService::createFromRequest();
// Log this data for debugging.
$log = fopen('/tmp/siphon-input.txt', 'w');
fwrite($log, $request->data);
fclose($log);

// TODO: Allow a service to name a method: name_access to invoke 'name' with that access.
// Example: add_rest, add_soap, add_xmlrpc can all be methods and the different ones are called based on the request mechanism.
if ($request->hasExtraPath()) {
	// REST
	$rest = new REST();
	$service->bindToServer($rest, SIPHON_REST);
	$rest->service($request);
} else if ($request->wasPOST()) {
	// Script was called with POST HTTP method.
	
	if (Siphon::isXMLRPC($request->data)) {
		$xmlrpc = new XMLRPC();
		$service->bindToServer($xmlrpc, SIPHON_XMLRPC);
		$xmlrpc->service($request->data);
	} else {
		$soap =& Siphon::setupSoapServer($service);
		$soap->service($request->data);
	}
	
} else if ($request->wasGET()) {
	if ($request->matchQuery('wadl')) {
		// WADL requested
		$wadl = new WADLGenerator($service);
		$wadl->output();
	} else {
		
		$soap =& Siphon::setupSoapServer($service);

		// Deliver the WSDL or intro page. 
		$soap->service('');
	}
}


?>
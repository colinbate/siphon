<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<title>Siphon &rarr; <?=$service->name?></title>
		<script type="text/javascript" charset="utf-8">
			<!-- //
			function toggleDetails(id, icon) {
				e = document.getElementById(id);
				if (e.className=='detailhide') {
					e.className='details';
					icon.className = 'toggleon';
				} else {
					e.className='detailhide';
					icon.className='toggle';
				}
			}
			// -->
		</script>
		<link rel="stylesheet" href="<?=SIPHON_BASE_URL?>t/1/default.css" type="text/css" media="screen" title="default css" charset="utf-8">
	</head>
	<body>
		<div id="sideinfo">
			<div id="logo">
				<img src="<?=SIPHON_BASE_URL?>t/i/logo-ref.png" alt="Siphon" />
				<div id="siphon">SIPHON</div>
			</div>
			<div class="sidebox">
			<h3>General Specifications</h3>
			&rarr; <a href="https://wadl.dev.java.net/">WADL</a><br>
			&rarr; <a href="http://www.xmlrpc.com/spec">XML-RPC</a><br>
			&rarr; <a href="http://www.w3.org/TR/2003/REC-soap12-part0-20030624/">SOAP</a><br>
			&rarr; <a href="http://www.w3.org/TR/2001/NOTE-wsdl-20010315">WSDL</a>
			</div>
			<div class="sidebox">
				<p>
					Server Auto-discovery, Binding, XML-RPC, WADL and REST interface by <a href="http://rhuvium.com/projects/siphon/">Siphon</a> // SOAP and WSDL handling by <a href="http://sourceforge.net/projects/nusoap/">NuSOAP</a>.
				</p>
				<p>
					Comments or problems can be addressed to: <a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#99;&#46;&#102;&#46;&#98;&#97;&#116;&#101;&#64;&#114;&#101;&#97;&#100;&#105;&#110;&#103;&#46;&#97;&#99;&#46;&#117;&#107;">&#99;&#46;&#102;&#46;&#98;&#97;&#116;&#101;&#64;&#114;&#101;&#97;&#100;&#105;&#110;&#103;&#46;&#97;&#99;&#46;&#117;&#107;</a>
				</p>
			</div>
		</div>
		<h1 id="servicename"><?=$service->name?></h1>
		<div id="maincontent">
			<div class="serviceinfo">
				<p><?=$package?><br><?=$author?></p>
				<p>
					<?=htmlspecialchars($service->desc)?>
				</p>
			</div>
			<p class="instructions">
				The following is a technical description of the operations offered by this service. Most of this information
				comes from the WSDL/SOAP/XML-RPC interface and does not explain the REST interface. However,
				the WADL file is available. Just click on the operation to expand more information.
			</p>
			<div class="files">
				<a href="<?=$wadl?>">WADL</a> / <a href="<?=$wsdl?>">WSDL</a>
			</div>
			
			<?=$operations?>
			
			<div id="footer">
				Copyright &copy; 2006-2007 Colin Bate. Some rights reserved.
			</div>
		</div>
	</body>
</html>
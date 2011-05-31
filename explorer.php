<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
	<title>Wiener Linien API</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<style type="text/css" media="screen">
/* <![CDATA[ */
pre {
	border: 1px solid #999;
	background-color: #eee;
	padding: 1em;
	white-space: pre-wrap;
}
/* ]]> */
</style>
</head><body><?php
// adapted from http://us.php.net/manual/en/ref.xmlwriter.php#89047
class XmlConstruct extends XMLWriter {
	public function __construct($rootElement, $ns = false) {
		$this->openMemory();
		$this->setIndent(true);
		$this->setIndentString('    ');
		$this->startDocument('1.0', 'UTF-8');
		
		if ($ns) $this->startElementNS(null, $rootElement, $ns);
		else $this->startElement($rootElement);
	}

	public function fromArray($array, $parentTag = "item") {
		if (!is_array($array)) return;
		
		foreach ($array as $index => $element) {
			$tag = is_numeric($index) ? $parentTag : $index;
			if (is_array($element)) {
				$this->startElement($tag);
				$this->fromArray($element, $tag);
				$this->endElement();
			} elseif (substr($tag, 0, 1) == "_") {
				$this->writeAttribute(substr($tag, 1), $element);
			} else {
				$this->writeElement($tag, $element);
			}
		}
	}
	
	public function getDocument(){
		$this->endElement();
		$this->endDocument();
		return $this->outputMemory();
	}
}

function buildRequest($call, $params) {
	$request = array(
		"_clientId" => 123, "_apiName" => $call, "_apiVersion" => "2.0",
		"client" => array(
			"_clientId" => 123
		)
	);
	$request["requestType"] = $call;
	foreach ($params as $k => $v) {
		$request[$k] = $v;
	}
	$xml = new XmlConstruct("ft");
	$xml->fromArray(array("request" => $request));
	return $xml->getDocument();
}

function httpPost($url, $data) {
	$len = strlen($data);
	return file_get_contents($url, false, stream_context_create(array(
		"http" => array(
			"method" => "POST",
			"header" => "Connection: close\r\nContent-Length: $len\r\n",
			"content" => $data,
		)
	)));
}

function xmlHighlight($s) {
	$s = preg_replace("|<(/?)([^>]+)>|", "[1]<\\1[2]\\2[/2]>[/1]", $s);
	$s = preg_replace("|(\s)([^= ]+)\=\"([^\"]*)\"|", "\\1[3]\\2[/3][6]=[/6][4]\"\\3\"[/4]",$s);
	$s = htmlspecialchars($s);
	$replace = array(1 => '0000FF', 2 => '0000FF', 3 => '800000', 4 => 'FF00FF', 5 => 'FF0000', 6 => '0000FF');
	foreach ($replace as $k => $v) {
		$s = preg_replace("|\[" . $k . "\]|", "<font color=\"#" . $v . "\">", $s);
	}
	$s = preg_replace("|\[/[1-6]]|", "</font>", $s);
	return $s;
}


$apis = array(
	"api_search_location_stops_nearby" => array("outputCoords" => "WGS84", "fromCoordName" => "WGS84", "fromType" => "coords", "fromWgs84Lat" => "48.22", "fromWgs84Lon" => "16.39"),
	"api_search_location" => array("outputCoords" => "WGS84", "from" => "Praterstern"),
	"api_get_monitor" => array("monitor" => array("outputCoords" => "WGS84", "type" => "stop", "name" => "60201040", "year" => date("Y"), "month" => date("m"), "day" => date("d"), "hour" => date("H"), "minute" => date("i"), "line" => "", "sourceFrom" => "stoplist")),
	"api_get_route" => array("outputCoords" => "WGS84", "from" => "Aktueller Standort", "fromType" => "coords", "fromCoordName" => "WGS84", "fromWgs84Lat" => "48.22", "fromWgs84Lon" => "16.39", "to" => "60201040", "toType" => "stop", "year" => date("Y"), "month" => date("m"), "day" => date("d"), "hour" => date("H"), "minute" => date("i"), "deparr" => "dep", "modality" => "pt", "sourceFrom" => "gps", "sourceTo" => "stoplist"),
);
if (!isset($_GET["api"])) $_GET["api"] = reset(array_keys($apis));

if (isset($apis[$_GET["api"]]) && isset($_GET[$_GET["api"] . reset(array_keys($apis[$_GET["api"]]))])) {
	$params = array();
	foreach (array_keys($apis[$_GET["api"]]) as $key) {
		$params[$key] = $_GET[$_GET["api"] . $key];
	}
	$req = buildRequest($_GET["api"], $params);
	echo "POST to <tt>http://webservice.qando.at/2.0/webservice.ft</tt><pre>" . xmlHighlight($req) . "</pre>";
	$response = httpPost("http://webservice.qando.at/2.0/webservice.ft", $req);
	
	$dom = new DOMDocument();
	$dom->formatOutput = true;
	$dom->preserveWhiteSpace = false;
	$dom->loadXML($response);
	echo "Response: <pre>" . xmlHighlight($dom->saveXML()) . "</pre>";
	exit;
}

?><div style="float: right; width: 75%; height: 100%"><iframe name="results" style="width: 100%; height: 100%"></iframe></div><form target="results"><select name="api"><?php
foreach ($apis as $api => $params) {
	?><option value="<?php echo $api; ?>"<?php if ($api == $_GET["api"]) echo ' selected="selected"'; ?>><?php echo $api; ?></option><?php
}
?></select><?php
foreach ($apis as $api => $params) {
	?><div id="<?php echo $api; ?>"<?php if ($api != $_GET["api"]) echo ' style="display: none"'; ?>><?php
	foreach ($params as $k => $v) {
		if (is_array($v)) {
			foreach ($v as $k1 => $v1) {
				echo $k, "[$k1]"; ?>: <input name="<?php echo $api , $k, "[$k1]"; ?>" value="<?php echo isset($_GET[$k][$k1]) ? htmlspecialchars($_GET[$k][$k1]) : htmlspecialchars($v1); ?>" /><br/><?php
			}
		} else {
			echo $k; ?>: <input name="<?php echo $api, $k; ?>" value="<?php echo isset($_GET[$k]) ? htmlspecialchars($_GET[$k]) : htmlspecialchars($v); ?>" 	/><br/><?php
		}
	}
	?></div><?php
}

?><input type="submit" /></form>
<script>
document.getElementsByTagName("select")[0].onchange = function() {
	var divs = document.getElementsByTagName("div");
	for (var i = 1, l = divs.length; i < l; i++) divs[i].style.display = "none";
	document.getElementById(this.value).style.display = "block";
}
</script></body></html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
	<title>Wiener Linien API</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<style type="text/css" media="screen">
/* <![CDATA[ */
h3 {
	border-top: 1px solid #ccc;
	margin-top: 2em;
	padding-top: 2em;
}
pre {
	border: 1px solid #999;
	background-color: #eee;
	padding: 1em;
	white-space: pre-wrap;
}
/* ]]> */
</style>
</head><body><?php
include __DIR__ . "/xmlconstruct.php";

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


$apis = array(
	"api_search_location_stops_nearby" => array("outputCoords" => "WGS84", "fromCoordName" => "WGS84", "fromType" => "coords", "fromWgs84Lat" => "48.22", "fromWgs84Lon" => "16.39"),
	"api_get_monitor" => array("monitor" => array("outputCoords" => "WGS84", "type" => "stop", "name" => "60201040", "year" => "2011", "month" => "05", "day" => "30", "hour" => "16", "minute" => "04", "line" => "", "sourceFrom" => "stoplist")),
	"api_get_route" => array("outputCoords" => "WGS84", "from" => "Aktueller Standort", "fromType" => "coords", "fromCoordName" => "WGS84", "fromWgs84Lat" => "48.22", "fromWgs84Lon" => "16.39", "to" => "60201040", "toType" => "stop", "year" => "2011", "month" => "05", "day" => "30", "hour" => "16", "minute" => "04", "deparr" => "dep", "modality" => "pt", "sourceFrom" => "gps", "sourceTo" => "stoplist"),
);
if (!isset($_GET["api"])) $_GET["api"] = reset(array_keys($apis));

if (isset($apis[$_GET["api"]]) && isset($_GET[$_GET["api"] . reset(array_keys($apis[$_GET["api"]]))])) {
	$params = array();
	foreach (array_keys($apis[$_GET["api"]]) as $key) {
		$params[$key] = $_GET[$_GET["api"] . $key];
	}
	$req = buildRequest($_GET["api"], $params);
	echo "<pre>" . htmlspecialchars($req) . "</pre>";
	$response = httpPost("http://webservice.qando.at/2.0/webservice.ft", $req);
	
	$dom = new DOMDocument();
	$dom->formatOutput = true;
	$dom->preserveWhiteSpace = false;
	$dom->loadXML($response);
	echo "<pre>" . htmlspecialchars($dom->saveXML()) . "</pre>";
	exit;
}

?><form><select name="api"><?php
foreach ($apis as $api => $params) {
	?><option value="<?php echo $api; ?>"<?php if ($api == $_GET["api"]) echo ' selected="selected"'; ?>><?php echo $api; ?></option><?php
}
?></select><?php
foreach ($apis as $api => $params) {
	?><div id="<?php echo $api; ?>"<?php if ($api != $_GET["api"]) echo ' style="display: none"'; ?>><?php
	foreach ($params as $k => $v) {
		?><?php echo $k; ?>: <input name="<?php echo $api . $k; ?>" value="<?php echo isset($_GET[$v]) ? htmlspecialchars($_GET[$v]) : htmlspecialchars($v); ?>" /><br/><?php
	}
	?></div><?php
}

?><input type="submit" /></form>
</body></html>
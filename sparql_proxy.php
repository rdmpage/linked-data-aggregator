<?php

error_reporting(E_ALL);

$config = array();
$config['sparql_endpoint'] = 'http://65.108.253.35:9999/blazegraph/namespace/kg/sparql';


$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

$postdata = '';
if (isset($_POST['query']))
{
	$postdata = $_POST['query'];
}

//$postdata = file_get_contents('php://input');

/*
$postdata = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
SELECT * WHERE {
  ?sub <http://schema.org/name> ?obj .
} 
LIMIT 5';
*/


$ch = curl_init(); 
curl_setopt ($ch, CURLOPT_URL, $config['sparql_endpoint']); 
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);

if ($postdata != '')
{
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
}
curl_setopt($ch, CURLOPT_HTTPHEADER, 
	array(
		'Content-Type: application/sparql-query',
		'Accept: application/sparql-results+json'
		),	
	 );

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno ($ch) != 0 )
{
	echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
}

if ($callback != '')
{
	echo $callback . '(';
}
echo $response;
if ($callback != '')
{
	echo ')';
}

?>

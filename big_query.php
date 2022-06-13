<?php

// SPARQL queries with lots of results

require_once(__DIR__ . '/utils.php');

$config = array();
$config['sparql_endpoint'] = 'http://65.108.253.35:9999/blazegraph/namespace/kg/sparql';

//----------------------------------------------------------------------------------------
function big_query($query)
{
	global $config;

	$heading = array();
	$first = true;

	$page = 1000;
	$offset = 0;

	$done = false;

	while (!$done)
	{
		$sparql = $query;
		
		$sparql .= "\nLIMIT $page";
		$sparql .= "\nOFFSET $offset";
		
		// do query
		$json = get(
			$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
			'application/sparql-results+json'
		);
		
		
		// echo $json;

		$obj = json_decode($json);
	
		// print_r($obj);

		foreach ($obj->results as $results)
		{
			foreach ($results as $binding)
			{
				//print_r($binding);
			
				// dump results 			
				$row = array();
			
				foreach ($binding as $k => $v)
				{
					if (!isset($heading[$k]))
					{
						$heading[] = $k;
					}
				
					$row[] = $v->value;					
				}
			
				if ($first)
				{
					echo join("\t", $heading) . "\n";
					$first = false;
				}
				echo join("\t", $row) . "\n";
			}
		}

		if (count($obj->results->bindings) < $page)
		{
			$done = true;
		}
		else
		{
			$offset += $page;
		}
	}
}
	
//----------------------------------------------------------------------------------------

if (1)
{
	// people
	$sparql = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX schema: <http://schema.org/>
	SELECT ?person ?person_name WHERE {
	  ?person a schema:Person .
	   {
		   ?person schema:name ?person_name .
	   }
	   UNION
	   {
		 ?person schema:givenName ?givenName .         
		 ?person schema:familyName ?familyName .

		 BIND(CONCAT(?familyName, ", ", ?givenName) AS ?person_name)           
	   }
	}';
}

if (0)
{
	// works
	$sparql = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX schema: <http://schema.org/>
	SELECT ?name WHERE {
	  ?work a schema:CreativeWork .
	  ?work schema:name ?name .
	}';
}

big_query($sparql);


?>

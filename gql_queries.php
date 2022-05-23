<?php

error_reporting(E_ALL);

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/utils.php');

use ML\JsonLD\JsonLD;

$config = array();
$config['sparql_endpoint'] = 'http://65.108.253.35:9999/blazegraph/namespace/kg/sparql';
$config['hack_uri']	= "http://example.com/";


//----------------------------------------------------------------------------------------
// base JSON-LD context

// JSON-LD context
$context = new stdclass;
$context->{'@vocab'} = 'http://schema.org/';
$context->gql = $config['hack_uri'];

// id
$context->id = '@id';

// type
$context->type = '@type';	

// URL as string
$url = new stdclass;
$url->{'@id'} = "url";
$url->{'@type'} = "@id";
$context->url = $url;

// identifier is always an array
$identifier = new stdclass;
$identifier->{'@id'} = "identifier";
$identifier->{'@type'} = "@id";
$identifier->{'@container'} = "@set";
$context->identifier = $identifier;


// sameAs as set of strings
$sameAs = new stdclass;
$sameAs->{'@id'} = "sameAs";
$sameAs->{'@type'} = "@id";
$sameAs->{'@container'} = "@set";
$context->sameAs = $sameAs;

// alternateName is an array
$alternateName = new stdclass;
$alternateName->{'@id'} = "alternateName";
$alternateName->{'@container'} = "@set";
$context->alternateName = $alternateName;

// alternateScientificName is an array
$alternateScientificName = new stdclass;
$alternateScientificName->{'@id'} = "alternateScientificName";
$alternateScientificName->{'@container'} = "@set";
$context->alternateScientificName = $alternateScientificName;

// affiliation is an array
$affiliation = new stdclass;
$affiliation->{'@id'} = "affiliation";
$affiliation->{'@container'} = "@set";
$context->affiliation = $affiliation;

// isBasedOn is an array
$isBasedOn = new stdclass;
$isBasedOn->{'@id'} = "isBasedOn";
$isBasedOn->{'@container'} = "@set";
$context->isBasedOn = $isBasedOn;

// mainEntityOfPage is an array of strings
$mainEntityOfPage = new stdclass;
$mainEntityOfPage->{'@id'} = "mainEntityOfPage";
$mainEntityOfPage->{'@type'} = "@id";
$mainEntityOfPage->{'@container'} = "@set";
$context->mainEntityOfPage = $mainEntityOfPage;


// GraphQL specific fields that have no obvious schema.org equivalent

$context->ringgold	= "gql:ringgold";
$context->ror		= "gql:ror";
$context->titles		= "gql:titles";

// so we can have dois as keys
$context->bibo = 'http://purl.org/ontology/bibo/';
$context->doi  = "bibo:doi";	


$config['context'] = $context;

//----------------------------------------------------------------------------------------
// type-specific contexts

$creativework_context = clone $context;

// contentUrl as string
$contentUrl = new stdclass;
$contentUrl->{'@id'} = "contentUrl";
$contentUrl->{'@type'} = "@id";
$creativework_context->contentUrl = $contentUrl;

// thumbnailUrl as string
$thumbnailUrl = new stdclass;
$thumbnailUrl->{'@id'} = "thumbnailUrl";
$thumbnailUrl->{'@type'} = "@id";
$creativework_context->thumbnailUrl = $thumbnailUrl;

// figures always an array
$figures = new stdclass;
$figures->{'@id'} = "gql:figures";
$figures->{'@container'} = "@set";
$creativework_context->{'figures'} = $figures;

// author is always an array
$author = new stdclass;
$author->{'@id'} = "author";
$author->{'@container'} = "@set";
$creativework_context->author = $author;

// ISSN is always an array
$issn = new stdclass;
$issn->{'@id'} = "issn";
$issn->{'@container'} = "@set";
$creativework_context->{'issn'} = $issn;

// ISBN is always an array
$isbn = new stdclass;
$isbn->{'@id'} = "isbn";
$isbn->{'@container'} = "@set";
$creativework_context->{'isbn'} = $isbn;



// hack
//$creativework_context->container = "gql:container";


$config['creativework_context'] = $creativework_context;


//----------------------------------------------------------------------------------------
// SPARQL and Wikidata will often return strings that have language flags so process
// these here. For now we strip language flags and return an array of unique strings.
function literals_to_array($value)
{
	$strings = array();
	
	if (is_object($value))
	{
		$strings[] = $value->{"@value"};
	}
	else
	{
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				if (isset($v->{"@value"}))
				{
					$strings[] = $v->{"@value"};
				}
				else
				{
					$strings[] = $v;
				}
			}
		
			$strings = array_unique($strings);
		}
		else
		{
			$strings[] = $value;
		}
	}
	
	return $strings;
}

//----------------------------------------------------------------------------------------
// Handle titles in same way as DataCite
function titles_to_array($value)
{
	$strings = array();
	
	if (is_object($value))
	{
		$title = new stdclass;
		if (isset($value->{"@language"}))
		{
			$title->lang = $value->{"@language"};
		}
		$title->title = $value->{"@value"};
		
		$title->title = strip_tags($title->title);
		
		$strings[] = $title;
	}
	else
	{
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				$title = null;
				if (is_object($v))
				{
					$title = new stdclass;
					$title->lang = $v->{"@language"};
					$title->title = $v->{"@value"};
				}
				else
				{
					$title = new stdclass;
					$title->title = $v;				
				}
				
				$title->title = strip_tags($title->title);
				
				$strings[] = $title;
			}
		}
		else
		{
			$title = new stdclass;
			$title->title = $value;
			
			$title->title = strip_tags($title->title);
			
			$strings[] = $title;
		}
	}
	
	return $strings;
}

//----------------------------------------------------------------------------------------
// We may sometimes get multiple values when we expect only one (e.g., for DOIs or ORCIDs)
// so arbitrarily pick one value to avoid type clashes (e.g. when schema expects a string
// but instead gets an array
function pick_one($value)
{
	$result = $value;
	
	if (is_array($value))
	{
		$result = $value[0];
	}
	
	return $result;
}


//----------------------------------------------------------------------------------------
// Dates may be simple strngs or date types, always convert to strig
function date_to_string($value)
{
	$date_string = '';
	
	if (is_object($value))
	{
		$date_string = $value->{"@value"};
	}
	else
	{
		$date_string = $value;
	}
	
	return $date_string;
}

//----------------------------------------------------------------------------------------
// Query for a single thing
// Note that we may need type-specific context (e.g., to ensure something is a string 
// rather than and array
function one_object_query($args, $sparql, $context = null)
{
	global $config;
	
	if ($context == null)
	{
		$context = $config['context'];
	}	
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
	
	$doc = JsonLD::compact($json, json_encode($context));
	
	// print_r($doc);
	
	if (isset($doc->{'@graph'}))
	{
		// We need to frame it on the thing that is the subject of the
		// query, i.e. $args['id']
		$n = count($doc->{'@graph'});
		$type = '';
		$i = 0;
		while ($i < $n && $type == '')
		{
			if ($doc->{'@graph'}[$i]->id == $args['id'])
			{
				if (is_array($doc->{'@graph'}[$i]->type))
				{
					$type = $doc->{'@graph'}[$i]->type[0];
				}
				else
				{
					$type = $doc->{'@graph'}[$i]->type;
				}
				
			}
			$i++;
		}
		
		$frame = (object)array(
				'@context' => $context,
				'@type' => $type
			);

		$framed = JsonLD::frame($json, json_encode($frame));
		
		$doc = $framed->{'@graph'}[0];
		
	
	}
	
	// post process 
	
	
	if (isset($doc->name))
	{
		$doc->name = literals_to_array($doc->name);
	}	
	
	if (isset($doc->titles))
	{
		$doc->titles = titles_to_array($doc->titles);
	}
		
	if (isset($doc->description))
	{
		$doc->description = literals_to_array($doc->description);
	}
		
	// cleanup
	if (isset($doc->{"@context"}))
	{
		unset($doc->{"@context"});
	}
	
	return $doc;
}	

//----------------------------------------------------------------------------------------
// Query for a list
function list_object_query($args, $sparql, $context = null)
{
	global $config;
	
	global $config;
	
	if ($context == null)
	{
		$context = $config['context'];
	}		
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
		
	$doc = JsonLD::compact($json, json_encode($config['context']));
	
	//print_r($doc);
	
	// post process to create a simple list
	
	$result = array();
	
	if (isset($doc->{"@graph"}))
	{
		foreach ($doc->{"@graph"} as $d)
		{
			if (isset($d->name))
			{
				$d->name = literals_to_array($d->name);
			}	
			
			if (isset($d->titles))
			{
				$d->titles = titles_to_array($d->titles);
			}	
			
			// unique?
			if (isset($d->doi) && is_array($d->doi))
			{
				$d->doi = $d->doi[0];
			}	
			if (isset($d->datePublished) && is_array($d->datePublished))
			{
				$d->datePublished = $d->datePublished[0];
			}	
			
			$result[] = $d;			
		}
	}
	else
	{
		if (isset($doc->id))
		{
			if (isset($doc->name))
			{
				$doc->name = literals_to_array($doc->name);
			}	

			if (isset($doc->titles))
			{
				$doc->titles = titles_to_array($doc->titles);
			}	
		
			$result[] = $doc;
		}
		else
		{
			$result = null;
		}
	}
	
	return $result;
}	



//----------------------------------------------------------------------------------------
// Query for a single thing
function thing_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?thing rdf:type ?type . 

	 ?thing schema:name ?name .
	 ?thing schema:description ?description .
	}
	WHERE
	{
	  VALUES ?thing { <' . $args['id'] . '> }
  
	  ?thing rdf:type ?type .
	  
	  # might not have a name, and might not use schema.org
	  OPTIONAL 
	  {
	  	{ ?thing schema:name ?name . } 
	  	UNION 
	  	{ ?thing dc:title ?name . }
	  }	  
	  
	  OPTIONAL
	  {
	  	?thing schema:description ?description .
	  }

	}   
	';
	
	//echo $sparql;
	
	$doc = one_object_query($args, $sparql);
	
	if (isset($doc->type))
	{
		
		$schema_types = array();
	
		$types = array();
		if (is_array($doc->type))
		{
			$types = $doc->type;
		}
		else
		{
			$types[] = $doc->type;
		}
	
		// we may have to map types from RDF-specific vocabs to our classes
		// we want a complte mapping as this query may be used to decide what
		// type of thing user wants to display
		foreach ($types as $type)
		{
			switch ($type)
			{
				case 'CreativeWork':
					$schema_types[] = 'CreativeWork';
					break;

				case 'ImageObject':
					$schema_types[] = 'ImageObject';
					break;
				
				case 'Organization':
					$schema_types[] = 'Organization';
					break;								
				
				case 'Person':
					$schema_types[] = 'Person';
					break;				

				case 'ScholarlyArticle':
					$schema_types[] = 'ScholarlyArticle';
					break;		
		
				case 'TaxonName':
					$schema_types[] = 'TaxonName';
					break;
	
				case 'Taxon':
					$schema_types[] = 'Taxon';
					break;
		
				default:
					$schema_types[] = 'Thing';
					break;	
			}
	
		}	
	
		$schema_types = array_unique($schema_types);
	
		$doc->type = $schema_types;
	}

	return $doc;
}	

//----------------------------------------------------------------------------------------
// Query for a scientific name
function taxon_name_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?item rdf:type ?type . 

	 ?item schema:name ?name .
	 
	  ?item schema:isBasedOn ?work .
	  ?work gql:titles ?title .
	  ?work bibo:doi ?doi .
	 
	 
	}
	WHERE
	{
	  VALUES ?item { <' . $args['id'] . '> }
  
	  ?item rdf:type ?type .
	  
	  ?item schema:name ?name .
	  	  
	  OPTIONAL
	  {
	  	?item schema:isBasedOn ?work .
	  	?work schema:name ?title
	  	
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}   	
	  	
	  }
	  

	}   
	';

	//echo $sparql;
	
	$doc = one_object_query($args, $sparql);
	
		
	$schema_types = array();
	
	$types = array();
	if (is_array($doc->type))
	{
		$types = $doc->type;
	}
	else
	{
		$types[] = $doc->type;
	}
	
	// we may have to map types from RDF-specific vocabs to our classes
	foreach ($types as $type)
	{
		switch ($type)
		{
	
			case 'TaxonName':
				$schema_types[] = 'TaxonName';
				break;
		
			default:
				$schema_types[] = 'Thing';
				break;	
		}
	
	}	
	
	$schema_types = array_unique($schema_types);
	
	$doc->type = $schema_types;
	

	return $doc;
}	

//----------------------------------------------------------------------------------------
// Query for a taxon
function taxon_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?item rdf:type ?type . 

	 ?item schema:name ?name .
	 ?item schema:taxonRank ?taxonRank .
	 
  	# scientific name
    ?item schema:scientificName ?scientificName .
	?scientificName schema:name ?scientificNameString .
    ?scientificName schema:author ?author .    
    #?scientificName schema:taxonRank ?nameRank .
    
    ?scientificName schema:isBasedOn ?work .
    ?work  gql:titles ?workName . 	
    ?work schema:datePublished ?datePublished . 
    ?work schema:url ?workUrl . 
    ?work bibo:doi ?doi . 
    
    # synonyms
    #?item schema:alternateName ?alternateName .
    ?item schema:alternateScientificName ?alternateScientificName .
    
  	?alternateScientificName schema:name ?alternateName .
  	?alternateScientificName schema:author ?alternateAuthor .
  	?alternateScientificName schema:isBasedOn ?alternateIsBasedOn .
    
    
    # classification
    ?item schema:parentTaxon ?parent . 
    ?parent schema:name ?parentName .
    
    ?item schema:childTaxon ?child . 
    ?child schema:name ?childName .
   	 
	}
	WHERE
	{
	  VALUES ?item { <' . $args['id'] . '> }
  
	  ?item rdf:type ?type .
	  
	  ?item schema:name ?name .
	  ?item schema:taxonRank ?taxonRank .
	  FILTER(isLiteral(?taxonRank)) 
	  
	  # scientific name
		?item schema:scientificName ?scientificName .
		?scientificName rdf:type ?scientificNameType . 
		?scientificName schema:name ?scientificNameString .
		
		OPTIONAL
		{
			?scientificName schema:author ?author .    
		}
		OPTIONAL
		{
  			?scientificName schema:taxonRank ?nameRank .
  		}
  		
  		OPTIONAL
  		{
  			?scientificName schema:isBasedOn ?work .
  			?work schema:name ?workName . 	
			OPTIONAL
			{
				?work schema:datePublished ?datePublished .
			}
			OPTIONAL
			{
				?work schema:url ?workUrl . 
			}
			OPTIONAL
			{
			  ?work schema:identifier ?identifier .
			  ?identifier schema:propertyID "doi" .
			  ?identifier schema:value ?doi .
			}      
  		}	  
  		
  		#OPTIONAL
  		#{
  		#	?item schema:alternateName ?alternateName .
  		#}

  		OPTIONAL
  		{
  			?item schema:alternateScientificName ?alternateScientificName .
  			?alternateScientificName schema:name ?alternateName .
  			OPTIONAL
  			{
  				?alternateScientificName schema:author ?alternateAuthor .
  			}
  		}

		
		OPTIONAL
		{
    		?item schema:parentTaxon ?parent . 
    		?parent schema:name ?parentName .
		}
		
		OPTIONAL
		{
    		?child schema:parentTaxon ?item . 
    		?child schema:name ?childName .
		}

		
	}   
	';
	
	//echo $sparql;
	
	$doc = one_object_query($args, $sparql);
	
	// only one string for name
	$doc->name = pick_one($doc->name);
	
	// handle titles of works as array so we can handle languages
	if (isset($doc->scientificName))
	{
		if (isset($doc->scientificName->isBasedOn))
		{
			$doc->scientificName->isBasedOn->titles = titles_to_array($doc->scientificName->isBasedOn->titles);
		}
		
	}
	
		
	$schema_types = array();
	
	$types = array();
	if (is_array($doc->type))
	{
		$types = $doc->type;
	}
	else
	{
		$types[] = $doc->type;
	}
	
	// we may have to map types from RDF-specific vocabs to our classes
	foreach ($types as $type)
	{
		switch ($type)
		{
	
			case 'Taxon':
				$schema_types[] = 'Taxon';
				break;
		
			default:
				$schema_types[] = 'Thing';
				break;	
		}
	
	}	
	
	$schema_types = array_unique($schema_types);
	
	$doc->type = $schema_types;
	

	return $doc;
}	

//----------------------------------------------------------------------------------------
// List of works about a thing 
function works_about_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?work a ?type . 

	 ?work gql:titles ?title .
	 ?work schema:datePublished ?datePublished .

	 ?work bibo:doi ?doi .
	 ?work schema:url ?url .
	 
	 ?work schema:sameAs ?sameAs .
	 
	}
	WHERE
	{
	  VALUES ?subject { <' . $args['id'] . '> }
	  
	  {
	  	?work schema:about ?subject .
	  }
	  UNION
	  {
	  	?subject schema:scientificName ?scientificName .
	  	?scientificName schema:isBasedOn ?work .
	  }
	  UNION
	  {
	  	?subject schema:alternateScientificName ?alternateScientificName .
	  	?alternateScientificName schema:isBasedOn ?work .
	  }
	  
	  
	  ?work rdf:type ?type .
	  
		?work schema:name ?title . 	
		OPTIONAL
		{
			?work schema:datePublished ?datePublished .
		}
		OPTIONAL
		{
			?work schema:url ?url . 
		}
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}   
		
		OPTIONAL
		{
			# get things that work is sameAs, and things sameAs work
			?work schema:sameAs|^schema:sameAs ?sameAs
		}   
		

		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of things a work is about 
function what_work_is_about_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?subject a ?type . 
	 ?subject schema:name ?name .
	 
	 ?work schema:about ?thing .	 
	}
	WHERE
	{
	  VALUES ?work { <' . $args['id'] . '> }
	  
	  # Note use of sameAs to get link versions together
	  {
	  	?work schema:about ?subject .
	  }
	  UNION
	  {
	    # sameAs realtionship
	  	?work schema:sameAs|^schema:sameAs ?sameAs .
	  	?sameAs schema:about ?subject .
	  }

	  UNION
	  {
	  	?subject schema:scientificName ?scientificName .
	  	?scientificName schema:isBasedOn ?work .
	  }
	  UNION
	  {
	  	?subject schema:scientificName ?scientificName .
	  	?scientificName schema:isBasedOn ?sameAs .
	  	?work schema:sameAs|^schema:sameAs ?sameAs .
	  }
 
	  UNION
	  {
	  	?subject schema:alternateScientificName ?alternateScientificName .
	  	?alternateScientificName schema:isBasedOn ?work .
	  }
	  UNION
	  {
	  	?subject schema:alternateScientificName ?alternateScientificName .
	  	?alternateScientificName schema:isBasedOn ?sameAs .
	  	?work schema:sameAs|^schema:sameAs ?sameAs .
	  }
	  
	  ?subject rdf:type ?type .
	  ?subject schema:name ?name . 	
		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// Citations by a work
// Need to think about how we handle sameAs clusters
function work_cites($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?work a ?type . 

	 ?work gql:titles ?title .
	 ?work schema:datePublished ?datePublished .

	 ?work bibo:doi ?doi .
	 ?work schema:url ?url .
	 
	 ?work schema:sameAs ?sameAs .	 
	}
	WHERE
	{
	  VALUES ?this { <' . $args['id'] . '> }
	  
	  ?this schema:citation ?work .
	  ?work rdf:type ?type .
	  
		?work schema:name ?title . 	
		OPTIONAL
		{
			?work schema:datePublished ?datePublished .
		}
		OPTIONAL
		{
			?work schema:url ?url . 
		}
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}   
		

		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// Citations by a work
// Need to think about how we handle sameAs clusters
function work_cited_by($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?work a ?type . 

	 ?work gql:titles ?title .
	 ?work schema:datePublished ?datePublished .

	 ?work bibo:doi ?doi .
	 ?work schema:url ?url .
	 
	 ?work schema:sameAs ?sameAs .	 
	}
	WHERE
	{
	  VALUES ?this { <' . $args['id'] . '> }
	  
	  ?work schema:citation ?this .
	  ?work rdf:type ?type .
	  
		?work schema:name ?title . 	
		OPTIONAL
		{
			?work schema:datePublished ?datePublished .
		}
		OPTIONAL
		{
			?work schema:url ?url . 
		}
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}   
		

		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	



//----------------------------------------------------------------------------------------
// List of works for a creator
function person_works_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?work a ?type . 

	 ?work gql:titles ?title .
	 ?work schema:datePublished ?datePublished .

	 ?work bibo:doi ?doi .
	 ?work schema:url ?url .
	 
	 ?work schema:sameAs ?sameAs .
	 
	}
	WHERE
	{
	  VALUES ?person { <' . $args['id'] . '> }
	  
	  ?work schema:creator ?person .
	  ?work rdf:type ?type .
	  
	  OPTIONAL
		{
		?work schema:name ?title . 
		}
			
		OPTIONAL
		{
			?work schema:datePublished ?datePublished .
		}
		OPTIONAL
		{
			?work schema:url ?url . 
		}
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}   
		
		OPTIONAL
		{
			# get things that work is sameAs, and things sameAs work
			?work schema:sameAs|^schema:sameAs ?sameAs
		}   
		

		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of works on which a name is based (typically 1)
function taxon_name_works_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?work a ?type . 

	 ?work gql:titles ?title .
	 ?work schema:datePublished ?datePublished .

	 ?work bibo:doi ?doi .
	 ?work schema:url ?url .
	 
	 ?work schema:sameAs ?sameAs .
	 
	}
	WHERE
	{
	  VALUES ?taxonName { <' . $args['id'] . '> }
	  
	  ?taxonName schema:isBasedOn ?work .
	  ?work rdf:type ?type .
	  
	  OPTIONAL
		{
		?work schema:name ?title . 
		}
			
		OPTIONAL
		{
			?work schema:datePublished ?datePublished .
		}
		OPTIONAL
		{
			?work schema:url ?url . 
		}
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}   
		
		OPTIONAL
		{
			# get things that work is sameAs, and things sameAs work
			?work schema:sameAs|^schema:sameAs ?sameAs
		}   
		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	


//----------------------------------------------------------------------------------------
// List of person affiliations
function person_affiliation_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?affiliation a ?type . 

	 ?affiliation schema:name ?name .
	}
	WHERE
	{
	  VALUES ?person { <' . $args['id'] . '> }
	  
	  ?person schema:affiliation ?affiliation .
	  ?affiliation rdf:type ?type .
	  
	   {
		 VALUES ?type { schema:Organization } # In case we haven\'t defined a type for this
	   }
	   UNION
	   {
		?affiliation a ?type .
	   }
  
	 ?affiliation schema:name ?name .
		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of scientific names for a creator
function person_scientific_names_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?scientificName schema:name ?name	.
	 ?scientificName a ?type . 
	}
	WHERE
	{
	  VALUES ?person { <' . $args['id'] . '> }
	  
  	?work schema:creator ?person .
  
  	?scientificName schema:isBasedOn ?work .
  	?scientificName schema:name ?name .
  	?scientificName a ?type .
  	
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of scientific names published by a work
function work_scientific_names_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?scientificName schema:name ?name	.
	 ?scientificName a ?type . 
	}
	WHERE
	{
	  VALUES ?work { <' . $args['id'] . '> }
	    
  	?scientificName schema:isBasedOn ?work .
  	?scientificName schema:name ?name .
  	?scientificName a ?type .
  	
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// An image
function image_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?image a ?type . 
	 ?image schema:name ?name .
	 ?image schema:caption ?description .
	 ?image schema:thumbnailUrl ?thumbnailUrl .
	 ?image schema:contentUrl ?contentUrl .
	}
	WHERE
	{
	  VALUES ?image { <' . $args['id'] . '> }
	  	  
	  ?image rdf:type ?type .
	  ?image schema:name ?name .
	  
		OPTIONAL
		{
			?image schema:description ?description .
		}
	  
		OPTIONAL
		{
			?image schema:thumbnailUrl ?thumbnailUrl .
		}
		
		OPTIONAL
		{
			?image schema:contentUrl ?contentUrl .
		}
		
	} 
	';
	
	
	$doc = one_object_query($args, $sparql, $config['creativework_context']);
	
	if (isset($doc->name))
	{
		$doc->name = pick_one($doc->name);
	}
	if (isset($doc->description))
	{
		$doc->description = pick_one($doc->description);
	}


	return $doc;	

}	

//----------------------------------------------------------------------------------------
// A work
function work_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?work a ?type . 

	 ?work gql:titles ?title .
	 ?work schema:datePublished ?datePublished .

	 ?work bibo:doi ?doi .
	 ?work schema:sameAs ?sameAs .
	 ?work schema:url ?url .
	 
	 
	 ?work schema:author ?creator .
     ?creator schema:name ?creator_name .
     ?creator schema:givenName ?givenName .
     ?creator schema:familyName ?familyName .
     ?creator gql:orcid ?orcid .
	 
	?work schema:volumeNumber ?volumeNumber .
	?work schema:issueNumber ?issueNumber .
	?work schema:pagination ?pagination .
	 	 
	# figures (e.g., Plazi)
	?work gql:figures ?image . 
	?image a schema:ImageObject .
	?image schema:thumbnailUrl ?thumbnailUrl .
	?image schema:contentUrl ?contentUrl .
	?image schema:caption ?description .
	
	?work schema:mainEntityOfPage ?mainEntityOfPage .	
	
	
	}
	WHERE
	{
	  VALUES ?work { <' . $args['id'] . '> }

	  ?work rdf:type ?type .
	  
		?work schema:name ?title . 	
		
		OPTIONAL
		{
			?work schema:datePublished ?datePublished .
		}
		
		OPTIONAL
		{
			?work schema:url ?url . 
		}
		
		OPTIONAL
		{
		  ?work schema:identifier ?identifier .
          ?identifier schema:propertyID "doi" .
          ?identifier schema:value ?doi .
		}    
		
		# Will need to handle creator and author, and handle order
		
		
		OPTIONAL
		{
			 ?work schema:creator ?creator .
			 
			 # not every creator has a name
			 {
				 ?creator schema:name ?creator_name .
			 }
			 UNION
			 {
			   ?creator schema:givenName ?givenName .         
			   ?creator schema:familyName ?familyName .
		   
			   BIND(CONCAT(?givenName, " ", ?familyName) AS ?creator_name)           
			 }
			 
        }
        
	OPTIONAL { ?work schema:volumeNumber ?volumeNumber .}
	OPTIONAL { ?work schema:issueNumber ?issueNumber .}
	OPTIONAL { ?work schema:pagination ?pagination .}
        
		
		
	OPTIONAL 
	{
		?image schema:isPartOf ?work . 
		?image rdf:type schema:ImageObject .
		?image schema:thumbnailUrl ?thumbnailUrl .
		?image schema:contentUrl ?contentUrl .
		?image schema:description ?description .
	}		   
		  
	OPTIONAL
	{
		# get things that work is sameAs, and things sameAs work
		?work schema:sameAs|^schema:sameAs ?sameAs
	}   


  		OPTIONAL
  		{
  			?work schema:mainEntityOfPage ?mainEntityOfPage .
  		}

		
	} 
	';
	
	//echo $sparql;
	
	$doc = one_object_query($args, $sparql, $config['creativework_context']);
	
	if (isset($doc->datePublished))
	{
		$doc->datePublished = date_to_string($doc->datePublished);
	}

	if (isset($doc->author))
	{
		foreach ($doc->author as &$author)
		{
			// force name to be a single string
			if (isset($author->name))
			{
				$author->name = pick_one($author->name);
			}
		
			// extract an ORCID if we have one
			if (preg_match('/orcid.org\/(?<id>\d{4}-\d{4}-\d{4}-\d{3}(\d|X)$)/', $author->id, $m))
			{
				$author->orcid = $m['id'];
			}			
		}
	}	
		

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// An person
function person_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
			PREFIX tp: <http://rs.tdwg.org/ontology/voc/Person#>
			PREFIX dc: <http://purl.org/dc/elements/1.1/>	
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?thing a ?type . 
	 ?thing schema:name ?name .
	 ?thing schema:givenName ?givenName .
	 ?thing schema:familyName ?familyName .
	 
	 ?thing schema:alternateName ?alternateName .
	 
	 ?thing gql:orcid ?orcid .
	 
	 ?thing schema:mainEntityOfPage ?mainEntityOfPage .
	}
	WHERE
	{
	  VALUES ?thing { <' . $args['id'] . '> }
	  
	   {
		 VALUES ?type { schema:Person } # In case we haven\'t defined a type for this
	   }
	   UNION
	   {
		?thing a ?type .
	  }
	  
  			OPTIONAL {{ ?thing schema:name ?name . } UNION { ?thing dc:title ?name . }}
  			OPTIONAL {{ ?thing schema:givenName ?givenName . } UNION { ?thing tp:forenames ?givenName . }}
  			OPTIONAL {{ ?thing schema:familyName ?familyName . } UNION { ?thing tp:surname ?familyName . }}
  			
  		OPTIONAL
  		{
  			?thing schema:alternateName ?alternateName .
  		}

  		OPTIONAL
  		{
  			?thing schema:mainEntityOfPage ?mainEntityOfPage .
  		}
  		
  		
		
	} 
	';
	
	//echo $sparql;
	
	$doc = one_object_query($args, $sparql);
	
	if (isset($doc->name))
	{
		$doc->name = pick_one($doc->name);
	}
	else
	{
		// make sure we have a name
		$strings = array();
		if (isset($doc->givenName))
		{
			$strings[] = $doc->givenName;
		}
		if (isset($doc->familyName))
		{
			$strings[] = $doc->familyName;
		}
		$doc->name = join(' ', $strings);
	}
	
	if (preg_match('/orcid.org\/(?<id>\d{4}-\d{4}-\d{4}-\d{3}(\d|X)$)/', $args['id'], $m))
	{
		$doc->orcid = $m['id'];
	}


	return $doc;	

}	


//----------------------------------------------------------------------------------------
// An organisation
function organisation_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?thing a ?type . 
	 ?thing schema:name ?name .	 
	 ?thing schema:alternateName ?alternateName .
 
	 ?thing gql:ringgold ?ringgold .
	 ?thing gql:ror ?ror .
	 }
	WHERE
	{
	  VALUES ?thing { <' . $args['id'] . '> }
  
	   {
		 VALUES ?type { schema:Organization } # In case we haven\'t defined a type for this
	   }
	   UNION
	   {
		?thing a ?type .
	   }
  
	 ?thing schema:name ?name .
 
	 OPTIONAL 
	 {
	  ?thing schema:alternateName ?alternateName 
	 }

	 OPTIONAL 
	 {
	  ?thing schema:identifier ?ringgold_identifier .
	  ?ringgold_identifier schema:propertyID "RINGGOLD" .
	  ?ringgold_identifier schema:value ?ringgold .
	 }
 
	 OPTIONAL 
	 {
	  ?thing schema:identifier ?ror_identifier .
	  ?ror_identifier schema:propertyID "ROR" .
	  ?ror_identifier schema:value ?ror .
	 }
 
 
 
	
	} 
	';

	
	//echo $sparql;
	
	$doc = one_object_query($args, $sparql);
	
	if (isset($doc->name))
	{
		$doc->name = pick_one($doc->name);
	}


	return $doc;	

}	



if (0)
{
	if (0)
	{
		// generic thing
		$args = array(
			'id' => 'https://www.catalogueoflife.org/data/taxon/7NN8R' 
		);	
		$result = thing_query($args);
	}
	
	if (1)
	{
		// taxon
		$args = array(
			'id' => 'https://www.catalogueoflife.org/data/taxon/46Q44' 
		);	
		$result = taxon_query($args);
	}

	if (0)
	{
		// works about something
		$args = array(
			'id' => 'https://www.catalogueoflife.org/data/taxon/7PVTH' 
		);	
		$result = works_about_query($args);
	}
	
	if (0)
	{
		// image
		$args = array(
			'id' => 'https://doi.org/10.5281/zenodo.5821112' 
		);	
		$result = image_query($args);
	}

	if (0)
	{
		// work
		$args = array(
//			'id' => 'https://doi.org/10.5852/ejt.2020.629' 
			//'id' => 'https://doi.org/10.3897/zookeys.921.49199',
			'id' => 'https://doi.org/10.1080/03036758.2017.1287101',
		);	
		$result = work_query($args);
	}
	
	if (0)
	{
		// things works is about 
		$args = array(
			'id' => 'https://doi.org/10.1080/03036758.2017.1287101' 
		);	
		$result = what_work_is_about_query($args);
	}	
	
	if (0)
	{
		// person
		$args = array(
			'id' => 'https://orcid.org/0000-0002-0630-545X' 
		);	
		$result = person_query($args);
	}
	
	if (0)
	{
		// works by person
		$args = array(
			'id' => 'https://orcid.org/0000-0001-7047-4680' 
		);	
		$result = person_works_query($args);
	}	

	if (0)
	{
		// organisation
		$args = array(
			'id' => 'https://doi.org/10.13039/100007698' 
		);	
		$result = organisation_query($args);
	}	
	
	if (0)
	{
		// affiliation
		$args = array(
			'id' => 'https://orcid.org/0000-0001-8259-3783' 
		);	
		$result = person_affiliation_query($args);
	}	
	
	
	if (0)
	{
		// scientific names by person
		$args = array(
			'id' => 'https://orcid.org/0000-0002-2168-0514' 
		);	
		$result = person_scientific_names_query($args);
	}	

	if (0)
	{
		// scientific names
		$args = array(
			'id' => 'urn:lsid:ipni.org:names:77191970-1' 
		);	
		$result = taxon_name_query($args);
	}	
	
	if (1)
	{
		// scientific names
		$args = array(
			'id' => 'urn:lsid:ipni.org:names:77191970-1' 
		);	
		$result = taxon_name_works_query($args);
	}	
		
	
	
	print_r($result);

}


?>

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
//$identifier->{'@type'} = "@id";
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
$context->titles	= "gql:titles";

$context->researchgate	= "gql:researchgate";
$context->wikidata		= "gql:wikidata";


// so we can have dois as keys
$context->bibo = 'http://purl.org/ontology/bibo/';
$context->doi  = "bibo:doi";	


$results = new stdclass;
$results->{'@id'} = "gql:results";
$results->{'@container'} = "@set";
$context->results = $results;

$score = new stdclass;
$score->{'@id'} = "gql:score";
$score->{'@type'} = "http://www.w3.org/2001/XMLSchema#double";
$context->score = $score;

// thumbnailUrl as string
$thumbnailUrl = new stdclass;
$thumbnailUrl->{'@id'} = "thumbnailUrl";
$thumbnailUrl->{'@type'} = "@id";
$context->thumbnailUrl = $thumbnailUrl;

// other vocabs
$context->dwc = "http://rs.tdwg.org/dwc/terms/";
$context->tn  = "http://rs.tdwg.org/ontology/voc/TaxonName#";

$context->rankString  = "tn:rankString";


$config['context'] = $context;

//----------------------------------------------------------------------------------------
// type-specific contexts

$creativework_context = clone $context;

// contentUrl as string
$contentUrl = new stdclass;
$contentUrl->{'@id'} = "contentUrl";
$contentUrl->{'@type'} = "@id";
$creativework_context->contentUrl = $contentUrl;


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

$config['creativework_context'] = $creativework_context;

// hack
//$creativework_context->container = "gql:container";

//----------------------------------------------------------------------------------------

$specimen_context = clone $context;
//$specimen_context->dwc = "http://rs.tdwg.org/dwc/terms/";

$specimen_context->catalogNumber = "dwc:catalogNumber";
$specimen_context->collectionCode = "dwc:collectionCode";
$specimen_context->institutionCode = "dwc:institutionCode";
$specimen_context->occurrenceID = "dwc:occurrenceID";

$specimen_context->gbif			= "gql:gbif";

$recorded = new stdclass;
$recorded->{'@id'} = "gql:recorded";
$recorded->{'@container'} = "@set";
$specimen_context->{'recorded'} = $recorded;

$identified = new stdclass;
$identified->{'@id'} = "gql:identified";
$identified->{'@container'} = "@set";
$specimen_context->{'identified'} = $identified;


 
$config['specimen_context'] = $specimen_context;

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
	
	//print_r($context);exit();
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
		
	$doc = JsonLD::compact($json, json_encode($context));
	
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
			unset($doc->{'@context'});
		
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
			$result = array();
		}
	}
	
	return $result;
}	

//----------------------------------------------------------------------------------------
// Search result query where return a list of results and details on the search
function search_object_query($args, $sparql, $context = null)
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
	
	//print_r($doc);
	
	if (isset($doc->{'@graph'}))
	{
		// We need to frame it
		
		$frame = (object)array(
				'@context' => $context,
				'@type' => 'http://schema.org/DataFeed'
			);

		$framed = JsonLD::frame($json, json_encode($frame));
		
		$doc = $framed->{'@graph'}[0];
		
	
	}
	
	// post process 
	
	// hits?
	if (!isset($doc->results))
	{
		$doc->results = array();
	}
	else
	{	
		foreach ($doc->results as &$result)
		{
			if (isset($result->name))
			{
				$result->name = literals_to_array($result->name);
			}	
		}
	}
		
	// cleanup
	if (isset($doc->{"@context"}))
	{
		unset($doc->{"@context"});
	}
	
	return $doc;
}	


//----------------------------------------------------------------------------------------
// Query for a single thing
function thing_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
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
	
	//print_r($doc);
	
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
				
				case 'dwc:PreservedSpecimen':
					$schema_types[] = 'Sample';
					break;		

				case 'ScholarlyArticle':
					$schema_types[] = 'ScholarlyArticle';
					break;		
		
				case 'TaxonName':
				case 'tn:TaxonName':
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
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?item rdf:type ?type . 

	 ?item schema:name ?name .
	 
	 ?item schema:url ?url .
	 
	  ?item schema:isBasedOn ?work .
	  ?work gql:titles ?title .
	  ?work bibo:doi ?doi .
	  
	  
	  ?item tn:rankString ?rankString .
	  
	 
	 
	}
	WHERE
	{
	  VALUES ?item { <' . $args['id'] . '> }
  
	  ?item rdf:type ?type .
	  
	{
    	?item schema:name ?name .
    }
    UNION
    {
     ?item tn:nameComplete ?name .
    }
    
    # ION
    OPTIONAL
    {
    	?item rdfs:seeAlso ?url .
    }    
    
    
    # rank
    OPTIONAL
    {
    	?item tn:rankString ?rankString .
    }
	  	  
	  # publication (via "glue" )
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
	
			case 'tn:TaxonName':
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
// List of variants of a taxon name, such objective synonyms, as a list.
// We are matching edges in a graph, these edges may be in either direction w.r.t. to 
// our target node.
function taxon_name_alternate_name_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?source schema:name ?name	.
	 ?source a ?type . 
	}
	WHERE
	{
	  VALUES ?target { <' . $args['id'] . '> }
	  
	  ?source (tn:hasBasionym|^tn:hasBasionym)* ?target .
	    
      {
         ?source schema:name ?name .
      }
      UNION
      {
    	?source tn:nameComplete ?name .
      }
 
   	  ?source a ?type .
  	
  	  FILTER(?source != ?target)
	} 
	';

	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

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
	 
	 ?work schema:thumbnailUrl ?thumbnailUrl .	
	 
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
		
		OPTIONAL
		{
			?work schema:thumbnailUrl ?thumbnailUrl .	
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
	 
	 ?work schema:thumbnailUrl ?thumbnailUrl .
	 
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
		
		OPTIONAL
		{
			?work schema:thumbnailUrl ?thumbnailUrl .	
		}

		
	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// Work(s) containing this image
function image_container($args)
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
	  
	  {
	  	?this schema:isPartOf ?work .
	  }
	  union
	  {
	  	?work schema:hasPart ?this .
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

	 ?work schema:url ?url .
	 
	 ?work schema:thumbnailUrl ?thumbnailUrl .
	 
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
		
		OPTIONAL
		{
			# get things that work is sameAs, and things sameAs work
			?work schema:thumbnailUrl ?thumbnailUrl
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
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
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
// List of works on name or its alternative names
function taxon_name_alternate_works_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
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
	  VALUES ?target { <' . $args['id'] . '> }
	  
	  ?taxonName(tn:hasBasionym|^tn:hasBasionym)* ?target .
	  
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
// List of images in papers by an author
function person_images_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?image a ?type . 
	 ?image schema:thumbnailUrl ?thumbnailUrl .
	}
	WHERE
	{
	  VALUES ?person { <' . $args['id'] . '> }
	  VALUES ?type { schema:ImageObject }
	  
  	  ?work schema:creator ?person .
  
  	  # figures in works by author
  	  {
	  	?image schema:isPartOf ?work .
	  }
	  union
	  {
	  	?work schema:hasPart ?image .
	  }
	  ?image schema:thumbnailUrl ?thumbnailUrl .
	  
	  ?image rdf:type ?type .	  
		
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
	  	  
	  #?image rdf:type ?type .
	  ?image rdf:type schema:ImageObject .
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
	?image schema:caption ?caption .
	
	?work schema:mainEntityOfPage ?mainEntityOfPage .	
	
	?work schema:description ?description .
	
	
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
	
	OPTIONAL { ?work schema:description ?description . }
        
		
		
	OPTIONAL 
	{
		?image schema:isPartOf ?work . 
		?image rdf:type schema:ImageObject .
		?image schema:thumbnailUrl ?thumbnailUrl .
		?image schema:contentUrl ?contentUrl .
		OPTIONAL {
		?image schema:description ?caption .
		}
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
	 
	 ?thing gql:researchgate ?researchgate .
	 ?thing gql:wikidata ?wikidata .
	 ?thing schema:thumbnailUrl ?thumbnailUrl .
	 
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
  		
  		# stuff from ResearchGate 
  		OPTIONAL
  		{
  			?rg_profile schema:sameAs ?thing.
  			
			 OPTIONAL
			  {
				?rg_profile schema:sameAs ?researchgate_url .
				FILTER regex(STR(?researchgate_url), "researchgate.net") .
				BIND( REPLACE( STR(?researchgate_url),"https://www.researchgate.net/profile/","" ) AS ?researchgate). 
			  }
  
			  OPTIONAL
			  {
				?rg_profile schema:sameAs ?wikidata_url .
				FILTER regex(STR(?wikidata_url), "wikidata") .
				BIND( REPLACE( STR(?wikidata_url),"http://www.wikidata.org/entity/","" ) AS ?wikidata). 
			  }  
			  
			  OPTIONAL
			  {
			  	?rg_profile schema:image ?image .
			  	?image schema:contentUrl ?thumbnailUrl .
			  } 		
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


//----------------------------------------------------------------------------------------
// List of works for a creator
function search_query($args)
{
	global $config;
	
	// Blazegraph full text (slow and ugly)
	$sparql = 'PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX bds: <http://www.bigdata.com/rdf/search#>
PREFIX gql: <' . $config['hack_uri'] . '>

CONSTRUCT
{
	<http://example.rss> a schema:DataFeed;
		gql:results ?item .
 
	?item a schema:DataFeedItem .
    ?item a ?type .
    ?item schema:name ?name .
    
    ?item schema:thumbnailUrl ?thumbnailUrl .
  
    ?item gql:score ?score .
    
    ?item schema:identifier ?identifier .
}
WHERE
{
  VALUES ?string { "' . addcslashes($args['query'], '"') . '" }
  ?name bds:search  ?string .
  ?name bds:relevance  ?score .

  {
    ?item schema:name ?name .
  }
  UNION
  {
    ?item schema:alternateName ?name .
  }
  
  OPTIONAL 
  {
	  ?item schema:thumbnailUrl ?thumbnailUrl .
  }
  
  OPTIONAL
  {
    ?item schema:identifier ?pv .
    ?pv schema:propertyID ?propertyID .
    ?pv schema:value ?value .
    BIND(CONCAT(?propertyID, ":", ?value) AS ?identifier)
  }  
 
  ?item a ?type .
  
}
ORDER BY DESC(?score)
LIMIT 10



	';
	
	// simple literal search
	$sparql = '
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
PREFIX gql: <' . $config['hack_uri'] . '>

CONSTRUCT
{
	<http://example.rss> a schema:DataFeed;
		gql:results ?item .
 
	?item a schema:DataFeedItem .
    ?item a ?type .
    ?item schema:name ?name .
    
    ?item schema:thumbnailUrl ?thumbnailUrl .

    ?item schema:identifier ?identifier .
}
WHERE
{
  VALUES ?query { "' . addcslashes($args['query'], '"') . '" }

  ?item schema:name|dc:title|tn:nameComplete|schema:alternateName|schema:keywords ?query .
  
  {
  	?item schema:name|dc:title|tn:nameComplete ?name .
  }
  
  OPTIONAL 
  {
	  ?item schema:thumbnailUrl ?thumbnailUrl .
  }
  
  OPTIONAL
  {
    ?item schema:identifier ?pv .
    ?pv schema:propertyID ?propertyID .
    ?pv schema:value ?value .
    BIND(CONCAT(?propertyID, ":", ?value) AS ?identifier)
  }  
 
  ?item a ?type .
  
}
LIMIT 10';	
	
	//echo $sparql;
	
	$doc = search_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// Query for a scientific name
function specimen_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?specimen rdf:type ?type . 

	 ?specimen schema:name ?name .
	 
	  ?specimen dwc:institutionCode ?institutionCode .
	  ?specimen dwc:collectionCode ?collectionCode .
	  ?specimen dwc:catalogNumber ?catalogNumber .
	  ?specimen dwc:occurrenceID ?occurrenceID .
	 
	  ?specimen gql:gbif ?gbif .
	  
     ?specimen gql:identified ?identified .
      ?specimen gql:recorded ?recorded .

	  
	 
	}
	WHERE
	{
	  VALUES ?specimen { <' . $args['id'] . '> }
  
	  ?specimen rdf:type ?type .
	  
		OPTIONAL { ?specimen dwc:institutionCode ?institutionCode } .
		OPTIONAL { ?specimen dwc:collectionCode ?collectionCode } .
		OPTIONAL { ?specimen dwc:catalogNumber ?catalogNumber } .
		OPTIONAL { ?specimen dwc:occurrenceID ?occurrenceID } .

		OPTIONAL { ?specimen dwc:decimalLatitude ?decimalLatitude } .
		OPTIONAL { ?specimen dwc:decimalLongitude ?decimalLongitude } .
    
        BIND (CONCAT(?institutionCode, " ", ?collectionCode,  " ", ?catalogNumber) AS ?name)

		OPTIONAL { 
		  ?specimen schema:sameAs ?sameAs . 
		  BIND(REPLACE(STR(?sameAs), "https://gbif.org/occurrence/" , "") AS ?gbif)		 
		}
		
		OPTIONAL { 
        ?specimen dwciri:identifiedBy ?identifiedBy .
          ?identifiedBy schema:sameAs ?identifiedString .
          BIND(IRI(?identifiedString) AS ?identified) .
      
      } 
      
      
      OPTIONAL { 
        ?specimen dwciri:recordedBy ?recordedBy .  
        ?recordedBy schema:sameAs ?recordedString .
         BIND(IRI(?recordedString) AS ?recorded) .
	    
	   }
	    		

	}   
	';

	//echo $sparql;
	
	$doc = one_object_query($args, $sparql, $config['specimen_context']);
	
	if (isset($doc->name))
	{
		$doc->name = pick_one($doc->name);
	}
	
	

	return $doc;
}	

//----------------------------------------------------------------------------------------
// List of people who identified  a specimen
function specimen_identified_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	  ?person a ?type .
      ?person schema:name ?name .
	}
	WHERE
	{
	  VALUES ?specimen { <' . $args['id'] . '> }
	  
      ?specimen dwciri:identifiedBy ?personIRI .
      ?personIRI schema:sameAs ?personString .
      BIND(IRI(?personString) AS ?person) .
      
      ?person a ?type .
      
	 # not every creator has a name
	 {
		 ?person schema:name ?name .
	 }
	 UNION
	 {
	   ?person schema:givenName ?givenName .         
	   ?person schema:familyName ?familyName .
   
	   BIND(CONCAT(?givenName, " ", ?familyName) AS ?name)           
	 }

	    


	} 
	';
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	  ?person a ?type .
      ?person schema:name ?name .
	}
	WHERE
	{
	  VALUES ?specimen { <' . $args['id'] . '> }
	  
      ?specimen dwciri:identifiedBy ?bionomia .
      ?bionomia schema:sameAs ?person .
      
      ?person a ?type .
      
	 # not every creator has a name
	 {
		 ?person schema:name ?name .
	 }
	 UNION
	 {
	   ?person schema:givenName ?givenName .         
	   ?person schema:familyName ?familyName .
   
	   BIND(CONCAT(?givenName, " ", ?familyName) AS ?name)           
	 }

	    


	} 
	';
		
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);
	
	foreach ($doc as &$person)
	{
		
		// force name to be a single string
		if (isset($person->name))
		{
			$person->name = pick_one($person->name);
		}
		

		// extract an ORCID if we have one
		if (preg_match('/orcid.org\/(?<id>\d{4}-\d{4}-\d{4}-\d{3}(\d|X)$)/', $person->id, $m))
		{
			$person->orcid = $m['id'];
		}			
	}
	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of people who recorded a specimen
function specimen_recorded_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	  ?person a ?type .
      ?person schema:name ?name .
	}
	WHERE
	{
	  VALUES ?specimen { <' . $args['id'] . '> }
	  
      ?specimen dwciri:recordedBy ?bionomia .
      ?bionomia schema:sameAs ?person .
      
      ?person a ?type .
      
	 # not every creator has a name
	 {
		 ?person schema:name ?name .
	 }
	 UNION
	 {
	   ?person schema:givenName ?givenName .         
	   ?person schema:familyName ?familyName .
   
	   BIND(CONCAT(?givenName, " ", ?familyName) AS ?name)           
	 }

	    


	} 
	';
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);
	
	foreach ($doc as &$person)
	{
		
		// force name to be a single string
		if (isset($person->name))
		{
			$person->name = pick_one($person->name);
		}
		

		// extract an ORCID if we have one
		if (preg_match('/orcid.org\/(?<id>\d{4}-\d{4}-\d{4}-\d{3}(\d|X)$)/', $person->id, $m))
		{
			$person->orcid = $m['id'];
		}			
	}
	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of people who identified  a specimen
function person_identified_specimen_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?specimen rdf:type ?type . 
     ?specimen schema:name ?name .
	}
	WHERE
	{
	  VALUES ?person { <' . $args['id'] . '> }
	  
      BIND(STR(?person) AS ?personString) .
      ?personIRI schema:sameAs ?personString .
 
      ?specimen dwciri:identifiedBy ?personIRI .

      	OPTIONAL { ?specimen dwc:institutionCode ?institutionCode } .
		OPTIONAL { ?specimen dwc:collectionCode ?collectionCode } .
		OPTIONAL { ?specimen dwc:catalogNumber ?catalogNumber } .
		OPTIONAL { ?specimen dwc:occurrenceID ?occurrenceID } .

		OPTIONAL { ?specimen dwc:decimalLatitude ?decimalLatitude } .
		OPTIONAL { ?specimen dwc:decimalLongitude ?decimalLongitude } .
    
        BIND (CONCAT(?institutionCode, " ", ?collectionCode,  " ", ?catalogNumber) AS ?name)

	} 
	';
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?specimen rdf:type ?type . 
     ?specimen schema:name ?name .
	}
	WHERE
	{
	  VALUES ?orcid { <' . $args['id'] . '> }
	  
      ?bionomia schema:sameAs ?orcid .
 
      ?specimen dwciri:identifiedBy ?bionomia .

      	OPTIONAL { ?specimen dwc:institutionCode ?institutionCode } .
		OPTIONAL { ?specimen dwc:collectionCode ?collectionCode } .
		OPTIONAL { ?specimen dwc:catalogNumber ?catalogNumber } .
		OPTIONAL { ?specimen dwc:occurrenceID ?occurrenceID } .

		OPTIONAL { ?specimen dwc:decimalLatitude ?decimalLatitude } .
		OPTIONAL { ?specimen dwc:decimalLongitude ?decimalLongitude } .
    
        BIND (CONCAT(?institutionCode, " ", ?collectionCode,  " ", ?catalogNumber) AS ?name)

	} 
	';
		
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);
	
	foreach ($doc as &$specimen)
	{
		
		// force name to be a single string
		if (isset($specimen->name))
		{
			$specimen->name = pick_one($specimen->name);
		}
	}
	
	
	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of people who identified  a specimen
function person_recorded_specimen_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?specimen rdf:type ?type . 
     ?specimen schema:name ?name .
	}
	WHERE
	{
	  VALUES ?person { <' . $args['id'] . '> }
	  
      BIND(STR(?person) AS ?personString) .
      ?personIRI schema:sameAs ?personString .
 
      ?specimen dwciri:recordedBy ?personIRI .

      	OPTIONAL { ?specimen dwc:institutionCode ?institutionCode } .
		OPTIONAL { ?specimen dwc:collectionCode ?collectionCode } .
		OPTIONAL { ?specimen dwc:catalogNumber ?catalogNumber } .
		OPTIONAL { ?specimen dwc:occurrenceID ?occurrenceID } .

		OPTIONAL { ?specimen dwc:decimalLatitude ?decimalLatitude } .
		OPTIONAL { ?specimen dwc:decimalLongitude ?decimalLongitude } .
    
        BIND (CONCAT(?institutionCode, " ", ?collectionCode,  " ", ?catalogNumber) AS ?name)

	} 
	';
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?specimen rdf:type ?type . 
     ?specimen schema:name ?name .
	}
	WHERE
	{
	  VALUES ?orcid { <' . $args['id'] . '> }
	  
      ?bionomia schema:sameAs ?orcid .
 
      ?specimen dwciri:recordedBy ?bionomia .

      	OPTIONAL { ?specimen dwc:institutionCode ?institutionCode } .
		OPTIONAL { ?specimen dwc:collectionCode ?collectionCode } .
		OPTIONAL { ?specimen dwc:catalogNumber ?catalogNumber } .
		OPTIONAL { ?specimen dwc:occurrenceID ?occurrenceID } .

		OPTIONAL { ?specimen dwc:decimalLatitude ?decimalLatitude } .
		OPTIONAL { ?specimen dwc:decimalLongitude ?decimalLongitude } .
    
        BIND (CONCAT(?institutionCode, " ", ?collectionCode,  " ", ?catalogNumber) AS ?name)

	} 
	';
	
	
	//echo $sparql;
	
	$doc = list_object_query($args, $sparql);
	
	foreach ($doc as &$specimen)
	{
		
		// force name to be a single string
		if (isset($specimen->name))
		{
			$specimen->name = pick_one($specimen->name);
		}
	}
	
	
	return $doc;	

}	


if (0)
{
	if (0)
	{
		// generic thing
		$args = array(
			//'id' => 'https://www.catalogueoflife.org/data/taxon/7NN8R' 
			'id' => 'https://bionomia.net/occurrence/416830699'
			
		);	
		$result = thing_query($args);
	}
	
	if (0)
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
//			'id' => 'https://doi.org/10.1080/03036758.2017.1287101',
			'id' => 'https://doi.org/10.5281/zenodo.1050060',
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

	if (1)
	{
		// scientific names
		$args = array(
			//'id' => 'urn:lsid:ipni.org:names:77191970-1' 
			//'id' => 'urn:lsid:organismnames.com:name:5404959', 
			'id' => 'urn:lsid:indexfungorum.org:names:553742',
		);	
		$result = taxon_name_query($args);
	}	
	
	if (1)
	{
		// scientific names
		$args = array(
			'id' => 'urn:lsid:ipni.org:names:77158272-1' 
		);	
		$result = taxon_name_works_query($args);
	}	
		
	if (0)
	{
		// search
		$args = array(
			//'query' => 'Newmania: A new ginger genus from central Vietnam' // 'Scaphisoma' 
			'query' => 'Paramollugo'
		);	
		$result = search_query($args);
	}	

	if (0)
	{
		// search
		$args = array(
			'id' => 'https://orcid.org/0000-0002-5329-7608' 
		);	
		$result = person_images_query($args);
	}	
	
	if (0)
	{
		// specimen
		$args = array(
		// https://bionomia.net/occurrence/1844429110
			'id' => 'https://bionomia.net/occurrence/416830699' 
		);	
		$result = specimen_query($args);
	}	
	
	if (0)
	{
		// who identified?
		$args = array(
			'id' => 'https://bionomia.net/occurrence/1844429110' 
		);	
		$result = specimen_identified_query($args);
	}	

	if (0)
	{
		// who recorded?
		$args = array(
			'id' => 'https://bionomia.net/occurrence/416844542' 
		);	
		$result = specimen_recorded_query($args);
	}	

	if (0)
	{
		//  identified?
		$args = array(
			'id' => 'https://orcid.org/0000-0003-3522-9342' 
		);	
		$result = person_identified_specimen_query($args);
	}	
	
	if (0)
	{
		//  recorded?
		$args = array(
			'id' => 'https://orcid.org/0000-0003-3522-9342' 
		);	
		$result = person_recorded_specimen_query($args);
	}	
	
	
	if (0)
	{
		$args = array(
			'id' => 'urn:lsid:indexfungorum.org:names:513483' 
		);	
		$result = taxon_name_alternate_name_query($args);
	}	

	if (0)
	{
		$args = array(
			'id' => 'urn:lsid:ipni.org:names:77203835-1' 
		);	
		$result = taxon_name_alternate_works_query($args);
	}	
	
	
	
	
	print_r($result);

}


?>

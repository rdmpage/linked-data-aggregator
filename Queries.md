# Queries

## Sameas

```
PREFIX schema: <http://schema.org/>
SELECT * 
WHERE 
{
  VALUES ?work { <https://doi.org/10.5852/ejt.2020.629> }
  ?work (schema:sameAs|^schema:sameAs)* ?sameAs .
}
```

## Search

### Search by a word or string

```
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX bds: <http://www.bigdata.com/rdf/search#>
SELECT * 
#FROM <https://www.catalogueoflife.org>
WHERE {
  ?name bds:search "Nyssorhynchus" .
  ?name bds:relevance  ?score .
  ?work schema:name ?name .
  ?work a schema:CreativeWork .
  
  OPTIONAL
  {
   ?work schema:identifier ?identifier . 
    ?identifier schema:propertyID "doi" .
    ?identifier schema:value ?doi .
   }
  #FILTER(?score > 0.5)
}
ORDER BY DESC(?score)
LIMIT 20
```

### Find similar articles by title

In this case the articles are both in the same ORCID profile: https://orcid.org/0000-0002-5301-7020

```
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX bds: <http://www.bigdata.com/rdf/search#>
SELECT * 
#FROM <https://www.catalogueoflife.org>
WHERE {
  ?name bds:search "Molecular taxonomy provides new insights into anopheles species of the neotropical arribalzagia series." .
  ?name bds:relevance  ?score .
  ?work schema:name ?name .
  
  OPTIONAL
  {
   ?work schema:identifier ?identifier . 
    ?identifier schema:propertyID "doi" .
    ?identifier schema:value ?doi .
   }
  FILTER(?score > 0.5)
}
ORDER BY DESC(?score)
LIMIT 10
```


## Taxonomic names

### basionyms

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
PREFIX urn: <http://fliqz.com/>
SELECT * WHERE {
    ?n1 (tn:hasBasionym|^tn:hasBasionym)* <urn:lsid:indexfungorum.org:names:513483> .
} 
```

```
PREFIX schema: <http://schema.org/>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
	PREFIX gql: <http://example.com/>

	CONSTRUCT
	{
	 ?source schema:name ?name	.
	 ?source a ?type .
	}
	WHERE
	{
	  VALUES ?target { <urn:lsid:indexfungorum.org:names:513483> }
	
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
```

### Search by name

```
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
PREFIX gql: <http://example.com/>

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
  VALUES ?name { "Begonia" }

  {
    ?item schema:name|dc:title|tn:nameComplete ?name .
  }
  UNION
  {
    ?item schema:alternateName ?name .
  }
  UNION
  {
    ?item schema:keywords ?name .
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
LIMIT 10
```

### types

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
SELECT * WHERE {
  <urn:lsid:ipni.org:names:77207009-1> <http://rs.tdwg.org/ontology/voc/TaxonName#typifiedBy> ?x . 
  ?x ?y ?z .
  
} 

```

## Works

### identifiers

```
prefix schema: <http://schema.org/>
select * where
{
  #?work schema:creator <https://orcid.org/0000-0002-0497-166X> .
  ?work a schema:CreativeWork .
  ?work a ?type .
  ?work schema:name ?name .
  OPTIONAL
  {
    ?work schema:identifier ?identifier .
    ?identifier schema:propertyID ?id .
    ?identifier schema:value ?value .
  }
  
  FILTER (isBlank (?work))
  
}
LIMIT 100
```

## Geospatial

### points

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <http://schema.org/>
SELECT * WHERE {
  
  ?treatment schema:isPartOf <https://doi.org/10.11646/zootaxa.4607.1.1> .
  ?treatment a schema:ScholarlyArticle .
  ?treatment schema:spatial ?place .  
  
  
  ?place a schema:Place .
  ?place schema:name ?name .
  ?place schema:geo ?geo .
  ?geo schema:latitude ?latitude .
  ?geo schema:longitude ?longitude .
  
  
  

  
} 
LIMIT 10
```

## People

### Multiple names with same oRCID

```
PREFIX schema: <http://schema.org/>

select * where {
  GRAPH ?g { <https://orcid.org/0000-0003-4418-0552> schema:name ?name . }
}
```

## Glue

### Name and publication

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX prefix: <http://prefix.cc/>
PREFIX urn: <http://fliqz.com/>
prefix tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
prefix tcom: <http://rs.tdwg.org/ontology/voc/Common#>

SELECT * WHERE {
  OPTIONAL
  {
	<urn:lsid:indexfungorum.org:names:513483> tcom:publishedInCitation ?pub .
  ?pub ?x ?y .
  }
  OPTIONAL
  {
    <urn:lsid:indexfungorum.org:names:513483> tcom:publishedIn ?citation .
  }
  
} 
LIMIT 10
```


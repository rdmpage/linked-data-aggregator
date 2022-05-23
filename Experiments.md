# Experiments

Notes on various datasets while we explore

## Interesting examples

PID | Name | Notes
--|--|--
https://doi.org/10.11646/zootaxa.4196.3.9 | Photography-based taxonomy is inadequate, unnecessary, and potentially harmful for biological sciences | Lots of ORCIDs
https://doi.org/10.1371/journal.pbio.2005075 | Taxonomy based on science is necessary for global conservation | Lots of ORCIDs
https://doi.org/10.11646/zootaxa.4061.1.9 | The Shannoniella sisters (Diptera: Rhinophoridae) | ORCID and Wikispecies

## Glue

### IPNI

Names and DOIs

```
SELECT CONCAT('<urn:lsid:ipni.org:names:', Id, '> <http://schema.org/isBasedOn> <https://doi.org/', 
LOWER(REPLACE(REPLACE(doi, '<', '%3c'),'>', '%3e')),
 '> .') 
FROM names 
WHERE doi IS NOT NULL;


SELECT CONCAT('<urn:lsid:ipni.org:names:', Id, '> <http://schema.org/name> "', Full_name_without_family_and_authors, '" .') 
FROM names 
WHERE  doi IS NOT NULL and Full_name_without_family_and_authors <> "";

SELECT CONCAT('<urn:lsid:ipni.org:names:', Id, '> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/TaxonName> .') 
FROM names 
WHERE doi IS NOT NULL;


```

### ION

```
SELECT CONCAT('<urn:lsid:organismnames.com:name:', id, '> <http://schema.org/isBasedOn> <https://doi.org/', 
LOWER(REPLACE(REPLACE(doi, '<', '%3c'),'>', '%3e')),
 '> .') 
FROM names 
WHERE doi IS NOT NULL;


SELECT CONCAT('<urn:lsid:organismnames.com:name:', id, '> <http://schema.org/name> "', nameComplete, '" .') 
FROM names 
WHERE  doi IS NOT NULL and nameComplete IS NOT NULL;

SELECT CONCAT('<urn:lsid:organismnames.com:name:', id, '> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/TaxonName> .') 
FROM names 
WHERE doi IS NOT NULL;
```

### IndexFungorum

### Phytotaxa

```
SELECT CONCAT('<https://doi.org/', guid, '> <http://schema.org/citation> <https://doi.org/', doi, '> .') 
FROM cites 
WHERE doi LIKE "10.%";
```

### Wikispecies and ORCID

#### Comparing authors

```
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

CONSTRUCT
{
	<http://example.rss1> a schema:DataFeed;
		schema:dataFeedElement ?authors1.
  
 	<http://example.rss2> a schema:DataFeed;
		schema:dataFeedElement ?authors2 .

	?authors1 a schema:DataFeedItem .
    ?authors1 schema:name ?name .

  	?author2 a schema:DataFeedItem .
    ?authors2 schema:name ?creator_name .
  
}
WHERE
{
  VALUES ?work { <https://doi.org/10.11646/zootaxa.4061.1.9> }
         
  # Wikispecies
  
  ?work schema:author/rdf:rest*|rdf:first ?list_element .
  ?list_element rdf:first ?authors1 .
  {
	  ?authors1 schema:name ?name .
  }
  UNION
  {
       ?authors1 schema:mainEntityOfPage ?page .
    	?page schema:name ?name .
  }



  
         
         
         
  # ORCID
  ?work schema:creator ?authors2 .
  {
  	?authors2 schema:name ?creator_name .
  } 
  UNION
  {
  	?authors2 schema:familyName ?family_name .
  	?authors2 schema:givenName ?given_name .
    BIND(CONCAT(?given_name, ' ', ?family_name ) AS ?creator_name)
  }
  UNION
  {
  	  ?authors2 schema:alternateName ?creator_name .
  }
  
}

```




# Experiments

Notes on various datasets while we explore

## Issues

- http://localhost:4000/index.html?id=https://orcid.org/0000-0002-5350-4267 shows images in list of works as these are lined to author’s ORCID by Zenodo, but this images don’t appear in “Images” section as that assumes link is person -> work -> image.

- do we need to extend notion of citation to match Plazi’s use of treatments citing images?

## Interesting examples

PID | Name | Notes
--|--|--
https://doi.org/10.11646/zootaxa.4196.3.9 | Photography-based taxonomy is inadequate, unnecessary, and potentially harmful for biological sciences | Lots of ORCIDs
https://doi.org/10.1371/journal.pbio.2005075 | Taxonomy based on science is necessary for global conservation | Lots of ORCIDs
https://doi.org/10.11646/zootaxa.4061.1.9 | The Shannoniella sisters (Diptera: Rhinophoridae) | ORCID and Wikispecies
https://orcid.org/0000-0001-6974-7741 | Sforzi, Alessandra | Lots of works that are Zenodo DOIs for treatments
https://doi.org/10.1080/00222930500145057 | | The genus Lilloiconcha in Colombia (Gastropoda: Charopidae) | Images in Zenodo, linked to CoL by DOI
https://doi.org/10.5281/zenodo.3253620 |FIGURE 1 in The Hirtodrosophila melanderi species group (Diptera: Drosophilidae) from the Huanglong National Nature Reserve, Sichuan, China | Phylogeny image
https://orcid.org/0000-0003-3952-9393 | Terry Griswold | Lots of images
https://doi.org/10.3897/zookeys.473.8659 | Systematics of the family Plectopylidae in Vietnam with additional information on Chinese taxa (Gastropoda, Pulmonata, Stylommatophora) | abstract, images
https://orcid.org/0000-0002-7277-1934 | Jonathan Ablett | LOTS of images
https://doi.org/10.11646/zootaxa.3825.1.1 | Molecular systematics of terraranas (Anura: Brachycephaloidea) with an assessment of the effects of alignment and optimality criteria | lots of cites/cited by, images are all of phylogeny 
https://orcid.org/0000-0002-6076-8463 | Casagrande, Mirna Martins | butterfly images
https://doi.org/10.5281/zenodo.6573246 | Splendeuptychia tupinamba Freitas, Huertas & Rosa 2021, sp. nov. | A treatment that cites images, and which has a keyword that matches the new species name. 

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




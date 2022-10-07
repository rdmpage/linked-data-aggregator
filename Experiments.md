# Experiments

Notes on various datasets while we explore

## Issues

- http://localhost:4000/index.html?id=https://orcid.org/0000-0002-5350-4267 shows images in list of works as these are lined to author’s ORCID by Zenodo, but this images don’t appear in “Images” section as that assumes link is person -> work -> image.

- do we need to extend notion of citation to match Plazi’s use of treatments citing images?

- Zenodo has given two different people the same ORCID: 

```
PREFIX schema: <http://schema.org/>

select * where {
  GRAPH ?g { <https://orcid.org/0000-0003-4418-0552> schema:name ?name . }
}
```

I think this is due to https://zenodo.org/record/6550195 and all its parts.

## Interesting examples

PID | Name | Notes
--|--|--
https://doi.org/10.1590/2175-7860201869402 | Brazilian flora 2020: Innovation and collaboration to meet target 1 of the global strategy for plant conservation (GSPC) | Huge numbers of ORCIDs 
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
https://orcid.org/0000-0002-3290-5416 | Li, Shuqiang | lots of spider images
https://doi.org/10.5281/zenodo.267559 | FIGURES 135–140 in The Neotropical cuckoo wasp genus Ipsiura| Absurdly colourful wasps
https://doi.org/10.5281/zenodo.3649001 | FIGURE 3 in Three challenges to contemporaneous taxonomy from a licheno-mycological perspective | Interesting map, can we reproduce this from data here?
https://doi.org/10.1111/j.1096-0031.2011.00348.x | Impediments to taxonomy and users of taxonomy: Accessibility and impact evaluation | can we use this to test “related” based on existing citation data?
https://doi.org/10.3897/zookeys.885.38980 | Notes on the sinistral helicoid snail Bertia cambojiensis (Reeve, 1860) from Vietnam (Eupulmonata, Dyakiidae) | nice pics, specimen has sequences, lots of potential BHL citations
https://doi.org/10.3897/phytokeys.73.10365 | Taxonomic revision and distribution of herbaceous Paramollugo (Molluginaceae) in the Eastern Hemisphere | Nice map
urn:lsid:indexfungorum.org:names:807796 | Austroafricana parva | Two references in bibliography
urn:lsid:indexfungorum.org:names:500833 | Colletogloeopsis stellenboschiana | Three names and three references
https://orcid.org/0000-0002-3874-7690 | David Salazar-Valenzuela | This author has a ResearchGate profile that has embedded schema.org with `sameAs` links to ORCID and Wikidata. Note that ORCID doesn’t have the ResearchGate link.
https://doi.org/10.5281/zenodo.6512406 | Begonia ambanizanensis Scherber. & Duruiss. 2019, sp. nov. | Treatment with cited figures, note we have no mention of the LSID 
urn:lsid:ipni.org:names:77207009-1 | Begonia ambanizanensis | IPNI LSID has http://purl.org/dc/terms/bibliographicCitation for DOI and http://rs.tdwg.org/ontology/voc/Common#publishedInCitation for BHL http://www.biodiversitylibrary.org/openurl?ctx_ver&#x3D;Z39.88-2004&rft.date&#x3D;2019&rft.issue&#x3D;1&rft.spage&#x3D;60&rft.volume&#x3D;41&rft_id&#x3D;http://www.biodiversitylibrary.org/bibliography/600&rft_val_fmt&#x3D;info:ofi/fmt:kev:mtx:book&url_ver&#x3D;z39.88-2004
https://doi.org/10.11646/zootaxa.4885.1.3 |The Rhyacophila fasciata Group in Croatia and Bosnia and Herzegovina… | Many variations on the title
https://doi.org/10.11646/zootaxa.3949.4.1 | Revision of the Sundaland species of the genus Dysphaea Selys, 1853 | Nice pics of damselflies
https://doi.org/10.11646/zootaxa.4949.1.4 | Four new species of parasitoid wasp (Hymenoptera: Braconidae) described through a citizen science partnership with schools in regional South Australia | Word cloud and citizen science


## Blank nodes

### Publications

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

### People

```
prefix schema: <http://schema.org/>
select * where
{
  ?person a schema:Person .  
  ?person schema:name ?name .

  FILTER (isBlank (?person))
  
}
LIMIT 100
```

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

### Authors with and without ORCIDs

#### Compare authors of same work

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <http://schema.org/>
SELECT * WHERE {
   VALUES ?work { <https://doi.org/10.3897/phytokeys.73.10365> } 
   ?work schema:creator ?creator .
   ?work schema:name ?name .
   {
       ?creator schema:name ?creator_name .
   }
   UNION
   {
     ?creator schema:givenName ?givenName .         
     ?creator schema:familyName ?familyName .

     BIND(CONCAT(?familyName, ", ", ?givenName) AS ?creator_name)           
   }
} 
```


### Treatments

Can we have a SPARQL query to link LSID names to publications to treatments via identifier and/or string matching so we can, for example, link IPNI names to Plaza treatments?

- SPARQL query
- Do we need to text mine treatment names to extract taxon names?


### Organisations

In ORCID these often don’t have `@id`

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <http://schema.org/>
SELECT * WHERE {
  ?organisation a schema:Organization  .
  ?organisation schema:name ?name .
  
  ?organisation schema:identifier ?identifier .
  ?identifier schema:propertyID "ROR" .
  ?identifier schema:value ?value .
} 
LIMIT 100
```

### Taxon name relationships

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX tn: <http://rs.tdwg.org/ontology/voc/TaxonName#>
PREFIX urn: <http://fliqz.com/>
SELECT * WHERE {
    ?n1 (tn:hasBasionym|^tn:hasBasionym)* <urn:lsid:indexfungorum.org:names:513483> .
} 
```




# Experiments

Notes on various datasets while we explore

## Interesting examples

https://doi.org/10.11646/zootaxa.4196.3.9 (lots of ORCIDs)


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



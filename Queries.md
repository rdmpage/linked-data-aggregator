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
# Linked data aggregator

## Vision
Goal is to have a GBIF-style centralised biodiversity data aggregator but for linked data. While the triple store is centralised, the data is stored independently by the data providers (e.g., in a data repository), and the source code for aggregation is available so that anyone could create their own instance of the aggregation.

## Approach

Data providers would publish linked data in a standard format (ideally n-triples) to a place that they choose. For example, data could be uploaded to repository such as [Zenodo](https://zenodo.org) or [figshare ](https://figshare.com) where it receives a DOI and is hence both citable and independently usable by other projects. This resembles GBIF where providers publish data to GBIF and get a DOI for that dataset.

The aggregator downloads these linked data files and adds them to a central triple store, which provides a single place to query data from multiple providers. The triple store functions rather like the GIF portal where users can query occurrence data from numerous providers (such as museums and citizen science projects).

To describe each RDF source we write a simple configuration file (e.g., in [YAML](https://yaml.org)) for each source. That configuration file is processed to retrieve the RDF, which we then upload to a triple store. These config files are a bit like the [HCLS VoID](https://www.w3.org/TR/hcls-dataset/) files used by the [Linked Open Data platform for EBI data](https://www.ebi.ac.uk/rdf/), but are simpler and are modelled on a [Bioschemas dataset](https://bioschemas.org/profiles/Dataset).

Each data source will have its own “named graph” in the triple store. This makes it easy to trace the provenance of each dataset, and also simplifies management. If a data source updates its data we can simply delete the named graph for that data, then load the new data in its place.

Given that some RDF datasets may be large we need to “chunk” the data into manageable pieces that we can upload to a triple store. This is easy for file formats where each line is independent of any other line. [N-triples](https://en.wikipedia.org/wiki/N-Triples) are a good format, but others such as JSON-LD stored in [JSON lines](https://jsonlines.org) format may also work. XML documents are problematic in that we need to know where to make the split.

The triple store can be queried directly using SPARQL. However, to make the data more accessible a GraphQL interface is provided so that a developer without a background in linked data can still make use of the data.

Hosting linked data in data repositories helps sustainability. If the central triple store goes offline it could be recreated by anyone using the configuration scripts.

## Configuration

For each data source we create a configuration file in YAML. This file is of the form:

```yaml
name: <name of the dataset>
identifier: <citable identifier>
url: <URL for dataset, e.g. its home page>
    
distribution:
    contentUrl: <URL for retrieving data>
    encodingFormat: <MIME type of data file>
```

Field | Description
--- | ---
name | A simple, human readable name for the dataset.
identifier | A citable identifier for the data, such as a DOI if the data is hosted in a repository. 
url | A URL for the dataset, which will be used to identify the `named graph` for that dataset within the triple store. Typically something simple such as the home page for the data, e.g. `https://orcid.org`. 
distribution | This field has two subfields (`contentUrl` and `encodingFormat`) that describe where to get the data and in what format.
contentUrl | URL where we can retrieve the data. This should be a direct link to a downloadable file, not an indirect link such as a DOI. If data is being loaded from a local file then use the `file://` prefix followed by the full path to the file. 
encodingFormat | MIME type for the data retrieved from `encodingFormat`, for example `application/n-triples` WE use this value to tell the triple store how the linked data is encoded.

Note that `contentUrl` supports loading from local files. This is to enable quick and easy testing of examples. Ideally data loaded for production would be freely available from external repositories.

## Loading data

The script `upload.php` will read one or more configuration files, retrieve the data, convert it into smaller chunks if needed, the upload it to the triple store. By default each dataset is loaded into its own named graph, and any data already stored under that graph is deleted.

## Triple stores

Triple stores such as [Oxigraph](https://crates.io/crates/oxigraph_server) and [Blazegraph](https://blazegraph.com) differ in their interfaces for adding data, so we use a configuration file `triplestore.yaml` to provide details on how to communicate with the triplestore. This file will also provide details of your triple store instance. For example, the configuration below is for a local instance of Oxigraph.

```
url:             http://localhost:7878
query_endpoint:  query
upload_endpoint: store
update_endpoint: update
graph_parameter: graph
default_graph:   default
```

## Queries

### Named graphs

Note that we will need to make use of named graphs in our queries, e.g.:

```
PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
SELECT ?name ?page (count(?mid)-1 as ?position)  
FROM <https://species.wikimedia.org>
WHERE { 
  ?work schema:mainEntityOfPage <https://species.wikimedia.org/wiki/Template:Curletti_&_Sakalian,_2009>. 
  # List authors in order  
  ?work schema:author/rdf:rest* ?mid .
  ?mid rdf:rest* ?node .
  ?node rdf:first ?author .
  ?author schema:name ?name .
  OPTIONAL {
  	?author schema:mainEntityOfPage ?page .
  }
}
GROUP BY ?author ?name ?page
ORDER BY ?position
```

Note the `FROM <https://species.wikimedia.org>` clause before `WHERE`.

However, `DESCRIBE` doesn’t need a named graph(!)

### Literal values we can use to search/reconcile



### Exploratory queries

#### Types in a dataset

#### Literals in a dataset

A query like this tells us what sort of values we have in the dataset.

```
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <http://schema.org/>
SELECT ?p (COUNT(?p) AS ?count) 
FROM <https://orcid.org>
WHERE {
  ?s rdf:type schema:Person .
  ?s ?p ?o .
  FILTER isLiteral(?o)
} 
GROUP BY ?p
ORDER BY DESC(?count)
```

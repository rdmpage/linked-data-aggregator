# Templates for harvesting linked data

Code for retrieving links data files from a source that serves RDF as one file per id. We can retrieve these, store them locally, do any post processing we need, then generate a large triple file for import into a triple store.

The files should need only minor modification to work for a new data source

File | Purpose
-- | --
core.php | Core functions, you need to define a URL to retrieve RDF for an identifier, and a function to convert the identifier to an integer to construct the cache directory.
fetch.php | Read a list of identifiers `ids.txt` and fetch RDF
triples.php | Parse downloaded RDF and convert to triples

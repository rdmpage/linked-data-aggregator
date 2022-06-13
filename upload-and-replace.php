<?php

require_once (dirname(__FILE__) . '/core.php');

$triplestore = get_triplestore('blazegraph.yaml');

if (!$triplestore)
{
	exit(1);
}

$sources = array(
	//'iflocal.yaml',
	//'wikispecies.yaml',
	//'orcid.yaml'
	//'markhughes.yaml'
	
	//'uniprot.yaml'
	
	//'col.yaml'
	
	//'zenodo.yaml'
	
	//'glue.yaml'
	//'citation.yaml'
	
	//'bionomia.yaml'
	
	//'ipni.yaml',
	//'if.yaml'
	'ion.yaml'
);


foreach ($sources as $source_filename)
{
	$source = get_source($source_filename);

	if (!$source)
	{
		exit(1);
	}
	
	
	echo "Removing existing data for " . $source->name . "\n";
	
	// clean up any previous upload
	remove_source($triplestore, $source);
	
	echo "Adding data for " . $source->name . "\n";
	
	$break_on_fail = false;
	if (add_source($triplestore, $source, $break_on_fail))
	{
		// all good
	}
	else
	{
		// not good, errors will have been output already
		exit(1);
	}
	
}



?>

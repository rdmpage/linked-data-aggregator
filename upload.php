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
	
	'col.yaml'
);


foreach ($sources as $source_filename)
{
	$source = get_source($source_filename);

	if (!$source)
	{
		exit(1);
	}
	
	echo "Adding data for " . $source->name . "\n";
	
	if (0)
	{
		// clean up any previous upload
		remove_source($triplestore, $source);
	}
	
	if (add_source($triplestore, $source))
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

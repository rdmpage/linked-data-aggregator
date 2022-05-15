<?php

// For each source file generate corresponding triples file

// php -d memory_limit=-1 triples.php

error_reporting(E_ALL);


require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/core.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$cuid = new EndyJasmi\Cuid;


$force = false;


$files1 = scandir($config['cache']);

$nquads = new NQuads();

foreach ($files1 as $directory)
{
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($config['cache'] . '/' . $directory);
		
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.json$/', $filename))
			{
				$id = str_replace('.json', '', $filename);				
				$json = get_one($id);
				
				$output = $config['cache'] . '/' . $directory . '/' . $id . '.nt';
				
				if (!file_exists($output) || $force)
				{				
					echo $id . "\n";
					$quads = JsonLD::toRdf($json);
					$serialized = $nquads->serialize($quads);
					$serialized = fix_triples($serialized);
					file_put_contents($output, $serialized);
				}
				else
				{
					echo "$id done\n";
				}
			}
		}
	}
}

?>



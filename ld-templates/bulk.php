<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/core.php');

$since = 0;

// yesterday
$since = strtotime('-1 day');

$files1 = scandir($config['cache']);

$count = 1;

foreach ($files1 as $directory)
{
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($config['cache'] . '/' . $directory);
		
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.nt$/', $filename))
			{	
				$id = str_replace('.nt', '', $filename);
				$ntfile = $config['cache'] . '/' . $directory . '/' . $filename;
				
				$modified = filemtime($ntfile);
						
				if ($modified > $since)
				{
					$triples = file_get_contents($ntfile);
					echo $triples . "\n";
				}				
				
			}
		}
	}
}

?>

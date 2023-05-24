<?php

/*

Core code to manage triple stores and data loading in a semi-automated fashion

*/

error_reporting(E_ALL);

require_once(__DIR__ . '/vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;

$config['triples_chunk_size'] = 500000;
$config['xml_chunk_size'] 	  =   1000;
$config['sleep']			  =     30;

//----------------------------------------------------------------------------------------
function error_message($message, $errors)
{
	echo "Error: " . $message . "\n";
	
	$count = 0;
	
	foreach ($errors as $source => $error)
	{
		echo "[" . $count++ . "]";
		if (is_array($error))
		{
			echo "  " . $source . "\n";
			foreach ($error as $msg)
			{
				echo "     " . $msg . "\n";
			}
		}
		else
		{
			// simple string message	
			echo "   " . $error . "\n";
		}
	}
}

//----------------------------------------------------------------------------------------
function chunk_triples($triples_filename, $chunks = 500000, $destination_dir = '')
{
	$handle   = null;
	$basename = basename($triples_filename, '.nt');

	if ($destination_dir == '')
	{
		$destination_dir = sys_get_temp_dir();
	}
	
	echo "Chunks will be written to $destination_dir\n";	
	echo "Generating chunks...\n";

	$chunk_files = array();
	
	$total = 0;
	$count = 0;

	$file_handle = fopen($triples_filename, "r");
	if (!$file_handle) { die ("Could not open file $triples_filename line: " . __LINE__ . "\n"); }
	
	while (!feof($file_handle)) 
	{
		if ($count == 0)
		{
			$output_filename = $destination_dir . '/' . $basename . '-' . $total . '.nt';
			$chunk_files[] = $output_filename;
			$handle = fopen($output_filename, 'w');
		}

		$line = fgets($file_handle);
	
		fwrite($handle, $line);
	
		if (!(++$count < $chunks))
		{
			fclose($handle);
		
			$total += $count;
		
			echo $total . "\n";
			$count = 0;		
		}
	}

	fclose($handle);
	
	return $chunk_files;
}



//----------------------------------------------------------------------------------------
function chunk_xml($xml_filename, $chunks = 1000, $destination_dir = '')
{
	$handle   = null;
	$basename = basename($xml_filename, '.xml');

	if ($destination_dir == '')
	{
		$destination_dir = sys_get_temp_dir();
	}
	
	echo "Chunks will be written to $destination_dir\n";	
	echo "Generating chunks...\n";

	$chunk_files = array();
	
	$total = 0;
	$count = 0;
	
	$state = 0;
	$header = '';
	

	$file_handle = fopen($xml_filename, "r");
	if (!$file_handle) { die ("Could not open file $xml_filename line: " . __LINE__ . "\n"); }

	while (!feof($file_handle)) 
	{	
		$line = fgets($file_handle);
		
		if (preg_match('/<rdf:Description/', $line))
		{
			$state = 1;
			
			if ($count == 0)
			{
				$output_filename = $destination_dir . '/' . $basename . '-' . $total . '.xml';
				$chunk_files[] = $output_filename;
				$handle = fopen($output_filename, 'w');
			
				if ($header != '')
				{
					fwrite($handle, $header);
				}
			}
			
			$count++;
			
		}
		
		if (preg_match('/<\/rdf:Description>/', $line))
		{
			$state = 2;
		}
		
		switch ($state)
		{
			case 0:
				$header .= $line;
				break;
				
			case 1:
			
				// can filter out stuff we don't want here
				$ok = true;
				
				if (preg_match('/<skos:narrowerTransitive/', $line))
				{
					$ok = false;
				}
			
				if ($ok)
				{
					fwrite($handle, $line);
				}
				break;
				
			case 2:
				fwrite($handle, $line);
				
				if (!($count < $chunks))
				{
					fwrite($handle, "</rdf:RDF>\n");
					fclose($handle);
		
					$total += $count;
		
					echo $total . "\n";
					$count = 0;	
					
					$state = 3;	
				}
				break;
				
			default:
				break;
		
		}

	}
	fclose($handle);
	
	return $chunk_files;
}




//----------------------------------------------------------------------------------------
// Get details on a triple store from the configuration file
function get_triplestore($filename = 'triplestore.yaml')
{
	global $config;
	
	// Parse YAML file and convert to object
	$triplestore = (object)(Yaml::parseFile($filename));
	
	// print_r($triplestore);
	
	// sanity checks
	$keys = array('maker', 'url', 'upload_endpoint', 'graph_parameter');
	
	$errors = array();
	foreach ($keys as $k)
	{
		if (!isset($triplestore->{$k}))
		{
			$errors[] = "Missing value for $k";
		}
	}
	
	if (count($errors) > 0)
	{
		error_message("Triple store description not valid", $errors);
		return null;
	}
	
	$triplestore->maker = strtolower($triplestore->maker);
	
	// defaults for uploading
	if (!isset($triplestore->sleep))
	{
		$triplestore->sleep = $config['sleep'];
	}
	if (!isset($triplestore->triples_chunk_size))
	{
		$triplestore->triples_chunk_size = $config['triples_chunk_size'];
	}
	if (!isset($triplestore->xml_chunk_size))
	{
		$triplestore->xml_chunk_size = $config['xml_chunk_size'];
	}

	if (!isset($triplestore->break_on_fail))
	{
		$triplestore->break_on_fail = true;
	}
	

	return $triplestore;
}

//----------------------------------------------------------------------------------------
// Get details on source from configuration file
function get_source($filename = 'source.yaml')
{
	// Parse YAML file and convert to object
	$source = (object)(Yaml::parseFile($filename));
	
	if (isset($source->distribution))
	{
		$source->distribution = (object)$source->distribution;
	}
	
	//print_r($source);
	
	// sanity checks
	$keys = array('name', 'identifier', 'url', 'distribution');
	
	$errors = array();
	foreach ($keys as $k)
	{
		if (!isset($source->{$k}))
		{
			$errors[] = "Missing value for $k";
		}
	}

	if (count($errors) == 0)
	{
		$keys = array('contentUrl', 'encodingFormat');
			
		foreach ($keys as $k)
		{
			if (!isset($source->distribution->{$k}))
			{
				$errors[] = "Missing value for $k";
			}
		}

	}
	
	if (count($errors) == 0)
	{
		if (!preg_match('/^(file:\/\/\/|ftp:\/\/|https?:\/\/)/', $source->distribution->contentUrl, $m))
		{
			$errors[] = "contentUrl must start with one of file:///, ftp://, http://, https://";
		}
	}		
	
	if (count($errors) == 0)
	{
		$mime_types = array(
			'application/n-triples',
			'application/rdf+xml',
			'text/rdf+n3'			
		);
		
		if (!in_array($source->distribution->encodingFormat, $mime_types))
		{
			$errors[] = "\"" . $source->distribution->encodingFormat . "\" is not a valid MIME type."
			 . " Expected one of " . join(', ', $mime_types) . ".";
		}

	}	

	
	if (count($errors) > 0)
	{
		error_message("Source description not valid", $errors);
		return null;
	}

	return $source;
}

//----------------------------------------------------------------------------------------
// Given a list of chunk filenames, and details on the source and triple store,
// upload the chuncks. If we encounter an error we either exit ($triplestore->break_on_fail
// is true) or continue ($triplestore->break_on_fail is false).
// Result is array of error messages, which is empty if everything succeeded.
function upload_chunks($chunk_files, $source, $triplestore)
{	
	$errors = array();
	
	// Enforce format Blazegraph expects for triples (text/rdf+n3)
	if ($triplestore->maker == "blazegraph")
	{
		if ($source->distribution->encodingFormat == "application/n-triples")
		{
			$source->distribution->encodingFormat = "text/rdf+n3";
		}
	}	

	$url = $triplestore->url . '/' . $triplestore->upload_endpoint;
	
	if ($triplestore->graph_parameter == 'rdf-graphs')
	{
		$url .= '/' . $triplestore->graph_parameter . '/' . $source->url;
	}
	else
	{	
		$parameters = array(
			$triplestore->graph_parameter => $source->url
		);
			
		$url .= '?' . http_build_query($parameters);
	}
	
	$num_chunks = count($chunk_files);
	$count = 0;
	
	foreach ($chunk_files as $chunk_filename)
	{
		echo "Uploading $chunk_filename\n";
	
		$curl_parameters = array(
			"'" . $url . "'",
			"--header Content-Type:" . $source->distribution->encodingFormat,
			"--data-binary @'" . $chunk_filename . "'",
			"--progress-bar"	
		);
		
		$command = 'curl ' . join(' ', $curl_parameters);
		
		echo $command . "\n";
		
		$output 		= array();
		$result_code 	= 0;
		exec($command, $output, $result_code);	
		
		//echo "Result code=$result_code\n";
		if ($result_code != 0)
		{
			$errors[$chunk_filename] = $output;
			if ($triplestore->break_on_fail)
			{
				return $errors;
			}							
		}		
		
		if (count($output) > 0)
		{
			// response varies among triple stores
			
			switch ($triplestore->maker)
			{
				// Blazegraph always tells us something
				case 'blazegraph':
					if (preg_match('/<\?xml version="1.0"\?>/', $output[0]))
					{
						// all good
					}
					else
					{
						// badness
						$errors[$chunk_filename] = array_slice($output, 0, 2);
						if ($triplestore->break_on_fail)
						{
							return $errors;
						}										
					}
					break;
			
				// anything back from oxigraph is an error
				case 'oxigraph':
				default:
					$errors[$chunk_filename] = $output;
					if ($triplestore->break_on_fail)
					{
						return $errors;
					}					
					break;
			
			}
			
		}
		
		if ($count++ < $num_chunks-1)
		{			
			echo "Sleeping...\n";
			sleep($triplestore->sleep);
		}
	}
	
	return $errors;
}

//----------------------------------------------------------------------------------------
// Remove a source from triplestore by deleting the corresponding named graph
function remove_source($triplestore, $source)
{
	echo "Removing " .  $source->url . "\n";

	$url = $triplestore->url . '/' . $triplestore->update_endpoint;
	
	$curl_parameters = array(
		"'" . $url . "'",
		"--request POST",
		"--header Content-Type:application/sparql-update",
		"--data 'CLEAR GRAPH <" . $source->url . ">'"
	);
	
	$command = 'curl ' . join(' ', $curl_parameters);
	
	$output 		= array();
	$result_code 	= 0;
	exec($command, $output, $result_code);	
	
	return $result_code;
}

//----------------------------------------------------------------------------------------
// Get RDF from source and store on disk
function get_source_rdf($source, $force = false)
{
	$errors = array();

	// default output name
	$dest_filename = 'output';
	
	// get output name from source URL
	$url_parts = parse_url($source->distribution->contentUrl);	
	
	$dest_filename =  basename($url_parts['path']);
			
	if (!file_exists($dest_filename) || $force)
	{	
		switch ($url_parts['scheme'])
		{
			// If source is a file we copy it to the current directory
			case 'file':
				if (file_exists($url_parts['path']))
				{
					if (!copy($url_parts['path'], dirname(__FILE__) . '/' . $dest_filename))
					{
						$errors[] = "Unable to copy file \"" . $url_parts['path'] . "\"";
					}
				}
				else
				{
					$errors[] = "Source file \"" . $url_parts['path'] . "\" not found.";
				}
				break;
					
			// If source is remote we download it
			case 'ftp':
			case 'http':
			case 'https':
			default:
				$command = "curl -L '" . $source->distribution->contentUrl . "' > '" . $dest_filename. "'";
				echo $command . "\n";
				system($command, $retval);
				echo "\nReturn value = $retval\n";	
				
				if ($retval != 0)
				{
					$errors[] = "Error retrieving data from \"" . $source->distribution->contentUrl . "\"";
				}
				break;
		}	
	
	}
	
	if (count($errors) == 0)
	{
		// do we need to decompress the file?
		$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type a la mimetype extension
		$mime_type = finfo_file($finfo, $dest_filename);
		finfo_close($finfo);
	
		echo "Downloaded file format \"$mime_type\"\n";
	
		// handle different types of compression
		switch ($mime_type)
		{
			case 'application/zip':
				echo "Unzipping file...\n";
				$command = 'unzip ' . $dest_filename;
				
				echo $command . "\n";
				system($command);
			
				// assume simple file name mapping
				$dest_filename = str_replace('.zip', '', $dest_filename);			
				break;
			
			case 'application/x-gzip':
				echo "Expanding file...\n";
				$command = 'gunzip -f -v ' . $dest_filename;
				system($command);
			
				// assume simple file name mapping
				$dest_filename = str_replace('.gz', '', $dest_filename);			
				break;
				
			case 'application/x-xz':
				echo "Expanding file...\n";
				$command = 'xz -d -v ' . $dest_filename;
				system($command);
			
				// assume simple file name mapping
				$dest_filename = str_replace('.xz', '', $dest_filename);			
				
			default:
				// file isn't an archive so simply move it 
				break;
		}
	
		// Infer mime type for RDF, e.g. triples, XML, etc.
		// Or rely on source?
	}
	
	if (count($errors) > 0)
	{
		error_message("Failed to get data", $errors);
		return "";
	}

	return $dest_filename;
}

//----------------------------------------------------------------------------------------
// Add data from a source to the triple store
function add_source($triplestore, $source)
{
	
	$ok = true;
		
	echo "Getting data for " . $source->name . "\n\n";
	
	$rdf_filename = get_source_rdf($source);
	
	if ($rdf_filename == '')
	{
		$ok = false;
	}
	else
	{	
		echo "\nChunking data for " . $source->name . "\n\n";
		
		switch ($source->distribution->encodingFormat)
		{
			case 'application/rdf+xml':			
				$chunks = chunk_xml($rdf_filename, $triplestore->xml_chunk_size);
				break;
				
			case 'application/n-triples':
			case 'text/rdf+n3':
			default:
				$chunks = chunk_triples($rdf_filename, $triplestore->triples_chunk_size);
				break;
		}
		
		print_r($chunks);
		
		echo "Uploading data for " . $source->name . "\n\n";

		$errors = upload_chunks($chunks, $source, $triplestore);

		if (count($errors) > 0)
		{
			error_message("Upload failed because of error", $errors);
			$ok = false;
		}
	}
	
	return $ok;
}

?>

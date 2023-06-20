<?php

// Create a TSV file for a subset of the data by source dataset

$headings = array();

$row_count = 0;

// all
$filename = "0604a7e4-a73f-472a-a4ae-f0f68cf342f7/NameUsage.tsv";

$filter = 1167;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = fgets($file_handle);
		
	$row = explode("\t",trim($line));
	
	$go = is_array($row) && count($row) > 1;
	
	$obj = null;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;	
			
			echo $line;
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
			
			if (isset($obj->{'col:sourceID'}) 
				&& $obj->{'col:sourceID'} == $filter
				)
			{			
				echo $line;
			}
		}
	}	
	$row_count++;	
	
}

?>

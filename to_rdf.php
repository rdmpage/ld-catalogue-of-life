<?php

// Read TSV file and export to RDF

require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/rdf.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

// all
$filename = "0604a7e4-a73f-472a-a4ae-f0f68cf342f7/NameUsage.tsv";

$filename = "zoro.tsv";

$checklist_bank_id = 9893;


$headings = array();

$row_count = 0;

$filter = 0;
$filter = 1167;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	$obj = null;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
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
			
			if ($filter == 0)
			{
				$go = true;
			}
			else
			{
				$go = false;
				if (isset($obj->{'col:sourceID'}) && $obj->{'col:sourceID'} == $filter)
				{
					$go = true;
				}
			}
			
			if ($go)
			{
				// print_r($obj);
				
				$done = false;
				
				$graph = null;
		
				if (!$done)
				{
					// a taxon (node in the tree)
					if (in_array($obj->{'col:status'}, array('accepted', 'provisionally accepted')))
					{
						$done = true;
		
						$url = 'https://www.catalogueoflife.org/data/taxon/' . $obj->{'col:ID'};						
						
						// Construct a graph of the results	
						// Note that we use the URL of the object as the name for the graph. We don't use this 
						// as we are outputting triples, but it enables us to generate fake bnode URIs.	
						$graph = new \EasyRdf\Graph($url);	
						
						$taxon = $graph->resource($url, 'schema:Taxon');

						// types
						$taxon->addResource('schema:additionalType', "http://rs.tdwg.org/dwc/terms/Taxon");
						$taxon->addResource('schema:additionalType', "http://rs.tdwg.org/ontology/voc/TaxonConcept#TaxonConcept");
						
						// scientific name
						$name = create_bnode($graph, true, 'schema:TaxonName');		
						$taxon->addResource('schema:scientificName', $name);
						
						// name
						$namestring = '';		
						if (isset($obj->{'col:scientificName'}))
						{
							$name->add("schema:name", $obj->{'col:scientificName'});
							$namestring = $obj->{'col:scientificName'};
						}
						if (isset($obj->{'col:authorship'}))
						{
							$name->add("schema:author", $obj->{'col:authorship'});
							$namestring .= ' ' . $obj->{'col:authorship'};
						}
				
						$taxon->add('schema:name', $namestring);
						
						if (isset($obj->{'col:rank'}))
						{
							$rank = mb_convert_case($obj->{'col:rank'}, MB_CASE_TITLE);
							$name->add("schema:taxonRank", $rank );

							$taxon->add("schema:taxonRank", $rank );
							$taxon->addResource("schema:taxonRank", "https://api.checklistbank.org/vocab/rank/" . str_replace(' ', '', $rank));
						}
		
						if (isset($obj->{'col:nameReferenceID'}))
						{
							$name->addResource("schema:isBasedOn", "https://api.checklistbank.org/dataset/$checklist_bank_id/reference/" . $obj->{'col:nameReferenceID'});
						}
		
						// identifier
						$identifier = create_bnode($graph, true, 'schema:PropertyValue');
		
						$identifier->add("schema:name", "dwc:taxonID")	;
						$identifier->add("schema:propertyID", "http://rs.tdwg.org/dwc/terms/taxonID");	
						$identifier->add("schema:value", $obj->{'col:ID'});	
		
						$taxon->addResource('schema:identifier', $identifier);	

						$identifier = create_bnode($graph, true, 'schema:PropertyValue');
		
						$identifier->add("schema:name", "col:ID")	;
						$identifier->add("schema:propertyID", "http://catalogueoflife.org/terms/ID");	
						$identifier->add("schema:value", $obj->{'col:ID'});	
		
						$taxon->addResource('schema:identifier', $identifier);	
						
						// parenttaxon
						if (isset($obj->{'col:parentID'}))
						{
							$taxon->addResource('schema:parentTaxon', 'https://www.catalogueoflife.org/data/taxon/' . $obj->{'col:parentID'});	
						}
				
						// reference
						if (isset($obj->{'col:referenceID'}))
						{
							$reference_id = 'https://api.checklistbank.org/dataset/$checklist_bank_id/reference/' . $obj->{'col:referenceID'};
							$work = $graph->resource($reference_id);				
							$work->addResource('schema:about', 'https://www.catalogueoflife.org/data/taxon/' . $obj->{'col:parentID'});	
						}						
						
					}
				}
				
				if (!$done)
				{
					// a synonym, so the taxon is the accepted name (i.e., the parent)
					if (in_array($obj->{'col:status'}, array('synonym', 'ambiguous synonym', 'misapplied')))
					{
						$done = true;
		
						// the taxon is the accepted name (i.e., the parent)
						$url = 'https://www.catalogueoflife.org/data/taxon/' . $obj->{'col:parentID'};
						
						
						// Construct a graph of the results	
						// Note that we use the URL of the object as the name for the graph. We don't use this 
						// as we are outputting triples, but it enables us to generate fake bnode URIs.	
						$graph = new \EasyRdf\Graph($url);	
						
						$taxon = $graph->resource($url, 'schema:Taxon');
						
						// scientific name
						$name = create_bnode($graph, true, 'schema:TaxonName');		
						$taxon->addResource('schema:alternateScientificName', $name);
		
						$namestring = '';
		
						if (isset($obj->{'col:scientificName'}))
						{
							$name->add("schema:name", $obj->{'col:scientificName'});
							$namestring = $obj->{'col:scientificName'};
						}
						if (isset($obj->{'col:authorship'}))
						{
							$name->add("schema:author", $obj->{'col:authorship'});
							$namestring .= ' ' . $obj->{'col:authorship'};
						}
				
						// name is an alternate name of the accepted name
						$taxon->add('schema:alternateName', $namestring);
		
						if (isset($obj->{'col:rank'}))
						{
							$rank = mb_convert_case($obj->{'col:rank'}, MB_CASE_TITLE);
							$name->add("schema:taxonRank", $rank );
						}
		
						if (isset($obj->{'col:nameReferenceID'}))
						{
							$name->addResource("schema:isBasedOn", "https://api.checklistbank.org/dataset/$checklist_bank_id/reference/" . $obj->{'col:nameReferenceID'});
						}
				
						// reference
						if (isset($obj->{'col:referenceID'}))
						{
							$reference_id = 'https://api.checklistbank.org/dataset/$checklist_bank_id/reference/' . $obj->{'col:referenceID'};
							$work = $graph->resource($reference_id);				
							$work->addResource('schema:about', 'https://www.catalogueoflife.org/data/taxon/' . $obj->{'col:parentID'});	
						}
								
				
					}
				}				
			
				if (!$done)
				{
					echo "Unknown status: "	. $obj->{'col:status'} . "\n";
					exit();
				}
				else
				{
	
					// Triples 
					$format = \EasyRdf\Format::getFormat('ntriples');

					$serialiserClass  = $format->getSerialiserClass();
					$serialiser = new $serialiserClass();

					$triples = $serialiser->serialise($graph, 'ntriples');

				
					// Remove JSON-style encoding
					$told = explode("\n", $triples);
					$tnew = array();

					foreach ($told as $s)
					{
						$tnew[] = unescapeString($s);
					}
	
					$triples = join("\n", $tnew);			

					echo $triples . "\n";
	
					// JSON-LD (can we replicate what CoL has?)
					if (0)
					{
						$context = new stdclass;
						$context->{'@vocab'} = 'http://schema.org/';
						$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
						$context->dwc =  "http://rs.tdwg.org/dwc/terms/";
						$context->tc =  "http://rs.tdwg.org/ontology/voc/TaxonConcept#";
						//$context->tn =  "http://rs.tdwg.org/ontology/voc/TaxonName#";

						$additionalType = new stdclass;
						$additionalType->{'@id'} = "additionalType";
						$additionalType->{'@type'} = "@id";
						$additionalType->{'@container'} = "@set";

						$context->{'additionalType'} = $additionalType;
		
						$sameAs = new stdclass;
						$sameAs->{'@id'} = "sameAs";
						$sameAs->{'@type'} = "@id";
						$context->sameAs = $sameAs;			

						// Frame document
						$frame = (object)array(
							'@context' => $context,
							'@type' => 'http://schema.org/Taxon'
						);	

						// Use same libary as EasyRDF but access directly to output ordered list of authors
						$nquads = new NQuads();
						// And parse them again to a JSON-LD document
						$quads = $nquads->parse($triples);		
						$doc = JsonLD::fromRdf($quads);

						$obj = JsonLD::frame($doc, $frame);

						echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
					}					
				}
			}
		}
	}	
	$row_count++;	
	
	//if ($row_count > 5) { exit(); }
	
}

?>


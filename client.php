<?php

// Do a CONSTRUCT on CoL triple store using Oxigraph, so we 
// have to filter out lots of duplicate triples.

require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/rdf.php');
require_once(dirname(__FILE__) . '/utils.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$url = 'http://localhost:7878/query';

$query = 'PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

CONSTRUCT 
{
  	#taxon
  	?taxon rdf:type schema:Taxon .
  
	#name
	?taxon schema:name ?name .

	# rank
	?taxon schema:taxonRank ?taxonRank .

	# synonyms
	?taxon schema:alternateName ?alternateName .
  
 	?taxon schema:alternateScientificName ?alternateScientificName .
    ?alternateScientificName rdf:type schema:TaxonName .
    ?alternateScientificName schema:name ?alternateNameString .
    ?alternateScientificName schema:author ?alternateAuthor .
    ?alternateScientificName schema:isBasedOn ?alternatePub .
    ?alternateScientificName schema:taxonRank ?alternateRank .
  

	# identifier
	?taxon schema:identifier ?identifier .
    ?identifier rdf:type schema:PropertyValue .
	?identifier schema:name ?identifierName .
	?identifier schema:propertyID ?propertyID .
	?identifier schema:value ?identifierValue .

	# scientific name
	?taxon schema:scientificName ?scientificName .
    ?scientificName rdf:type schema:TaxonName .
 	?scientificName schema:name ?scientificNameString .
	?scientificName schema:author ?author .
	?scientificName schema:isBasedOn ?pub .
	?scientificName schema:taxonRank ?rank .
	?pub schema:name ?pubName . 

	# reference
	?reference schema:about ?taxon
}
WHERE 
{
	VALUES ?taxon { <https://www.catalogueoflife.org/data/taxon/8NFYQ> } .
  	?taxon rdf:type ?type .
	?taxon schema:name ?name .

	?taxon schema:taxonRank ?taxonRank .


	?taxon schema:scientificName ?scientificName .
	?scientificName schema:name ?scientificNameString .
	?scientificName schema:author ?author .
	?scientificName schema:taxonRank ?rank .
	OPTIONAL 
	{
		?scientificName schema:isBasedOn ?pub . 
		#?pub schema:name ?pubName . 
	}  
  
  	?taxon schema:alternateName ?alternateName .

  	?taxon schema:alternateScientificName ?alternateScientificName .
    ?alternateScientificName schema:name ?alternateNameString .
    ?alternateScientificName schema:taxonRank ?alternateRank .
    OPTIONAL
    {
    	?alternateScientificName schema:author ?alternateAuthor .
    }  
    OPTIONAL
    {
    	?alternateScientificName schema:isBasedOn ?alternatePub .
    }

	?taxon schema:identifier ?identifier .
	?identifier schema:name ?identifierName .
	?identifier schema:propertyID ?propertyID .
	?identifier schema:value ?identifierValue .


	OPTIONAL 
	{
		?reference schema:about ?taxon
	}


  
} 
';

// Oxigraph will send triples
$response = post($url, $query, 'application/sparql-query');

//echo $response;

$rows = explode("\n", $response);

$rows = array_unique($rows);

$triples = join("\n", $rows);

// echo $triples;

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




?>

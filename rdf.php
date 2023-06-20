<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/vendor/autoload.php');

$cuid = new EndyJasmi\Cuid;

//----------------------------------------------------------------------------------------
function is_uri($string)
{
	$ok = true;
	
	if ($ok)
	{
		$ok = preg_match('/^(https?:\/\/|urn:)/', $string);
	}
	
	if ($ok)
	{
		$ok = !preg_match('/ /', $string);
	}
	
	return $ok;
}

//----------------------------------------------------------------------------------------
// Make a URI play nice with triple store
function nice_uri($uri)
{
	$uri = str_replace('[', urlencode('['), $uri);
	$uri = str_replace(']', urlencode(']'), $uri);
	$uri = str_replace('<', urlencode('<'), $uri);
	$uri = str_replace('>', urlencode('>'), $uri);

	return $uri;
}



//----------------------------------------------------------------------------------------
// From easyrdf/lib/parser/ntriples
function unescapeString($str)
    {
        if (strpos($str, '\\') === false) {
            return $str;
        }

        $mappings = array(
            't' => chr(0x09),
            'b' => chr(0x08),
            'n' => chr(0x0A),
            'r' => chr(0x0D),
            'f' => chr(0x0C),
           // '\"' => chr(0x22),
            '\'' => chr(0x27)
        );
        foreach ($mappings as $in => $out) {
            $str = preg_replace('/\x5c([' . $in . '])/', $out, $str);
        }

        if (stripos($str, '\u') === false) {
            return $str;
        }

        while (preg_match('/\\\(U)([0-9A-F]{8})/', $str, $matches) ||
               preg_match('/\\\(u)([0-9A-F]{4})/', $str, $matches)) {
            $no = hexdec($matches[2]);
            if ($no < 128) {                // 0x80
                $char = chr($no);
            } elseif ($no < 2048) {         // 0x800
                $char = chr(($no >> 6) + 192) .
                        chr(($no & 63) + 128);
            } elseif ($no < 65536) {        // 0x10000
                $char = chr(($no >> 12) + 224) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } elseif ($no < 2097152) {      // 0x200000
                $char = chr(($no >> 18) + 240) .
                        chr((($no >> 12) & 63) + 128) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } else {
                # FIXME: throw an exception instead?
                $char = '';
            }
            $str = str_replace('\\' . $matches[1] . $matches[2], $char, $str);
        }
        return $str;
    }



//----------------------------------------------------------------------------------------
// Create a uniquely labelled b node
// If $skolemise make a URI rather than a _:  b-node
function create_bnode($graph, $skolemise = false, $type = "")
{
	global $cuid;
	$bnode = null;

	$node_id = $cuid->cuid();
	
	if ($skolemise)
	{
		$uri = $graph->getUri() . '#' . $node_id;
	}
	else
	{	
		$uri = '_:' . $node_id; // b-node
	}
	
	if ($type != "")
	{
		$bnode = $graph->resource($uri, $type);
	}
	else
	{
		$bnode = $graph->resource($uri);
	}	
	return $bnode;
}


?>

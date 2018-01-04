<?php
/**
 * xml-string-streamer ParserPipeline parser functor
 * 
 * @package xml-string-streamer
 * @author  Oskar Thornblad <oskar.thornblad@gmail.com>
 */

namespace Prewk\XmlStringStreamer\Parser;

use Exception;
use Prewk\XmlStringStreamer\ParserInterfaceEventDriven;
use Evenement\EventEmitter;

class ParserPipelinesEventDriven extends EventEmitter
{
    protected $parsers = array();
    
    protected $parser_pipelines = array();
    
    protected function __construct()
    {
    }
	
    static public function getInstance()
    {
	       return new self;
    }
    
    public function setParser($parser_name, ParserInterfaceEventDriven $parser)
    {
	$this->parsers[$parser_name] = $parser;
	
	return $this;
    }
    
    public function getParser($parser_name)
    {
	       return isset($this->parsers[$parser_name]) ? $this->parsers[$parser_name] : false;
    }
    
    public function setParserPipeline($event_name, array $parser_names) {	
	$unknown_parsers = array_diff($parser_names, array_keys($this->parsers));
	if (!$unknown_parsers) {
	    $this->parser_pipelines[$event_name] = $parser_names;
	    return $this;
	} else {
	    return $unknown_parsers;
	}
    }
    
    public function apply($chunk)
    {
	$parsers = $this->parsers;
	
	$parser_results = 
	    array_filter(array_map(function ($chain) use ($parsers, $chunk) {
		$nodes = array();
		if (count($chain) == 1) {
		    while($nodes[] = $parsers[current((array)$chain)]->getNodeFrom($chunk));
		    $res = $nodes;
		} else {
		    $res = array_reduce($chain, function($nodes_carry, $parser_index) use ($parsers) {
			$nodes[] = array();
			array_walk($nodes_carry, function($chunk_transformed) use(&$nodes, $parsers, $parser_index){
			    while($nodes[] = $parsers[$parser_index]->getNodeFrom($chunk_transformed ? $chunk_transformed : ''));
			});

			return array_filter($nodes);
		    }, array($chunk));
		}

		return $res;
	    }, $this->parser_pipelines));
	
	return $parser_results;
    }
}

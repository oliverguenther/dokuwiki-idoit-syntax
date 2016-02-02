<?php

include_once(dirname(__FILE__) . join(DIRECTORY_SEPARATOR, array('idoit', 'php', 'apiclient.php')));

use idoit\Api\Client as ApiClient;
use idoit\Api\Request as Request;
use idoit\Api\CMDB\Object as CMDBObject;
use idoit\Api\CMDB\Category;
use idoit\Api\Connection as ApiConnection;

class syntax_plugin_idoit extends DokuWiki_Syntax_Plugin {

	public function getType(){ return 'disabled'; }
	public function getPType() { return 'block'; }
	public function getAllowedTypes() { return array(); }
	public function getSort(){ return 100; }
	public function connectTo($mode) { $this->Lexer->addEntryPattern('<idoitAPI>(?=.*?</idoitAPI>)','base','plugin_idoit'); }
	public function postConnect() { $this->Lexer->addExitPattern('</idoitAPI>','plugin_idoit'); }


	function getInfo(){
	  return array(
		'author' => 'Oliver Günther',
		'email'  => 'mail@oliverguenther.de',
		'date'   => '2014-11-19',
		'name'   => 'i-doit API client plugin',
		'desc'   => 'Call i-doit API with JSON requests directly from DokuWiki',
		'url'    => 'https://github.com/oliverguenther/dokuwiki-idoit-syntax'
	  );
	}


	/**
	* Filter results with a given set of arrays, each containing
	* one hierarchy element to access.
	*/
	function filterResults($request, $response) {
		$results = array();

		foreach ($request['filter'] as $filter) {
			// Every filter is an array of items
			$obj = $response;

			$path = $filter['path'];
			$name = $filter['desc'];
			foreach ($path as $elem) {
				if (array_key_exists($elem, $obj)) {
					// descend
					$obj = $obj[$elem];
				} else {
					// continue with next filter
					$results[$name] = "Filter '$name' (path " . join('/', $path) . ") does not match response";
					continue 2;
				}
			}
			$results[$name] = $obj;
		}

		return $results;
	}


	/**
	* Execute the request on the JSON RPC apai
	* and process the results.
	*/
	function callAPI($request) {
		try {
			
			// Init connection to api endpoint
			$api_conn = new ApiClient(new ApiConnection(
				$this->getConf('api_endpoint'),
				$this->getConf('api_key'),
				$this->getConf('api_user'),
				$this->getConf('api_pass')
			));

			$apiRequest = new Request($api_conn, $request['method'], $request['params']);
			$response = $apiRequest->send();

			if ($request['filter']) {
				return $this->filterResults($request, $response);
			} else {
				return $response;
			}

	

		} catch (Exception $e) {
			return "API error: " . $e->getMessage();
		}
	}

	/**
	* Parse the JSON request from the syntax environment
	* and call the API with it.
	*
	* Returns a string error for JSON decode errors.
	*/
	function decodeAndRunQuery($request) {

		$json = json_decode(trim($request), 1);

		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return $this->callAPI($json);
			case JSON_ERROR_DEPTH:
				return 'JSON decode error: Maximum stack depth exceeded';
			case JSON_ERROR_STATE_MISMATCH:
				return 'JSON decode error: Underflow or the modes mismatch';
			case JSON_ERROR_CTRL_CHAR:
				return 'JSON decode error: Unexpected control character found';
			case JSON_ERROR_SYNTAX:
				return 'JSON decode error: Syntax error, malformed JSON';
			case JSON_ERROR_UTF8:
				return 'JSON decode error: Malformed UTF-8 characters, possibly incorrectly encoded';
			default:
				return 'JSON decode error: unknown';
		}
	}

	
	public function handle($match, $state, $pos, Doku_Handler $handler){
		switch ($state) {
			case DOKU_LEXER_EXIT :
			case DOKU_LEXER_ENTER :
				break;
		  
			case DOKU_LEXER_UNMATCHED :
				$result = $this->decodeAndRunQuery($match);
				return array($state, $result);
		}

		return array($state, null);
	}
 
	public function render($mode, Doku_Renderer $renderer, $data) {
		if ($mode == 'xhtml') {
			list($state, $match) = $data;
			switch ($state) {
				case DOKU_LEXER_ENTER :
					$renderer->doc .= "<pre>";
					break;
 
				case DOKU_LEXER_UNMATCHED :
					if (is_array($match)) {
						foreach ($match as $k => $v) {
							if (!is_array($v)) {
								// Print literal
								$renderer->doc .= str_pad($k, 30) . "$v\n";
							} else {
								// Print complex as print_r
								$renderer->doc .= "--- $k ---\n";
								$renderer->doc .= print_r($v, true);
								$renderer->doc .= "end $k end\n";
							}
						}
					} else {
						$renderer->doc .= print_r($match, true);
					}

					break;

				case DOKU_LEXER_EXIT :
					$renderer->doc .= "</pre>";
					break;
			}
			return true;
		}
		return false;
	}
}

# i-doit DokuWiki Syntax Plugin

This plugin provides a basic integration of the [i-doit JSON-RPC API client in PHP](https://bitbucket.org/dstuecken/i-doit-api-clients/wiki/PHP) with DokuWiki.

## Installation

### Manually

Simply clone the repo to `lib/plugins` of your Dokuwiki installation root within a directory named `idoit`. The naming is important, otherwise the plugin will not be loaded.

	git clone https://github.com/oliverguenther/dokuwiki-idoit-syntax <path to dokuwiki>/lib/plugins/idoit/
	
The PHP client API must be initialized for your i-doit installation. Run the following command ([More Information](https://bitbucket.org/dstuecken/i-doit-api-clients/wiki/PHP)).
	
	cd <path to dokuwiki>/lib/plugins/idoit/php && make initialize

## Usage

The plugin adds a protected syntax block `<idoitAPI></idoitAPI>` which contains a JSON request passed to the API.
	
	<idoitAPI>
	{
	  "method": "cmdb.object.read",
	  "params": { "id": 1234 },
	  "filter": [
	    { "desc": "SYS-ID", "path": [ "sysid" ] },
	    { "desc": "Foo", "path": [ "foo", "bar" ] }
	  ]
	}
	</idoitAPI>

The JSON request must contain the following attributes:

 * **method**: The method string as defined in `CMDB/Methods.class.php` of the PHP API
 * **request** The actual JSON request as sent to the API.
 
 And may contain an optional `filter` attribute for filtering the result object as an array of the following objects:
 
 * **path**: An array that denotes the accessor hierarchy in the response object.

	A filter `{ "desc": "My Hostname", "path": [0, "hostaddress", "ref_title"] }`
	would return the line `'My Hostname			myhostname'` for the following API response:
	
		[{
			"hostaddress": {
				id: 128,
				type: "C__OBJTYPE__LAYER3_NET",
				title: "Management",
				ref_title: "myhostname"
			}
		},
		...
		]
 
 
 * **desc**: The name to be used for printing the retrieved value


For available methods and categories as strings, see the constant definitions in [https://bitbucket.org/dstuecken/i-doit-api-clients](https://bitbucket.org/dstuecken/i-doit-api-clients).


## Examples


### Retrieving objects by IDs

	<idoitAPI>
	{
	  "method": "cmdb.object.read",
	  "params": { "id": 578 }
	}
	</idoitAPI>
	
**Output**

	id                            578
	title                         NOS
	sysid                         SYSID_1404992452
	objecttype                    5
	type_title                    Server
	....
	updated                       2014-10-17 11:53:54


#### Retrieve single attribute pairs


	<idoitAPI>
	{
	  "method": "cmdb.object.read",
	  "params": { "id": 578 },
	  "filter": [
	    { "desc": "SYS-ID", "path": [ "sysid" ] },
	    { "desc": "Foo", "path": [ "foo", "bar" ] }
	  ]
	}
	</idoitAPI>

If a filter does not match the response, a warning is instead printed:

	SYS-ID                        SYSID_1404992452
	Foo                           Filter 'Foo' (path foo/bar) does not match response
	
#### Retrieve, Filter values from categories

	<idoitAPI>
	{
	  "method": "cmdb.category.read",
	  "params": { "objID": 578, "catgID": "C__CATG__IP" },
	  "filter": [
	    { "desc": "Hostname", "path": [ "1", "hostname" ] },
	    { "desc": "IPv4", "path": [ "1", "hostaddress", "ref_title" ] }
	  ]
	}
	</idoitAPI>

	Hostname                      myhostname.example.com
	IPv4                          123.456.789.0



## Copyrights & License
The i-doit DokuWiki syntax plugin is completely free and open source and released under the [MIT License](https://github.com/oliverguenther/dokuwiki-idoit-syntax/blob/master/LICENSE).

Copyright (c) 2014 Oliver Günther (mail@oliverguenther.de)

This plugin makes use of the [i-doit JSON-RPC API client in PHP](https://bitbucket.org/dstuecken/i-doit-api-clients/wiki/PHP), Copyright (c) 2014 Dennis Stücken

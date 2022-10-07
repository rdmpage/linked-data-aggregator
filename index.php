<?php

// Simple tests

error_reporting(E_ALL);

// for dev environment we do the job of .htaccess 
if(preg_match('/^\/gql.php/', $_SERVER["REQUEST_URI"])) return false;

if(preg_match('/^\/css/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/fonts/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/js/', $_SERVER["REQUEST_URI"])) return false;

//if(preg_match('/^\/images/', $_SERVER["REQUEST_URI"])) return false;

if(preg_match('/^\/sparql.html/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/sparql_proxy.php/', $_SERVER["REQUEST_URI"])) return false;


?>

<html>
<head>
	<style>
	body {
		padding:2em;
		font-family:sans-serif;
	}
	
	li {
		padding:0.1em;
		font-size: 0.8em;
	}
	
	details {
		border:1px solid rgb(128,128,128);
		margin-bottom: 1em;
		background:rgba(192,192,192,0.5);
		border-radius:4px;
	}
	
	summary {
	    padding:0.5em;
		outline-style: none; 
		background:rgb(128,128,128);
		color:white;
		border-radius:4px;
	}	
	
	
	input {
		font-size:1em;
	}
	
	button {
		font-size:1em;
	}
	
	a {
		text-decoration: none;
	}
	
	a:hover {
		text-decoration: underline;
	}	
	
	span.doi {
		text-decoration: underline;
		text-transform: lowercase;
		font-size:12px;		
	}
	
	span.doi a {
		color:black;
	}
	
	span.doi:before {
		content: "doi:";		
	}
	
	span.lsid {
		text-decoration: underline;
		text-transform: lowercase;
		font-size:12px;		
	}
	
	span.lsid a {
		color:black;
	}		
	
	.description {
		font-size: 0.8em;
		color:rgb(64,64,64);
		border: 1px solid  rgb(192,192,192);
		padding:0.5em;
		line-height:1.4em;
		border-radius:4px;
	}
	
	.figures {
		/*background: rgb(224,224,224);*/
		display: block;
		overflow: auto;
	}
	
	.figure {
		background: white;
		margin: 0.2em;
		padding: 1em;	
		border: 1px solid rgb(192,192,192);
		text-align: justify;
	}
	
	/* heavily based on https://css-tricks.com/adaptive-photo-layout-with-flexbox/ */
	.gallery ul {
	  display: flex;
	  flex-wrap: wrap;
	  
	  list-style:none;
	  padding-left:2px;
	}

	.gallery li {
	  height: 80px;
	  flex-grow: 1;
  
	}

	.gallery li:last-child {
	  flex-grow: 10;
	}

	.gallery img {
	  max-height: 90%;
	  min-width: 90%;
	  object-fit: cover;
	  vertical-align: bottom;
	  
	  border:1px solid rgb(192,192,192);
	}	
	
	</style>
	
	<link rel="stylesheet" href="css/academicons.min.css"/>
	
	<!-- jquery -->
    <script src="js/jquery-1.11.2.min.js" type="text/javascript"></script>
    
    <script>
        //--------------------------------------------------------------------------------
		// http://stackoverflow.com/a/11407464
		$(document).keypress(function(event){

			var keycode = (event.keyCode ? event.keyCode : event.which);
			if(keycode == '13'){
				$('#go').click();   
			}

		});    
	
        //--------------------------------------------------------------------------------
		//http://stackoverflow.com/a/25359264
		$.urlParam = function(name){
			var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
			if (results==null){
			   return null;
			}
			else{
			   return results[1] || 0;
			}
		}    
    </script>

</head>
<body>
	<h1>Linked Data Aggregator Browser</h1>
	
	<div><a href="sparql.html" target="_new">SPARQL</a></div>
	<hr />
	
	<div>
		<input type="text" id="id" value="" size="60" placeholder="https://doi.org/10.5852/ejt.2020.629">
		<button id="go" onclick="go();">Go</button>
	</div>
	
	<p>
	A demo of a simple <a href="gql.php">GraphQL API endpoint</a> for a triple store.</a>
	</p>
	
	
	
	<!--
	<h3>Examples</h3>
	<div>		
		<li><a href="./?id=https://doi.org/10.5852/ejt.2020.629">Revision of the aperturally dentate Charopidae (Gastropoda: Stylommatophora) of southern Africa - genus Afrodonta s. lat., with description of five new genera, twelve new species and one new subspecies</a></li>
		<li><a href="./?id=https://doi.org/10.5281/zenodo.3762282">Fig. 1</a></li>
		<li><a href="./?id=https://www.catalogueoflife.org/data/taxon/7NN8R">Afrodonta Melvill & Ponsonby, 1908</a></li>
	</div>

	<hr/>
	-->

	<div id="output"></div>
	
	
	<script>
        //--------------------------------------------------------------------------------
		function go () {
		
			var id = $('#id').val();		
		
			
			if (id.match(/^(https?|urn):/)) {
				// it's a thing
				//history.pushState(null, null, '?id=' + id);

				thing(id);
			} else {
				// search
				history.pushState(null, null, '?q=' + id);

				search(id);
			}
			
		}
		
        //--------------------------------------------------------------------------------
        // Convert array of title objects into a concatenated string to display
		function get_title (value) {
		
			var titles = [];
			
			for (var i in value) {
				titles.push(value[i].title);
			}
				
			return titles.join(' / ');
		}		
		
        //--------------------------------------------------------------------------------
		function thing (id) {
	
			var data = {};
			data.query = `query{
	  thing(id: "` + id + `"){
		id
		name
		type
	  }
	}`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				if (response.data.thing.type) {
					var have_type = false;
					
					// 
					if (!have_type && response.data.thing.type.indexOf('ImageObject') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						image(id);
					}
					

					if (!have_type && response.data.thing.type.indexOf('ScholarlyArticle') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						work(id);
					}
					
					
					if (!have_type && response.data.thing.type.indexOf('CreativeWork') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						work(id);
					}

					
					if (!have_type && response.data.thing.type.indexOf('TaxonName') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						taxon_name(id);
					}					

					if (!have_type && response.data.thing.type.indexOf('Taxon') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						taxon(id);
					}

					if (!have_type && response.data.thing.type.indexOf('Organization') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						organisation(id);
					}
					
					
					/*
					if (!have_type && response.data.thing.type.indexOf('Periodical') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						container(id);
					}	
					*/				

					if (!have_type && response.data.thing.type.indexOf('Person') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						person(id);
					}
					

					if (!have_type && response.data.thing.type.indexOf('Sample') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						specimen(id);
					}
					
					
					// types we will need to add:
					/// https://doi.org/10.3897/dez.65.21000.suppl1 is <http://schema.org/Dataset>
					
					if (!have_type) {
						alert("Unknown type |" + response.data.thing.type + '|');					
					}
				} else {
					$("#output").html('<span style="background:orange;">Nothing found for "' + id + '"' + '</span>');
				
				}
			}

		);
	
	}
	
	
        //--------------------------------------------------------------------------------
		function search (id) {
	
			var data = {};
			data.query = `query{
	  search(query: "` + id + `"){
	  results {
		id
        name
        type
        score
        thumbnailUrl
        identifier
      }
	  }
	}`;

		data.variables = {};
		
		$("#output").html("<progress></progress>");
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				if (response.data.search.results) {
				   var html = '';
				   
				   html += '<h2>' + response.data.search.results.length + ' result(s) for "' + id + '"' + '</h2>';
				   
				   for (var i in response.data.search.results) {
				   	html += '<div style="padding:0.2em;display:block;overflow:auto;">';
				   	
				   	html += '<div style="padding:4px;float:left;width:40px;height:40px;">';				   	
					if (response.data.search.results[i].thumbnailUrl) {
						html += '<img style="object-fit:cover;width:40px;height:40px;" src="https://aezjkodskr.cloudimg.io/' + response.data.search.results[i].thumbnailUrl + '?height=40">';
					}			   	
			   	    html += '</div>';
				   	
					// not verything in list may have a URI
					if (response.data.search.results[i].id.match(/^(http|urn)/)) {
						html += '<a href="./?id=' + response.data.search.results[i].id + '">';
					}
					
					if (response.data.search.results[i].name) {
						html += response.data.search.results[i].name.join(' / ');
					}
				   	
					if (response.data.search.results[i].id.match(/^(http|urn)/)) {
						html += '</a>';
					}
					
					// identifiers?
					if (response.data.search.results[i].identifier) {
						html += '<ul>';
						for (var j in response.data.search.results[i].identifier) {
							html += '<li>';
							html += response.data.search.results[i].identifier[j];
							html += '</li>'
						}						
						html += '</ul>';
					}					
				   
				    html += '</div>';
				   }
				   $("#output").html(html);
				} else {
					$("#output").html('<span style="background:orange;">Nothing found for "' + id + '"' + '</span>');
				
				}
			}

		);
	
	}	
        //--------------------------------------------------------------------------------
		function taxon_name (id) {
	
			var data = {};
			data.query = `query{
  taxonName(id: "` + id + `") {
    id
    name
    rankString
    url
    
    alternateName {
      id
      name
    }
    
    isBasedOn {
      id
      titles {
        title
      }
      doi
    }    
    
    subjectOf {
      id
      titles {
        title
      }
      doi
    }        
  }
}`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				if (response.data.taxonName.name) {
					html += '<h2>' + response.data.taxonName.name + '</h2>';				
				}
				
				// id
				
				// LSID
				
				if (response.data.taxonName.id.match(/^urn:lsid/)) {
					html += '<span class="lsid">' + '<a href="https://lsid.herokuapp.com/' + response.data.taxonName.id + '/jsonld" target="_new">' + response.data.taxonName.id + '</a></span><br/>';				
				}

				// URL
				if (response.data.taxonName.url) {
					html += '<span class="url">' + '<a href="' + response.data.taxonName.url + '" target="_new">' + response.data.taxonName.url+ '</a></span><br/>';				
				}
				
				html += '<h3>Details</h3>';
				
				// other names
				if (response.data.taxonName.alternateName && response.data.taxonName.alternateName.length > 0) {
					html += '<details open>';				
					html += '<summary>Other names (' + response.data.taxonName.alternateName.length + ')</summary>';
					html += name_list_to_html(response.data.taxonName.alternateName);
					html += '</details>';
				}

				// work(s) is Based On
				if (response.data.taxonName.isBasedOn && response.data.taxonName.isBasedOn.length > 0) {
					html += '<details open>';				
					html += '<summary>Based on (' + response.data.taxonName.isBasedOn.length + ')</summary>';
					html += work_list_to_html(response.data.taxonName.isBasedOn);
					html += '</details>';
				}

				// bibliography
				if (response.data.taxonName.subjectOf && response.data.taxonName.subjectOf.length > 0) {
					html += '<details open>';				
					html += '<summary>Bibliography (' + response.data.taxonName.subjectOf.length + ')</summary>';
					html += work_list_to_html(response.data.taxonName.subjectOf);
					html += '</details>';
				}
				
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}				
		
        //--------------------------------------------------------------------------------
		function taxon (id) {
	
			var data = {};
			data.query = `query{
  taxon(id: "` + id + `") {
    id
    name
    taxonRank

    scientificName {
      #id
      name
      author
      isBasedOn {
        #id
        titles {
          title
        }
        datePublished
        doi
        #url
      }
    }
    
    alternateScientificName {
    	name
    	author
    }
    
   parentTaxon {
      id
      name
    }
    
   childTaxon {
      id
      name
    }    

    references {
      id
      titles {
        title
      }
      datePublished
      doi
      #url
      sameAs
    }
  }
}`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				html += '<h2>' + response.data.taxon.name + '</h2>';
				
				// synonyms
				if (response.data.taxon.alternateScientificName) {
					html += '<h3>Synonyms</h3>';
					html += '<ul>';					
					for (var j in response.data.taxon.alternateScientificName) {		
						html += '<li>';	
						if (response.data.taxon.alternateScientificName[j].name) {
							html += response.data.taxon.alternateScientificName[j].name;							
						}
						if (response.data.taxon.alternateScientificName[j].author) {
							html += ' ' + response.data.taxon.alternateScientificName[j].author;							
						}											
						html += '</li>';						
					}
					html += '</ul>';
				}
				
				
				// classification
				
				// parent
				if (response.data.taxon.parentTaxon) {
					html += '<p>';
					html += '<a href="./?id=' + response.data.taxon.parentTaxon.id + '">' + response.data.taxon.parentTaxon.name + '</a>';						
				}
				
				html += '<ul>';					
				html += '<li>';
				html += response.data.taxon.name + '</a>';
				
				// childen	
				if (response.data.taxon.childTaxon) {
					html += '<ul>';					
					for (var j in response.data.taxon.childTaxon) {		
						html += '<li>';						
						html += '<a href="./?id=' + response.data.taxon.childTaxon[j].id + '">' + response.data.taxon.childTaxon[j].name + '</a>';	
						html += '</li>';						
					}
					html += '</ul>';
				}
				html += '</li>';
				html += '</ul>';
				if (response.data.taxon.parentTaxon) {
					html += '</p>';
				}
							
				
				
				// list of references
				if (response.data.taxon.references) {
					html += '<h3>References</h3>';
					html += work_list_to_html(response.data.taxon.references);
				}
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}		
	
	
        //--------------------------------------------------------------------------------
	function work_list_to_html(works) {
		var html = '';
		html += '<ul>';
		for (var i in works) {
			html += '<li style="display:block;overflow:auto;">';
			
			if (works[i].thumbnailUrl) {
				html += '<img style="border:1px solid rgb(192,192,192);float:left;height:32px;width:32px;object-fit:cover;"  src="https://aezjkodskr.cloudimg.io/' + works[i].thumbnailUrl + '?height=32">';
			} else {
				html += '<div style="border:1px solid rgb(192,192,192);float:left;height:32px;width:32px;"></div>';
			}
			
			html += '<div style="margin-left:34px;">';
			
			// not verything in list may have a URI
			if (works[i].id.match(/^(http|urn)/)) {
				html += '<a href="./?id=' + works[i].id + '">';
			}
			
			if (works[i].titles) {
				html += get_title(works[i].titles);
			} else {
				html += '[No title]';
			}
			
			if (works[i].id.match(/^(http|urn)/)) {
				html += '</a>';
			}			
			
			// DOI?
			if(works[i].doi) {
				//html += '&nbsp;<span class="doi"><a href="https://doi.org/' + works[i].doi + '" target="_new">' + works[i].doi + '</a></span>';
				html += '&nbsp;<i class="ai ai-doi"></i><a href="https://doi.org/' + works[i].doi + '" target="_new">' + works[i].doi + '</a>';
			}
			
			// Other versions?
			if(works[i].sameAs) {
				html += '&nbsp;<span style="font-size:0.8em">Other versions:';
				for (var j in works[i].sameAs) {								
					html += ' <a href="./?id=' + works[i].sameAs[j] + '">' + works[i].sameAs[j] + '</a>';	
				}
				html += '</span>';
			}
			
			html += '</div>';
			
			html += '</li>';
		}			
		html += '</ul>';				
		
		return html;
	}
	
        //--------------------------------------------------------------------------------
	function name_list_to_html(list) {
		var html = '';
		html += '<ul>';
		for (var i in list) {
			html += '<li>';
			
			// not verything in list may have a URI
			if (list[i].id.match(/^(http|urn)/)) {
				html += '<a href="./?id=' + list[i].id + '">';
			}
			
			html += list[i].name.join(' / ');
			
			if (list[i].id.match(/^(http|urn)/)) {
				html += '</a>';
			}			
			
			if (list[i].id.match(/^urn:lsid/)) {
				html += '&nbsp;<span class="lsid">' + '<a href="https://lsid.herokuapp.com/' + list[i].id + '/jsonld" target="_new">' + list[i].id + '</a></span><br/>';				
			}
			
			
			html += '</li>';
		}			
		html += '</ul>';				
		
		return html;
	}
	
        //--------------------------------------------------------------------------------
	function person_list_to_html(list) {
		var html = '';
		html += '<ul>';
		for (var i in list) {
			html += '<li>';
			
			// ORCID?
			if (list[i].orcid) {
				//html +='<img src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png">&nbsp;';
				html += '<i class="ai ai-orcid"></i>';
			}
			
			// not verything in list may have a URI
			if (list[i].id.match(/^(http|urn)/)) {
				html += '<a href="./?id=' + list[i].id + '">';
			}
			
			html += list[i].name;
			
			if (list[i].id.match(/^(http|urn)/)) {
				html += '</a>';
			}						
			
			html += '</li>';
		}			
		html += '</ul>';				
		
		return html;
	}
	
       //--------------------------------------------------------------------------------
	function thing_list_to_html(list) {
		var html = '';
		html += '<ul>';
		for (var i in list) {
			html += '<li>';
			
			// not verything in list may have a URI
			if (list[i].id.match(/^(http|urn)/)) {
				html += '<a href="./?id=' + list[i].id + '">';
			}
			
			html += list[i].name;
			
			if (list[i].id.match(/^(http|urn)/)) {
				html += '</a>';
			}						
			
			html += '</li>';
		}			
		html += '</ul>';				
		
		return html;
	}	
	
	
        //--------------------------------------------------------------------------------
		function image_gallery(images) {
			var html = '';
			html += '<div class="gallery">';
			html += '<ul>';					
			for (var i in images) {
				if (images[i].thumbnailUrl) {
					html += '<li>'
					html += '<a href="./?id=' + images[i].id + '">';
					html += '<img src="https://aezjkodskr.cloudimg.io/' + images[i].thumbnailUrl + '?height=80">';
					html += '</a>';
					html += '</li>'
				}
			}
			html += '<li></li>';
			html += '</ul>';
			html += '</div>';

			return html;
		}
	
		
        //--------------------------------------------------------------------------------
		function person (id) {
	
			var data = {};
			data.query = `query{
	  person(id: "` + id + `"){
		id
		orcid
		researchgate
		wikidata
		
		thumbnailUrl
		
		mainEntityOfPage
		
		name
		givenName
		familyName
		
		alternateName
		
	   affiliation {
		  id
		  name
		}	
		
		works {
		  id
		  titles {
		  	title
		  }
		  doi
		  thumbnailUrl
		}
		
    scientificNames {
      id
      name
    }		
    
    images {
    	id
    	thumbnailUrl    
    }
    
#  identified {
#     id
#     name
#   }
#   
#   recorded {
#     id
#     name
#   }        
#		
	  }
	}`;



		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				//html += '<h2>' + response.data.person.name[0] + '</h2>';
				html += '<h2>' + response.data.person.name + '</h2>';
				
				// Image?
				if (response.data.person.thumbnailUrl) {
					html += '<div style="padding:4px;width:80px;height:80px;">';		
					html += '<img style="object-fit:cover;width:80px;height:80px;" src="https://aezjkodskr.cloudimg.io/' + response.data.person.thumbnailUrl + '?height=80">';
					html += '</div>';
				}		
				
				
								
				// ORCID?
				if (response.data.person.orcid) {
					//html += '<div><img src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png">';
					html += '<i class="ai ai-orcid"></i>';
					html += '&nbsp;<a href="https://orcid.org/' + response.data.person.orcid + '" target="_new">';
					html += 'https://orcid.org/' + response.data.person.orcid;
					html += '</a>';
					html += '</div>';
				}
				
				
				// other names
				if (response.data.person.alternateName) {
					html += '<div>';
					for (var i in response.data.person.alternateName) {
						html += response.data.person.alternateName[i] + ' ';
					}
					html += '</div>';
				}	
				
				// Web pages
				if(response.data.person.mainEntityOfPage) {
					html += '<br><span style="font-size:0.8em">Web pages:';
					for (var j in response.data.person.mainEntityOfPage) {								
						html += ' <a href="' + response.data.person.mainEntityOfPage[j] + '" target="_new">' + response.data.person.mainEntityOfPage[j] + '</a>';	
					}
					html += '</span>';
				}
				
				// Identifiers
				if (response.data.person.researchgate) {
					html += '<div>';
					html += '<i class="ai ai-researchgate"></i>';
					html += '<a href="https://www.researchgate.net/profile/' + response.data.person.researchgate + '" target="_new">' + response.data.person.researchgate + '</a>';	
					html += '</div>';
				}	

				if (response.data.person.wikidata) {
					html += '<div>';
					html += '<a href="http://www.wikidata.org/entity/' + response.data.person.wikidata + '" target="_new">' + response.data.person.wikidata + '</a>';	
					html += '</div>';
				}				
				
				
				html += '<h3>Activities</h3>';
				
				// affiliation	
				if (response.data.person.affiliation) {
					html += '<details>';				
					html += '<summary>Employment (' + response.data.person.affiliation.length + ')</summary>';
					
					html += '<ul>';
					for (var i in response.data.person.affiliation) {
						html += '<li>';
						html += response.data.person.affiliation[i].name[0]; // hack
						html += '</li>';
					}
					html += '</ul>';
					html += '</details>';
				}
				
				// works
				if (response.data.person.works) {
					html += '<details>';				
					html += '<summary>Works (' + response.data.person.works.length + ')</summary>';
					html += work_list_to_html(response.data.person.works);
					html += '</details>';
				}
				
				
				// scientific names 
				if (response.data.person.scientificNames) {
					html += '<details>';				
					html += '<summary>Taxon names (' + response.data.person.scientificNames.length + ')</summary>';
					html += name_list_to_html(response.data.person.scientificNames);
					html += '</details>';
				}
				
				
				// figures				
				if (response.data.person.images) {
					html += '<details>';				
					html += '<summary>Images (' + response.data.person.images.length + ')</summary>';					
					html += image_gallery(response.data.person.images);					
					html += '</details>';
				}
				
				// identified
				if (response.data.person.identified) {
					html += '<details>';				
					html += '<summary>Identified (' + response.data.person.identified.length + ')</summary>';
					html += thing_list_to_html(response.data.person.identified);
					html += '</details>';
				}
				
				// recorded
				if (response.data.person.recorded) {
					html += '<details>';				
					html += '<summary>Recorded (' + response.data.person.recorded.length + ')</summary>';
					html += thing_list_to_html(response.data.person.recorded);
					html += '</details>';
				}
				
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}
	
        //--------------------------------------------------------------------------------
		function work (id) {
	
			var data = {};
			data.query = `query{
	  work(id: "` + id + `"){
    id
    doi
    
    mainEntityOfPage
    
    sameAs
    
    #isbn
    #identifier
    
    titles {
    	title
    }
    
    author {
      id
      name
      orcid
    }
    
    description

    #container {
    #  id
    #  issn
    #	titles {
    #		title
    #	}
    #}

    volumeNumber
    issueNumber
    pagination

    datePublished
    
    
	figures {
	  id
      thumbnailUrl
      contentUrl
      caption
    }    
    
    about {
    	id
    	name
    }
    
    scientificNames {
      id
      name
    }      
 
    cites {
      id
      doi
    	titles {
    		title
    	}
    	thumbnailUrl 
    }

    cited_by {
      id
      doi
    	titles {
    		title
    	}
    	thumbnailUrl
    }

    #related {
    #  id
    #  doi
    #	titles {
    #		title
    #	}
    #}
  }
}
`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				// alert(JSON.stringify(response, null, 2));
				var html = '';
				
				html += '<h2>';
				
				html += get_title(response.data.work.titles);
				
				html += '</h2>';
				
				// authors
				if (response.data.work.author) {
					html += '<div>';
					
					for (var i in response.data.work.author) {
						html += '<div style="display:inline;padding-right:1em;">';
						
						if (response.data.work.author[i].orcid) {
							//html += '<img src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png">&nbsp;';
							html += '<i class="ai ai-orcid"></i>';
						}
					
						// thing or string?
						if (response.data.work.author[i].id.match(/^(https?|urn)/)) {
							html += '<a href="index.html?id=';
							html += response.data.work.author[i].id;
							html += '">';							
						}
						
						//html += response.data.work.author[i].name[0];
						html += response.data.work.author[i].name;
						
						if (response.data.work.author[i].id.match(/^(https?|urn)/)) {
							html += '</a>';
						}
						
						html += '</div>';
					
					}

					html += '</div>';

				}
				
				// description							
				if (response.data.work.description) {
					html += '<div class="description">' + response.data.work.description.join('<br/>') + '</div>';					
				}
				
				
				// to do
				if (response.data.work.container) {
					if (response.data.work.container.id) {
						html += '<div style="padding-top:1em;">';
						html += 'Published in ';
						
						html += '<a href="index.html?id=' + response.data.work.container.id + '">' 
							+ get_title(response.data.work.container.titles) 
							+ '</a>';

						html += '</div>';
					}
				
				
				}
				
				// DOI							
				if (response.data.work.doi) {
					//html += '<br><span class="doi"><a href="https://doi.org/' + response.data.work.doi + '" target="_new">' + response.data.work.doi + '</a></span>';					
					html += '<i class="ai ai-doi"></i><a href="https://doi.org/' + response.data.work.doi + '" target="_new">' + response.data.work.doi + '</a>';
				}

				// Other versions?
				if(response.data.work.sameAs) {
					html += '<br><span style="font-size:0.8em">Other versions:';
					for (var j in response.data.work.sameAs) {								
						html += ' <a href="./?id=' + response.data.work.sameAs[j] + '">' + response.data.work.sameAs[j] + '</a>';	
					}
					html += '</span>';
				}
				
				// Web pages
				if(response.data.work.mainEntityOfPage) {
					html += '<br><span style="font-size:0.8em">Web pages:';
					for (var j in response.data.work.mainEntityOfPage) {								
						html += ' <a href="' + response.data.work.mainEntityOfPage[j] + '" target="_new">' + response.data.work.mainEntityOfPage[j] + '</a>';	
					}
					html += '</span>';
				}
				

				// figures				
				if (response.data.work.figures) {
					html += '<h3>Figures</h3>';
					html += image_gallery(response.data.work.figures);					
				}
				
				html += '<h3>Links</h3>'
				
				// literature cited
				if (response.data.work.cites) {
					html += '<details>';				
					html += '<summary>Cites (' + response.data.work.cites.length + ')</summary>';
					html += work_list_to_html(response.data.work.cites);
					html += '</details>';
				}

				// citing
				if (response.data.work.cited_by) {
					html += '<details>';				
					html += '<summary>Cited by (' + response.data.work.cited_by.length + ')</summary>';
					html += work_list_to_html(response.data.work.cited_by);
					html += '</details>';
				}
				
				
				
				// what is work about?				
				if (response.data.work.about) {
					html += '<h3>Work is about</h3>';
					html += '<ul>';
					for (var i in response.data.work.about) {
						html += '<li>';
						html += '<a href="./?id=' + response.data.work.about[i].id + '">';
						html += response.data.work.about[i].name;
						html += '</a>';
						html += '</li>';
					}
					html += '</ul>';
				}
				
				
				// scientific names 
				if (response.data.work.scientificNames) {
					html += '<details>';				
					html += '<summary>Taxon names (' + response.data.work.scientificNames.length + ')</summary>';
					html += name_list_to_html(response.data.work.scientificNames);
					html += '</details>';
				}
				
				
				
				/*
				html += '<h3>Citation graph</h3>';
				
				html += '<details>';				
				html += '<summary>Cites</summary>';
				html += '<ul>';
				for (var i in response.data.work.cites) {
					if (response.data.work.cites[i].titles) {
						html += '<li>';
						html += '<a href="index.html?id=' + response.data.work.cites[i].id + '">' 
							+ get_title(response.data.work.cites[i].titles) 
							+ '</a>';

				
						if(response.data.work.cites[i].doi) {
							html += '<br><span class="doi"><a href="https://doi.org/' + response.data.work.cites[i].doi + '" target="_new">' + response.data.work.cites[i].doi + '</a></span>';
						}
						html += '</li>';
					}
				}
				html += '</ul>';
				html += '</details>';	
				
				html += '<details>';		
				html += '<summary>Cited by</summary>';
				html += '<ul>';
				for (var i in response.data.work.cited_by) {
					if (response.data.work.cited_by[i].titles) {
						html += '<li>';
						html += '<a href="index.html?id=' + response.data.work.cited_by[i].id + '">'
						 + get_title(response.data.work.cited_by[i].titles)
						 + '</a>';
				
						if(response.data.work.cited_by[i].doi) {
							html += '<br><span class="doi"><a href="https://doi.org/' + response.data.work.cited_by[i].doi + '" target="_new">' + response.data.work.cited_by[i].doi + '</a></span>';
						}
						html += '</li>';
					}
				}
				html += '</ul>';
				html += '</details>';	

				html += '<details>';		
				html += '<summary>Related work</summary>';
				html += '<ul>';
				for (var i in response.data.work.related) {
					if (response.data.work.related[i].titles) {
						html += '<li>';
						html += '<a href="index.html?id=' + response.data.work.related[i].id + '">'
						 + get_title(response.data.work.related[i].titles)
						 + '</a>';
						
				
						if(response.data.work.related[i].doi) {
							html += '<br><span class="doi"><a href="https://doi.org/' + response.data.work.related[i].doi + '" target="_new">' + response.data.work.related[i].doi + '</a></span>';
						}
						html += '</li>';
					}
				}
				html += '</ul>';
				html += '</details>';	
				*/
				
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}	
	
        //--------------------------------------------------------------------------------
		function image (id) {
	
			var data = {};
			data.query = `query{
  image(id: "` + id + `") {
    name
    caption
    thumbnailUrl
    contentUrl
    
    container {
      id
      titles {
        title
      }
      doi
    }    
  }
}`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				if (response.data.image.name) {
					html += '<h2>' + response.data.image.name + '</h2>';				
				}

				html += '<figure>';
				
				if (response.data.image.caption) {
					html += '<figcaption>' + response.data.image.caption + '</figcaption>';				
				}
				
				// works
				if (response.data.image.container) {
					html += '<details open>';				
					html += '<summary>Works containing this image (' + response.data.image.container.length + ')</summary>';
					html += work_list_to_html(response.data.image.container);
					html += '</details>';
				}
				

				if (response.data.image.contentUrl) {
					html += '<div style="text-align:center;">';
					html += '<img class="figure" src="https://aezjkodskr.cloudimg.io/' + response.data.image.contentUrl + '?width=600">';	
					html += '</div>';			
				}
				
				


				html += '</figure>';
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}	
	
		
        //--------------------------------------------------------------------------------
		function organisation (id) {
	
			var data = {};
			data.query = `query{
	  organisation(id: "` + id + `"){
		id
		name
		alternateName
		ringgold
		ror
	  }
	}`;



		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				//html += '<h2>' + response.data.person.name[0] + '</h2>';
				html += '<h2>' + response.data.organisation.name + '</h2>';
				
				if (response.data.organisation.ror) {
					html += '<div>' + response.data.organisation.ror + '</div>';
				}
				if (response.data.organisation.ringgold) {
					html += '<div>' + response.data.organisation.ringgold + '</div>';
				}
				
				
				/*
					html += '<div><img src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png">';
					html += '&nbsp;<a href="https://orcid.org/' + response.data.person.orcid + '" target="_new">';
					html += 'https://orcid.org/' + response.data.person.orcid;
					html += '</a>';
					html += '</div>';
					*/
				
				
				/*
				if (response.data.person.works) {
					html += '<h3>References</h3>';
					html += work_list_to_html(response.data.person.works);
				}
				*/
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}
	
        //--------------------------------------------------------------------------------
		function specimen (id) {
	
			var data = {};
			data.query = `query{
  specimen(id: "` + id + `") {
    name
      
    catalogNumber
    collectionCode
    institutionCode
    
    occurrenceID
    
    gbif  
    
   identified {
      id
      name
      orcid
    }
    
    recorded {
      id
      name
      orcid
   }
       
  }
}`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				if (response.data.specimen.name) {
					html += '<h2>' + response.data.specimen.name + '</h2>';				
				}
				
				// recorded 
				if (response.data.specimen.recorded) {
					html += '<details open>';
					html += '<summary>Recorded by (' + response.data.specimen.recorded.length + ')</summary>';
					html += person_list_to_html(response.data.specimen.recorded);
					html += '</details>';
				}
				
				// identified 
				if (response.data.specimen.identified) {
					html += '<details open>';	
					html += '<summary>Identified by (' + response.data.specimen.identified.length + ')</summary>';
					html += person_list_to_html(response.data.specimen.identified);
					html += '</details>';
				}
				
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}				
			
		
        //--------------------------------------------------------------------------------
		function container (id) {
	
			var data = {};
			data.query = `query{
	  container(id: "` + id + `"){
   id
    identifier
    issn
    isbn
    titles {
      title
    }
    startDate
    endDate
    
    predecessorOf {
      id
      titles {
       title
     }
    }
    
   successorOf {
      id
      titles {
        title
      }
    }    
    
    hasPart {
      id
      doi
	  datePublished
      titles {
      	title
      }
    }
  }
}`;

		data.variables = {};
		
		$.post(
			'gql.php', 
			JSON.stringify(data), 
			function(response){ 
				//alert(JSON.stringify(response, null, 2));
				var html = '';
				
				html += '<h2>' + get_title(response.data.container.titles) + '</h2>';
				
				html += '<details>';		
				html += '<summary>Publications</summary>';
				html += '<ul>';
				for (var i in response.data.container.hasPart) {
					html += '<li>';
					html += '<a href="index.html?id=' + response.data.container.hasPart[i].id + '">' + get_title(response.data.container.hasPart[i].titles) + '</a>';
				
					if(response.data.container.hasPart[i].doi) {
						//html += '<br><span class="doi"><a href="https://doi.org/' + response.data.container.hasPart[i].doi + '" target="_new">' + response.data.container.hasPart[i].doi + '</a></span>';
						html += '<i class="ai ai-doi"></i><a href="https://doi.org/' + response.data.container.hasPart[i].doi + '" target="_new">' + response.data.container.hasPart[i].doi + '</a>';

					}
				
					html += '</li>';
				}
				html += '</ul>';
				html += '</details>';
		
				//alert(JSON.stringify(response, null, 2));
				//alert("success");
				$("#output").html(html);
				}
		);
	
	}
	
	</script>

		
	<script>
		// do we have a URL parameter?
		var id = $.urlParam('id');
		if (id) {
		   id = decodeURIComponent(id);
		   $('#id').val(id); 
		   go();
		}
		
		var q = $.urlParam('q');
		if (q) {
		   q = decodeURIComponent(q);
		   $('#id').val(q); 
		   go();
		}
		
	</script>

</body>
</html>

<!-- 
curl 'gql.php' -H 'Accept-Encoding: gzip, deflate, br' -H 'Content-Type: application/json' -H 'Accept: application/json' -H 'Connection: keep-alive' -H 'Origin: altair://-' --data-binary '{"query":"# Welcome to Altair GraphQL Client.\n# You can send your request using CmdOrCtrl + Enter.\n\n# Enter your graphQL query here.\n\nquery{\n  person(id: \"wd:Q21389139\"){\n    id\n    orcid\n    researchgate\n    twitter\n    name\n    birthDate\n    deathDate\n    description\n    thumbnailUrl\n    works {\n      id\n      name\n      doi\n    }\n  }\n}","variables":{}}' --compressed

-->

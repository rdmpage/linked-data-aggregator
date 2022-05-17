<?php

// Simple tests

error_reporting(E_ALL);

// for dev environment we do the job of .htaccess 
if(preg_match('/^\/gql.php/', $_SERVER["REQUEST_URI"])) return false;

if(preg_match('/^\/js/', $_SERVER["REQUEST_URI"])) return false;
//if(preg_match('/^\/images/', $_SERVER["REQUEST_URI"])) return false;



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
		border:1px solid rgb(192,192,192);
		margin-bottom: 1em;
	}
	
	summary {
	    padding:0.5em;
		outline-style: none; 
		background:green;
		color:white;
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
	
	.figures {
		/*background: rgb(224,224,224);*/
		display: block;
		overflow: auto;
	}
	
	.figure {
		background: white;
		margin: 0.2em;
		padding: 0.2em;		
	}
	
	
	</style>
	
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
		
			/*
			if (!id.match(/^wd:/)) {
				id = 'wd:' + id;
			}
			*/
			
			thing(id);
			
			//person(id);
			//work(id);
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
					

					if (!have_type && response.data.thing.type.indexOf('CreativeWork') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						work(id);
					}

					if (!have_type && response.data.thing.type.indexOf('ScholarlyArticle') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						work(id);
					}
					

					if (!have_type && response.data.thing.type.indexOf('ImageObject') !== -1) {
						$("#output").html("<progress></progress>");
						have_type = true;
						image(id);
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
					
					// types we will need to add:
					/// https://doi.org/10.3897/dez.65.21000.suppl1 is <http://schema.org/Dataset>
					
					if (!have_type) {
						alert("Unknown type |" + response.data.thing.type + '|');					
					}
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
    
    isBasedOn {
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

				// published in
				if (response.data.taxonName.isBasedOn) {
					html += '<h3>References</h3>';
					html += work_list_to_html(response.data.taxonName.isBasedOn);
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
			html += '<li>';
			
			// not verything in list may have a URI
			if (works[i].id.match(/^(http|urn)/)) {
				html += '<a href="./?id=' + works[i].id + '">';
			}
			
			html += get_title(works[i].titles);
			
			if (works[i].id.match(/^(http|urn)/)) {
				html += '</a>';
			}			
			
			// DOI?
			if(works[i].doi) {
				html += '&nbsp;<span class="doi"><a href="https://doi.org/' + works[i].doi + '" target="_new">' + works[i].doi + '</a></span>';
			}
			
			// Other versions?
			if(works[i].sameAs) {
				html += '&nbsp;<span style="font-size:0.8em">Other versions:';
				for (var j in works[i].sameAs) {								
					html += ' <a href="./?id=' + works[i].sameAs[j] + '">' + works[i].sameAs[j] + '</a>';	
				}
				html += '</span>';
			}
			
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
			
			html += '</li>';
		}			
		html += '</ul>';				
		
		return html;
	}
	
		
        //--------------------------------------------------------------------------------
		function person (id) {
	
			var data = {};
			data.query = `query{
	  person(id: "` + id + `"){
		id
		orcid
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
		}
		
    scientificNames {
      id
      name
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
				
				//html += '<h2>' + response.data.person.name[0] + '</h2>';
				html += '<h2>' + response.data.person.name + '</h2>';
								
				// ORCID?
				if (response.data.person.orcid) {
					html += '<div><img src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png">';
					html += '&nbsp;<a href="https://orcid.org/' + response.data.person.orcid + '" target="_new">';
					html += 'https://orcid.org/' + response.data.person.orcid;
					html += '</a>';
					html += '</div>';
				}
				
				html += '<h3>Activities</h3>';
				
				// other names
				if (response.data.person.alternateName) {
					html += '<div>';
					for (var i in response.data.person.alternateName) {
						html += response.data.person.alternateName[i] + ' ';
					}
					html += '</div>';
				}			
				
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
    }

    cited_by {
      id
      doi
    	titles {
    		title
    	}
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
							html += '<img src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png">&nbsp;';
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
					html += '<br><span class="doi"><a href="https://doi.org/' + response.data.work.doi + '" target="_new">' + response.data.work.doi + '</a></span>';					
				}

				// Other versions?
				if(response.data.work.sameAs) {
					html += '<br><span style="font-size:0.8em">Other versions:';
					for (var j in response.data.work.sameAs) {								
						html += ' <a href="./?id=' + response.data.work.sameAs[j] + '">' + response.data.work.sameAs[j] + '</a>';	
					}
					html += '</span>';
				}

				// figures				
				if (response.data.work.figures) {
					html += '<h3>Figures</h3>';
					html += '<div class="figures">';
					
					for (var i in response.data.work.figures) {
						if (response.data.work.figures[i].thumbnailUrl) {
						    html += '<a href="./?id=' + response.data.work.figures[i].id + '">';
							html += '<img class="figure" src="https://aipbvczbup.cloudimg.io/s/height/80/' + response.data.work.figures[i].thumbnailUrl + '">';
							html += '</a>';
						}
					}
					
					html += '</div>';
				}
				
				// literature cited
				if (response.data.work.cites) {
					html += '<h3>Cites</h3>';
					html += work_list_to_html(response.data.work.cites);
				}

				// citing
				if (response.data.work.cited_by) {
					html += '<h3>Cited by</h3>';
					html += work_list_to_html(response.data.work.cited_by);
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
					html += '<h3>Taxon names</h3>';
					html += name_list_to_html(response.data.work.scientificNames);
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

				if (response.data.image.contentUrl) {
					html += '<div>';
					html += '<img class="figure" src="https://aipbvczbup.cloudimg.io/s/width/600/' + response.data.image.contentUrl + '">';	
					html += '</div>';			
				}

		
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
						html += '<br><span class="doi"><a href="https://doi.org/' + response.data.container.hasPart[i].doi + '" target="_new">' + response.data.container.hasPart[i].doi + '</a></span>';
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
	</script>

</body>
</html>

<!-- 
curl 'gql.php' -H 'Accept-Encoding: gzip, deflate, br' -H 'Content-Type: application/json' -H 'Accept: application/json' -H 'Connection: keep-alive' -H 'Origin: altair://-' --data-binary '{"query":"# Welcome to Altair GraphQL Client.\n# You can send your request using CmdOrCtrl + Enter.\n\n# Enter your graphQL query here.\n\nquery{\n  person(id: \"wd:Q21389139\"){\n    id\n    orcid\n    researchgate\n    twitter\n    name\n    birthDate\n    deathDate\n    description\n    thumbnailUrl\n    works {\n      id\n      name\n      doi\n    }\n  }\n}","variables":{}}' --compressed

-->

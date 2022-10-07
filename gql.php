<?php

// Heavily borrows from Roger Hyam's WFO code

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/vendor/autoload.php');

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\EnumType;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;


/*

Need to do the following:

- Create a class for the type of object we are going to query for, e.g. PersonType (the class extends ObjectType). This type will list all the fields it supports. Fields can be scalars or lists. If the value for a field will require it's own resolver (e.g., the list of works that a person authored) then you need to add that resolver to the field and use `$thing` as source of arguments to pass to that function (e.g., `$thing->id`).

- Add a variable and method to TypeRegister to register this type

- Add type to the schema and include arguments passed to resolver, and call the function that does the work and returns an object corresponding to the type (e.g., data for a person).

- Create a function that will do the actual work of populating the object. For example, write a SPARQL query to retrieve data from Wikidata. Approach I take here is to retrieve results in JSON-LD then compact them using a `@context` that makes as many keys as possible into simple strings (i.e., no namespaces) that match the GQL schema. hence we can use the power of SPARQL but the simplicity of JSON for results. Note that there is a lot of scope for problems with Wikidata as if there are multiple values where one would expect a single value the GQL will complain (e.g., it may get an array of dates for `datePublished` rather than a single string.)

*/

//----------------------------------------------------------------------------------------

// Code to resolve queries, in this case using our triple store
require_once (dirname(__FILE__) . '/gql_queries.php');

//----------------------------------------------------------------------------------------
class ThingType extends ObjectType
{

    public function __construct()
    {
        error_log('ThingType');
        $config = [
			'description' =>  "An entity in the triple store",
            'fields' => function(){
                return [
                     
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Id of thing"
                    ],   
                                     
                    'name' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Name of the thing."
                    ],            
                    
                   'type' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Type(s) of thing"
                    ],
                                
                                                       
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class OrganisationType extends ObjectType
{

    public function __construct()
    {
        error_log('OrganisationType');
        $config = [
			'description' =>  "An organisation",
            'fields' => function(){
                return [
                     
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Id of thing"
                    ],   
                                     
                    'name' => [
                        'type' => Type::string(),
                        'description' => "Name of the thing."
                    ],      
                    
                                     
                    'alternateName' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Other name(s) of the thing."
                    ],    
                    
                                     
                    'ringgold' => [
                        'type' => Type::string(),
                        'description' => "RINGGOLD number"
                    ],                                      

                    'ror' => [
                        'type' => Type::string(),
                        'description' => "ROR identifier"
                    ],                                                                                            
                    
                   'type' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Type(s) of thing"
                    ],
                                
                                                       
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class ImageType extends ObjectType
{

    public function __construct()
    {
        error_log('ImageType');
        $config = [
			'description' =>   "An image.",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the image."
                    ],
                    
                    'caption' => [
                        'type' => Type::string(),
                        'description' => "Caption for the image."
                    ],
                                        
                    'name' => [
                        'type' => Type::string(),
                        'description' => "Name for the image."
                    ],  
                    
                   'container' => [
                        'type' =>Type::listOf(TypeRegister::SimpleWorkType()),
                        'description' => "Works that include this image",
            			'resolve' => function($thing) {
            			
 							if ($thing && isset($thing->id))
            			    {
                    			return image_container(array('id' => $thing->id));
                    		}            			
             			}
                    ],    
                  
                    'contentUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to image."
                    ],

                
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to a thumbnail view of the image."
                    ],

                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
// Simple thing in a list
class ThingListType extends ObjectType
{

    public function __construct()
    {
        error_log('ThingListType');
        $config = [
			'description' =>  "Simplified thing to include in lists.",
            'fields' => function(){
                return [
                     
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Id of work"
                    ],  
                    
                    'name' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Name of the thing."
                    ]            
                     
                                                       
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}



//----------------------------------------------------------------------------------------
// Work that we can use in a list, for example
class SimpleWorkType extends ObjectType
{

    public function __construct()
    {
        error_log('SimpleWorkType');
        $config = [
			'description' =>  "Simplified work to include in lists.",
            'fields' => function(){
                return [
                     
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Id of work"
                    ],   
                                        
                   'datePublished' => [
                        'type' => Type::string(),
                        'description' => "Publication date"
                    ],

                   'doi' => [
                        'type' => Type::string(),
                        'description' => "DOI for this work."
                    ],
                    
                   'formattedCitation' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Formatted citation"
                    ], 
                                                                                                                  
                    'titles' => [
                        'type' => Type::listOf(TypeRegister::titleType()),
                        'description' => "Title of the work, may be in more than one language."
                    ], 
                    
                    'url' => [
                        'type' => Type::string(),
                        'description' => "URL for this work."
                    ],
                    
                    'sameAs' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Other instances of this work."
                    ],   
                    
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to a thumbnail view of the image or work."
                    ],
                                                                                                     
                                                       
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
// A published work
class WorkType extends ObjectType
{

    public function __construct()
    {
        error_log('WorkType');
        $config = [
			'description' =>   "A work such as an article or a book",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Identifier for the publication, such as a DOI."
                    ],
                    
                    'sameAs' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Other instances of this work."
                    ],
                    
                    'author' => [
                        'type' => Type::listOf(TypeRegister::PersonType()),
                        'description' => "The main researchers involved in producing the data, or the authors of the publication."
                    ],                                                                                 
                    
                    'volumeNumber' => [
                        'type' => Type::string(),
                        'description' => "Volume"
                    ],  
                     
                    'issueNumber' => [
                        'type' => Type::string(),
                        'description' => "Issue."
                    ], 
                      
                    'pagination' => [
                        'type' => Type::string(),
                        'description' => "Pagination."
                    ],   
 
                    'description' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Description."
                    ],                      
                    
                    /*
                    'container' => [
                        'type' => TypeRegister::ContainerType(),
                        'description' => "The container (e.g. journal or repository) hosting the resource."
                    ],  
                                      
                                      
                    'contentUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to download content directly, if available."
                    ],   
                    
                    'citeproc' => [
                        'type' => Type::string(),
                        'description' => "Metadata as Citeation Style Language JSON."
                    ],
                    
                    
                    'creators' => [
                        'type' => Type::listOf(TypeRegister::creatorType()),
                        'description' => "The main researchers involved in producing the data, or the authors of the publication."
                    ],     
                    */     
                    
                    'datePublished' => [
                        'type' => Type::string(),
                        'description' => "Publication date"
                    ],                              
                
                    'doi' => [
                        'type' => Type::string(),
                        'description' => "The DOI for the publication."
                    ],

					/*
                    'formattedCitation' => [
                        'type' => Type::string(),
                        'description' => "Metadata as formatted citation."
                    ],
                    */
                    
                    'figures' => [
                        'type' => Type::listOf(TypeRegister::imageType()),
                        'description' => "Figures in the publication."
                    ],  
                    
                    /*
                    'keywords' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Figures in the publication."
                    ],                      
                    
                    'license' => [
                        'type' => Type::string(),
                        'description' => "License under which content is made available."
                    ],   
					*/
					                    
                    'about' => [
                        'type' => Type::listOf(TypeRegister::thingType()),
                        'description' => "Things this work is about",
                        'resolve' => function($thing) {
                    		return what_work_is_about_query(array('id' => $thing->id));
                    	}
                    ],     
                    
                    'scientificNames' => [
                        'type' => Type::listOf(TypeRegister::TaxonNameType()),
                        'description' => "Taxonomic names published in this work",
            			'resolve' => function($thing) {
                    		return work_scientific_names_query(array('id' => $thing->id));
            			}                        
                     ],                                                                                
                    
                    'titles' => [
                        'type' => Type::listOf(TypeRegister::titleType()),
                        'description' => "Title of the work, may be in more than one language."
                    ],
                    
                     'url' => [
                        'type' => Type::string(),
                        'description' => "URL for the publication."
                    ],
                    
                    
                   'cites' => [
                        'type' =>Type::listOf(TypeRegister::SimpleWorkType()),
                        'description' => "Works this work cites",
            			'resolve' => function($thing) {
            			
 							if ($thing && isset($thing->id))
            			    {
                    			return work_cites(array('id' => $thing->id));
                    		}            			
             			}
                    ],    
                    
                   'cited_by' => [
                        'type' =>Type::listOf(TypeRegister::SimpleWorkType()),
                        'description' => "Works citing this work",
            			'resolve' => function($thing) {
            			
 							if ($thing && isset($thing->id))
            			    {
                    			return work_cited_by(array('id' => $thing->id));
                    		}            			
             			}
                    ],    
                    
                    'mainEntityOfPage' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Web page about this person."
                    ],                    
                    
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class SimplePersonType extends ObjectType
{

    public function __construct()
    {
        error_log('SimplePersonType');
        $config = [
			'description' =>   "A person in a list of people.",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the person."
                    ],
                
                    'orcid' => [
                        'type' => Type::ID(),
                        'description' => "ORCID for person."
                    ],
 
                    'name' => [
                        'type' => Type::string(),
                        'description' => "The name of the person."
                    ],                      
                    
                    /*
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to a thumbnail view of the image."
                    ],
                    */
                                        
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class PersonType extends ObjectType
{

    public function __construct()
    {
        error_log('PersonType');
        $config = [
			'description' =>   "A person.",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the person."
                    ],
                
                    'orcid' => [
                        'type' => Type::ID(),
                        'description' => "ORCID for person."
                    ],
                                        
                   'researchgate' => [
                        'type' => Type::ID(),
                        'description' => "ResearchGate profile for person"
                    ],   
 
                   'wikidata' => [
                        'type' => Type::ID(),
                        'description' => "Wikidata item person"
                    ],               
                
                    'givenName' => [
                        'type' => Type::string(),
                        'description' => "Given name."
                    ],

                    'familyName' => [
                        'type' => Type::string(),
                        'description' => "Family name."
                    ],

                    'name' => [
                        'type' => Type::string(),
                        'description' => "The name of the person."
                    ],
                    
                    'alternateName' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Other name(s) of the person."
                    ],                     
                     
                    'works' => [
                        'type' =>Type::listOf(TypeRegister::simpleWorkType()),
                        'description' => "Authored works",
            			'resolve' => function($thing) {
                    		return person_works_query(array('id' => $thing->id));
            			}
                    ],    
                    
                    'affiliation' => [
                        'type' =>Type::listOf(TypeRegister::thingListType()),
                        'description' => "Afilliation",
            			'resolve' => function($thing) {
                    		return person_affiliation_query(array('id' => $thing->id));
            			}
                    ],                        
                
                    'scientificNames' => [
                        'type' => Type::listOf(TypeRegister::taxonNameType()),
                        'description' => "Scientific names in publications.",
                        'resolve' => function($thing) {
                    		return person_scientific_names_query(array('id' => $thing->id));           	
                    	}
                    ],                  
                                        
                    'mainEntityOfPage' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Web page about this person."
                    ],                    
                                        
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to a thumbnail view of the image."
                    ],                    
                    
                    'images' => [
                        'type' => Type::listOf(TypeRegister::imageType()),
                        'description' => "Images in works by this person.",
                         'resolve' => function($thing) {
                    		return person_images_query(array('id' => $thing->id));           	
                    	}
                   ],  
                                   
                    'identified' => [
                        'type' => Type::listOf(TypeRegister::simpleSpecimenType()),
                        'description' => "Specimens identified.",
                        'resolve' => function($thing) {
                    		return person_identified_specimen_query(array('id' => $thing->id));           	
                    	}
                    ],      
                
                    'recorded' => [
                        'type' => Type::listOf(TypeRegister::simpleSpecimenType()),
                        'description' => "Specimens recorded.",
                        'resolve' => function($thing) {
                    		return person_recorded_specimen_query(array('id' => $thing->id));           	
                    	}
                    ],      
                   
                    
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class SpecimenType extends ObjectType
{

    public function __construct()
    {
        error_log('ImageType');
        $config = [
			'description' =>   "A biological specimen",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the specimen."
                    ],
                                         
                    'name' => [
                        'type' => Type::string(),
                        'description' => "Name for the specimen."
                    ],  
 
                                          
                    'institutionCode' => [
                        'type' => Type::string(),
                        'description' => "Acronym or code for the institution holding this specimen."
                    ],  

                                         
                    'collectionCode' => [
                        'type' => Type::string(),
                        'description' => "Acronym or code for the collection containing this specimen."
                    ],  

                                         
                    'catalogNumber' => [
                        'type' => Type::string(),
                        'description' => "Catalogue number for the specimen."
                    ],  

                                         
                    'occurrenceID' => [
                        'type' => Type::string(),
                        'description' => "Unique identifier for the specimen"
                    ],  
                    
 
                     'gbif' => [
                        'type' => Type::ID(),
                        'description' => "Corresponding record in GBIF"
                    ],  
                    

                    'identified' => [
                        'type' => Type::listOf(TypeRegister::SimplePersonType()),
                        'description' => "The person or people who identified this specimen.",
            			'resolve' => function($thing) {
                    		return specimen_identified_query(array('id' => $thing->id));
            			}
                        
                    ],                                                                                 
                    
                     'recorded' => [
                        'type' => Type::listOf(TypeRegister::SimplePersonType()),
                        'description' => "The person or people who recorded this specimen.",
            			'resolve' => function($thing) {
                    		return specimen_recorded_query(array('id' => $thing->id));
            			}
                        
                    ],                                                                                 
                  
                     
 
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class SimpleSpecimenType extends ObjectType
{

    public function __construct()
    {
        error_log('SimpleSpecimenType');
        $config = [
			'description' =>   "A biological specimen in a list",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the specimen."
                    ],
                                         
                    'name' => [
                        'type' => Type::string(),
                        'description' => "Name for the specimen."
                    ],  
 
 /*                                         
                    'institutionCode' => [
                        'type' => Type::string(),
                        'description' => "Acronym or code for the institution holding this specimen."
                    ],  

                                         
                    'collectionCode' => [
                        'type' => Type::string(),
                        'description' => "Acronym or code for the collection containing this specimen."
                    ],  

                                         
                    'catalogNumber' => [
                        'type' => Type::string(),
                        'description' => "Catalogue number for the specimen."
                    ],  

                                         
                    'occurrenceID' => [
                        'type' => Type::string(),
                        'description' => "Unique identifier for the specimen"
                    ],  
                    
 
                     'gbif' => [
                        'type' => Type::ID(),
                        'description' => "Corresponding record in GBIF"
                    ],  
                    
*/                     
 
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
// Based on bioschemas.org
class TaxonNameType extends ObjectType
{

    public function __construct()
    {
        error_log('TaxonNameType');
        $config = [
			'description' =>   "A taxonomic name",
            'fields' => function(){
                return [
                
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the taxonomic name."
                    ],                              
                    
                    'name' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "The taxonomic name"
                    ],

                    'author' => [
                        'type' => Type::string(),
                        'author' => "Author(s) of the name"
                    ],
                 
                    'isBasedOn' => [
                        'type' => Type::listOf(TypeRegister::simpleWorkType()),
                        'description' => "publication that established this name",
            			'resolve' => function($thing) {
                    		return taxon_name_works_query(array('id' => $thing->id));
            			}
                        
                     ],     
                     
                   'url' => [
                        'type' => Type::string(),
                        'description' => "URL for webpage for the taxonomic name."
                    ],
                      
                      
                    
                    'rankString' => [
                        'type' => Type::string(),
                        'description' => 'The taxonomic rank of this name'
                    ],
                    
                                                        
                    'alternateName' => [
                        'type' => Type::listOf(TypeRegister::TaxonNameType()),
                        'description' => "Variations of this name, such as objective synonyms",
            			'resolve' => function($thing) {
                    		return taxon_name_alternate_name_query(array('id' => $thing->id));
            			}                        
                     ],    
                     
                    'subjectOf' => [
                        'type' => Type::listOf(TypeRegister::simpleWorkType()),
                        'description' => "Publications about this name",
            			'resolve' => function($thing) {
                    		return taxon_name_alternate_works_query(array('id' => $thing->id));
            			}
                        
                     ],     
                                                                                                 
                 
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
// Based on bioschemas.org
class TaxonType extends ObjectType
{

    public function __construct()
    {
        error_log('TaxonType');
        $config = [
			'description' =>   "A taxon (i.e., a node in a classification)",
            'fields' => function(){
                return [
                
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the taxon."
                    ],
                              
                                       
                    'scientificName' => [
                        'type' => TypeRegister::TaxonNameType(),
                        'description' => "The name that should be used for this taxon."
                    ],
                    
                    'name' => [
                        'type' => Type::string(),
                        'description' => "Name of the taxon"
                    ],
                    
                    'taxonRank' => [
                        'type' => Type::string(),
                        'description' => 'The rank of this taxon'
                    ],                    
                    
                    'references' => [
                        'type' =>Type::listOf(TypeRegister::SimpleWorkType()),
                        'description' => "Publications on this taxon",

						// MAGIC! Fuck knows why, but this works. 
						// Thing (can be called anything)
						// is the object returned by the GQL query 
						// (in this case taxon name), so we just use 
						// id as a parameter to get publications for the name.
						
            			'resolve' => function($thing) {
            			
 							if ($thing && isset($thing->id))
            			    {
                    			return works_about_query(array('id' => $thing->id));
                    		}            			
             			}
                    ],    
                    
                    
                    'parentTaxon' => [
                        'type' => TypeRegister::TaxonType(),
                        'description' => 'Parent of this taxon'
                    ],                    
                    
                    
                   'childTaxon' => [
                        'type' =>Type::listOf(TypeRegister::TaxonType()),
                        'description' => "Children of this taxon"
                    ],    
                    
                   /*
                    'alternateName' => [
                        'type' =>Type::listOf(Type::string()),
                        'description' => "Synonym as simple string",
                    ], 
                    */                   
                     
                    'alternateScientificName' => [
                        'type' =>Type::listOf(TypeRegister::TaxonNameType()),
                        'description' => "Synonym as taxonomic name",
                    ],                    
                     
/*                    
                    'hasSynonym' => [
                        'type' => Type::listOf(TypeRegister::taxonNameType()),
                        'description' => "A name associated with this TaxonConcept which should not be used.
                        This includes homotypic (nomenclatural) synonyms which share the same type specimen as the accepted name 
                        and heterotypic (taxonomic) synonyms whose type specimens are considered to fall within the circumscription of this taxon."
                    ],                    
                    
                    // This could be a query
                    'hasPart' => [
                        'type' => Type::listOf(TypeRegister::TaxonType()),
                        'description' => "A sub taxon of the current taxon within this classification"
                    ],
                    
                    // This could be a query 
                    'isPartOf' => [
                        'type' => TypeRegister::TaxonType(),
                        'description' => "The parent taxon of the current taxon within this classification"
                    ],
                    
   */
                    
                   
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
// based on DataCite GraphQL, one way to support multilingual titles
class TitleType extends ObjectType
{

    public function __construct()
    {
        error_log('NameType');
        $config = [
			'description' =>   "Information about a title",
            'fields' => function(){
                return [
                    'lang' => [
                        'type' => Type::ID(),
                        'description' => "Language."
                    ],
                
                    'title' => [
                        'type' => Type::string(),
                        'description' => "Title."
                    ]                    
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class SearchType extends ObjectType
{

    public function __construct()
    {
        error_log('SearchType');
        $config = [
			'description' =>   "A simple text search.",
            'fields' => function(){
                return [
                
                    'query' => [
                        'type' => Type::string(),
                        'caption' => "Search query"
                    ],                
                
                    'results' => [
                        'type' => Type::listOf(TypeRegister::SearchResultType()),
                        'description' => "Search results"
                    ]

                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}
//----------------------------------------------------------------------------------------
class SearchResultType extends ObjectType
{
    public function __construct()
    {
        error_log('SearchResultType');
        $config = [
			'description' =>  "A search result.",
            'fields' => function(){
                return [
                                 
                    'id' => [
                        'type' => Type::string(),
                        'caption' => "Id of thing"
                    ],
                    
                   'type' => [
                        'type' => Type::listOf(Type::string()),
                        'caption' => "Type of thing"
                    ],
                                                         
                   'thumbnailUrl' => [
                        'type' => Type::string(),
                        'caption' => "Link to thumbnail image of thing"
                    ],
                  
 					'name' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Name of thing"
                    ],   
                    
 					'score' => [
                        'type' => Type::float(),
                        'description' => "Score of thing in search results"
                    ],  
                    
 					'identifier' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Identifier of thing"
                    ],   
                                          
                                         
                   
                    ];
            }                    
			      
       ];
      parent::__construct($config);

    }

}


//----------------------------------------------------------------------------------------

class TypeRegister {

	private static $thingType;	
	private static $taxonNameType;	
	private static $taxonType;	
	
	private static $containerType;
	private static $titleType;
	private static $personType;
	private static $simpleWorkType;
	private static $workType;
	
	private static $imageType;	

	private static $organisationType;	
	
	private static $thingListType;

	private static $searchType;
	private static $searchResultType;
	
	private static $specimenType;
	
	private static $simplePersonType;
	private static $simpleSpecimenType;
	
	
    // thing
    public static function thingType(){
        return self::$thingType ?: (self::$thingType = new ThingType());
    }      
    
    public static function searchType(){
        return self::$searchType ?: (self::$searchType = new SearchType());
    }      
        
    // search result
    public static function searchResultType(){
        return self::$searchResultType ?: (self::$searchResultType = new SearchResultType());
    }      
    
    
    // an image
    public static function imageType(){
        return self::$imageType ?: (self::$imageType = new ImageType());
    } 
        
    // work in a list of works
    public static function simpleWorkType(){
        return self::$simpleWorkType ?: (self::$simpleWorkType = new SimpleWorkType());
    }    
    
    // a person
    public static function personType(){
        return self::$personType ?: (self::$personType = new PersonType());
    } 
    
    // a person in a list
    public static function simplePersonType(){
        return self::$simplePersonType ?: (self::$simplePersonType = new SimplePersonType());
    }     
        
    
    // work
    public static function workType(){
        return self::$workType ?: (self::$workType = new WorkType());
    }      
      
    // organisation
    public static function organisationType(){
        return self::$organisationType ?: (self::$organisationType = new OrganisationType());
    }  
    
    // thing in a list
    public static function thingListType(){
        return self::$thingListType ?: (self::$thingListType = new ThingListType());
    }  

    // taxon name AKA scientific name
    public static function taxonNameType(){
        return self::$taxonNameType ?: (self::$taxonNameType = new TaxonNameType());
    }      

    // taxon
    public static function taxonType(){
        return self::$taxonType ?: (self::$taxonType = new TaxonType());
    }      
    
 	// title
    public static function titleType(){
        return self::$titleType ?: (self::$titleType = new TitleType());
    }  
    
    // specimen        
    public static function specimenType(){
        return self::$specimenType ?: (self::$specimenType = new SpecimenType());
    }      
    
    // specimen in a list
    public static function simpleSpecimenType(){
        return self::$simpleSpecimenType ?: (self::$simpleSpecimenType = new SimpleSpecimenType());
    }     
    

}

$typeReg = new TypeRegister();


//----------------------------------------------------------------------------------------

$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'Query',
        'description' => 
            "Experimental interface",
        'fields' => [
                
            'hello' => [
       	     'type' => Type::string(),
          	  'resolve' => function() {
               	 return 'Hello World!';
            	}
        	],
        	       	
           'thing' => [
                'type' => TypeRegister::thingType(),
                'description' => 'Returns a thing, its name and its type',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for thing'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return thing_query($args);
                }
            ],  
                    	
           'image' => [
                'type' => TypeRegister::imageType(),
                'description' => 'Returns an image by its identifier',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for image'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return image_query($args);
                }
            ],    
            
           'person' => [
                'type' => TypeRegister::personType(),
                'description' => 'Returns a person',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for person'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return person_query($args);
                }
            ],        	
                	
           
           'specimen' => [
                'type' => TypeRegister::specimenType(),
                'description' => 'Returns a specimen',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for specimen'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return specimen_query($args);
                }
            ],        	
                	
            
            
           'taxonName' => [
                'type' => TypeRegister::TaxonNameType(),
                'description' => 'Returns a taxon name by its identifier',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for taxon name'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return taxon_name_query($args);
                }
            ], 
            
           'taxon' => [
                'type' => TypeRegister::taxonType(),
                'description' => 'Returns a taxon by its identifier',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for taxon'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return taxon_query($args);
                }
            ],  
            
           'work' => [
                'type' => TypeRegister::WorkType(),
                'description' => 'Returns a work by its identifier',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for work'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return work_query($args);
                }
            ], 
            
           'organisation' => [
                'type' => TypeRegister::organisationType(),
                'description' => 'Returns an organisation',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for organisation'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return organisation_query($args);
                }
            ],  
             
           'search' => [
                'type' => TypeRegister::searchType(),
                'description' => 'Search for entities',
                'args' => [
                    'query' => [
                        'type' => Type::string(),
                        'description' => 'Search query'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    return search_query($args);
                }
            ],    
              
            
 
        ]
    ])
]);


$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$query = $input['query'];
$variableValues = isset($input['variables']) ? $input['variables'] : null;

$debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;

try {
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    $output = $result->toArray($debug);
} catch (\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}
header('Content-Type: application/json');
echo json_encode($output);


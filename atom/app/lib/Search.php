<?php

namespace Atom;

class Search extends DBObject {
	
	protected $table = 'search_index';
	
	/*
	 * Example:
	 * 
	 *	SELECT *, MATCH (`search_text`) AGAINST ('2007 +Airbus' IN BOOLEAN MODE) AS score
	 *		FROM `ab_search_index`
	 *	  WHERE MATCH (`search_text`) AGAINST ('2007 +Airbus' IN BOOLEAN MODE);
	 */
	
	public function match($text = null) {
		
	}
}
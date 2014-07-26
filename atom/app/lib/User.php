<?php

 /**
 * AtomMVC: User Class
 * atom/app/lib/User.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * 
 */

namespace Atom;

class User extends DBObject {
	
	protected $usernameField = 'email';
	
	protected $hidden = ['password'];
	
	protected $excluded = ['add_date','last_modified'];
	
	protected $searchKeys = ['email'];
	
	public static function findByUsername($username) {
		
		$user = null;
		
		$properties = static::propertiesByUsername($username);
		
		if($properties) {
			
			$user = new static($properties);
		}
		
		return $user;
	}
	
	protected function propertiesByUsername($username) {
		return $this->queryBuilder()
			->where(static::$usernameField, $username)
			->first();
	}
}
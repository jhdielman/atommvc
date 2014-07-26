<?php

/**
 * AtomMVC: Permissions Config
 * atom/config/permissions.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * 
 */

return [
	
	/**
	 * Format:
	 *
	 * 'url-controller-segment' => [
	 * 		'url-method-segment' => [
	 * 		
	 * 			// allow|deny is an array of user_type_ids
	 *			// 0 indicates anonymous users
	 *			'allow' => [5,6],
	 * 			'deny' => [0],
	 * 			'failedRedirect' => '/login',
	 * 			'authRedirect' => '/'
	 * 		]
	 * ]
	 */
];
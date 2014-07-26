<?php
/**
 * AtomMVC
 * atom/app/main.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * 
 * The following class objects are compact implementations from Laravel's library.
 * I love their code and integrated them into the Atom library.
 * I'm not sure I could have written them better myself.
 *
 * @see /lib/Expression.php
 * @see /lib/JoinClause.php
 * @see /lib/MySqlGrammar.php;
 * @see /lib/Pluralizer.php;
 * @see /lib/QueryBuilder.php;
 * @see /lib/QueryException.php;
 * 
 */


// Bootstrap the constants config
require dirname(__DIR__).'/config/constants.php';

require 'Bootstrapper'.PHPEXT;

$directories = [
	
	// Bootstrap the core abstractions
	//	Note:	Calling 'Object' first since we may make that
	//			the base object for everything... need to think
	//			about architecture.
	ATOM_CORE_PATH => [
		'Object','Controller','DBObject','Model'
	],
	
	// Bootstrap the main library
	ATOM_LIB_PATH => [],
	
	// Bootstrap the third-party vendor plugins
	ATOM_VENDOR_PATH => [
		'htmlpurifier/HTMLPurifier',
		'phpmailer/PHPMailerAutoload',
		//'stripe/lib/Stripe'
	],
	
	// Bootstrap the Client specific objects
	ATOM_CLIENT_PATH => [],
	
	// Bootstrap out Models
	ATOM_MODEL_PATH => []
];

// Load dependancies
Atom\Bootstrapper::load($directories);

// Initialize the config
Atom\Config::load();

// Initialize site security
Atom\Security::initialize();

// Run our magical Router class and Let's go!
Atom\Router::route();
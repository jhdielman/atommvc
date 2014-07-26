<?php

/**
 * AtomMVC by Jason Dielman
 *
 * @package   AtomMVC
 * @author    Jason Dielman.
 * @copyright Copyright (c) 2013, Jason Dielman.
 * @license   http://opensource.org/licenses/MIT
 * @link      http://jasondielman.com
 *
 * Many thanks to everyone who inspired me to et off my duff and do this!
 * - My wife Nikki :)
 * - Eric Kever
 * - Craft CMS team
 * - Laravel framework
 */

// Path to your app/ folder
$atomPath = '../atom';

// Do not edit below this line
$path = rtrim($atomPath, '/').'/app/main.php';

if (!is_file($path))
{
	exit('Could not find your atom/ folder. Please ensure that <strong><code>$atomPath</code></strong> is set correctly in '.__FILE__);
}

require_once $path;
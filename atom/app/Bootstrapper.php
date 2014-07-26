<?php

namespace Atom;

class Bootstrapper {
	
	public static function load(array $directories) {
		
		//$start_time = microtime(TRUE);
		
		foreach($directories as $directory => $files) {
			
			if(count($files)) {
				
				foreach($files as $file) {
					
					require $directory.$file.PHPEXT;
				}
				
			} else {
				
				$dir = new \DirectoryIterator($directory);
				
				foreach ($dir as $file) {
					
					if (!$file->isDot()) {
						
						require $directory.$file->getFilename();
					}
				}
				
				//$dir = opendir($directory);
				//
				//while(($file = readdir($dir)) !== false) {
				//	
				//	if ( $file == '.' || $file == '..' ) {
				//		continue;
				//	}
				//	
				//	require $directory.$file;
				//}
				//
				//closedir($dir);
				
			}
		}
		
		//// mark the stop time
		//$stop_time = microtime(TRUE);
		// 
		//// get the difference in seconds
		//$time = $stop_time - $start_time;
		// 
		//print "Elapsed time was $time seconds.";
	}
}

<?php

namespace Atom;

class Bootstrapper {
    
    protected static $dependencies = [
        '/interfaces',
        '/traits',
        '/abstractions'
    ];
    
    public static function load(array $directories) {
        
        foreach($directories as $directory => $files) {
            if(count($files)) {
                foreach($files as $file) {
                    $path = $directory.$file.PHPEXT;
                    if (is_file($path)) {
                        require $path;
                    }
                }
            } else {
                $dirs = static::getCodePaths($directory);
                foreach($dirs as $dir) {
                    try {
                        $dir = new \DirectoryIterator($dir);
                        foreach ($dir as $item) {
                            if ($item->isFile() && !$item->isDot()) {
                                require $item->getPathname();
                            }
                        }
                    } catch(\Exception $ex) {
                        
                    }
                }
            }
        }
    }
    
    protected static function getCodePaths($directory) {
        $paths = [];
        $directory = rtrim($directory,'/');
        foreach(static::$dependencies as $dependency) {
            array_push($paths, $directory.$dependency);
        }
        array_push($paths, $directory.'/');
        return $paths;
    }
}

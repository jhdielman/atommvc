<?php

/**
 * AtomMVC: Error Class
 * atom/app/lib/Error.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Error {
    
    public static function show($message, $code = null) {
        
        $error = $code ? $code : 'error';
        $errorFile = ATOM_ERROR_PATH.$error.PHPEXT;
        
        if(!is_file($errorFile)) {
            $errorFile = ATOM_ERROR_PATH.'error'.PHPEXT;
        }
        
        include $errorFile;
        
        exit();  
    }
}

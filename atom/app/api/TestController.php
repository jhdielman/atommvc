<?php

namespace Atom;

class TestController {
    
    public function get($param = null) {
        
        echo json_encode(['test' => $param]);
    }
}
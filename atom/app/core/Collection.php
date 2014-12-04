<?php

/**
 * AtomMVC: Collection Class
 * atom/app/lib/Collection.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

use Closure;

class Collection extends Object implements \JsonSerializable {

    public function __construct($data) {
        //If a Collection is passed in, push the inner-array forward.
        if ($data != null && is_a($data, get_class()))
            parent::__construct($data->getAll());
        else
            parent::__construct($data);
    }
    
    public function get($key) {
        if(isset($this->data) && is_array($this->data)) {
            if(array_key_exists($key, $this->data)) {
                return $this->data[$key];
            }
        }
    }

    public function set($key, $value) {
        if(isset($this->data) && is_array($this->data)) {
            $this->data[$key] = $value;
        }
    }

    public function value($key, $value = null) {

        $argCount = func_num_args();
        $get = $argCount = 1;
        $set = $argCount = 2;

        if(isset($this->data) && is_array($this->data)) {

            if($get && array_key_exists($key, $this->data)) {
                return $this->data[$key];
            } else if ($set) {
                if($value === null) {
                    unset($this->data[$key]);
                } else {
                    $this->data[$key] = $value;
                }
            }
        }
    }

    public function level() {
        $return = array();

        array_walk_recursive($this->data, function($x) use (&$return) {
            $return[] = $x;
        });

        $this->data = $return;

        return $this;
    }

    public function range($from, $to = null, $preserveKeys = false) {

        $itemCount = count($this->data);
        if(!$to) { $to = $itemCount; }
        if(!$from || $from > $itemCount) { $from = $itemCount; }

        $this->data = array_slice($this->data, $from, ($to - $from), $preserveKeys);

        return $this;
    }

    public function fetch($key) {
        
        $array = $this->data;
        
        foreach (explode('.', $key) as $segment) {
            $results = array();
            foreach ($array as $value) {
                $value = (array) $value;
                $results[] = $value[$segment];
            }
            $array = array_values($results);
        }
        
        $this->data = array_values($results);
        
        return $this;
    }

    public function count() {
        return count($this->data);
    }

    public function each(Closure $callback) {
        
        if($callback instanceof Closure ) {
            foreach($this->data as $key => $value) {
                $callback($key, $value);
            }
        }
        
        return $this;
    }
    
    public function toJson() {
        return json_encode($this);
    }
    
    public function jsonSerialize() {
        return $this->data;
    }
}
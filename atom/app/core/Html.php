<?php

/**
 * AtomMVC: Hash Class
 * atom/app/lib/Hash.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 */

namespace Atom;

class Html {

    public static function select($options = [], $attributes = [], $selectedValue = null, $textAsValue = false) {

        $select = 'select';
        $opts = '';
        $attrs = static::attributes($attributes);

        foreach($options as $val => $text) {
            $value = $textAsValue ? $text : $val;
            $selected = $value == $selectedValue;
            $opts .= static::option($text, $value, $selected);
        }

        return "<$select $attrs>$opts</$select>";
    }

    public static function option($text, $value = null, $selected = false) {

        $option = 'option';
        $value = static::attr('value',$value);
        return "<$option $value>$text</$option>";
    }

    public static function stateProvince($countryCode = 'USA', $default = null) {

        $country = Config::get('locations',$countryCode);
        $statesProvinces = $country['statesProvinces'];

        return static::select($statesProvinces,['name' => 'states'],$default);
    }

    public static function attributes($attrList = []) {

        $attributes = [];

        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        foreach ((array) $attrList as $key => $value) {

            $attr = static::attr($key, $value);

            if ( ! is_null($attr)) {
                $attributes[] = $attr;
            }
        }

        return count($attributes) > 0 ? ' '.implode(' ', $attributes) : '';
    }

    public static function attr($key, $value) {

        if (is_numeric($key)) {
            $key = $value;
        }

        if ( ! is_null($value)) {
            return $key.'="'.$value.'"';
        }
    }
    
    public static function element() {
        
    }
}
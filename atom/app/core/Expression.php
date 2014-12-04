<?php

/**
 * AtomMVC: Expression Class
 * atom/app/lib/Expression.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 * @see https://github.com/illuminate/database/blob/master/Query/Expression.php
 *
 */

namespace Atom;

class Expression {

    protected $value;

    public function __construct($value) {

        $this->value = $value;
    }

    public function getValue() {

        return $this->value;
    }

    public function __toString() {

        return (string) $this->getValue();
    }
}
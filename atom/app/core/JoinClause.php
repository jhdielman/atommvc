<?php

/**
 * AtomMVC: JoinClause Class
 * atom/app/lib/JoinClause.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class JoinClause {

    public $query;

    public $type;

    public $table;

    public $clauses = array();

    public function __construct(QueryBuilder $query, $type, $table) {

        $this->type = $type;
        $this->query = $query;
        $this->table = $table;
    }

    public function on($first, $operator = '=', $second = null, $boolean = 'and', $where = false) {
        if ($first instanceof \Closure) {
            $this->onNested($first, $boolean);
        }
        else {
            $type = 'Join';
            $this->clauses[] = compact('type', 'first', 'operator', 'second', 'boolean', 'where');
        
            if ($where) $this->query->addBinding($second, 'join');
        }

        return $this;
    }
    
    protected function onNested(\Closure $callback, $boolean = 'and') {
        $join = new JoinClause($this->query, $this->type, $this->table);
        
        call_user_func($callback, $join);
        
        $this->clauses[] = [
            'type'      => 'Nested',
            'clauses'   => $join->clauses,
            'boolean'   => $boolean];
    }

    public function orOn($first, $operator = '=', $second = null) {

        return $this->on($first, $operator, $second, 'or');
    }

    public function where($first, $operator = '=', $second = null, $boolean = 'and') {

        return $this->on($first, $operator, $second, $boolean, true);
    }

    public function orWhere($first, $operator = '=', $second = null) {

        return $this->on($first, $operator, $second, 'or', true);
    }
}
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

	public function on($first, $operator, $second, $boolean = 'and', $where = false) {
        
		$this->clauses[] = compact('first', 'operator', 'second', 'boolean', 'where');

		if ($where) $this->query->addBinding($second, 'join');

		return $this;
	}

	public function orOn($first, $operator, $second) {
        
		return $this->on($first, $operator, $second, 'or');
	}

	public function where($first, $operator, $second, $boolean = 'and') {
        
		return $this->on($first, $operator, $second, $boolean, true);
	}

	public function orWhere($first, $operator, $second) {
        
		return $this->on($first, $operator, $second, 'or', true);
	}
}
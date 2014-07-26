<?php

/**
 * AtomMVC: QueryException Class
 * atom/app/lib/QueryException.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 * @see https://github.com/illuminate/database/blob/master/QueryException.php
 * 
 */

namespace Atom;

use PDOException;

class QueryException extends PDOException {

	/**
	 * The SQL for the query.
	 *
	 * @var string
	 */
	protected $sql;

	/**
	 * The bindings for the query.
	 *
	 * @var array
	 */
	protected $bindings;

	/**
	 * Create a new query exception instance.
	 *
	 * @param  string  $sql
	 * @param  array  $bindings
	 * @param  \Exception $previous
	 * @return void
	 */
	public function __construct($sql, array $bindings, $previous) {
		$this->sql = $sql;
		$this->bindings = $bindings;
		$this->previous = $previous;
		$this->code = $previous->getCode();
		$this->message = $this->formatMessage($sql, $bindings, $previous);

		if ($previous instanceof PDOException) {
			$this->errorInfo = $previous->errorInfo;
		}
	}

	/**
	 * Format the SQL error message.
	 *
	 * @param  string  $sql
	 * @param  array  $bindings
	 * @param  \Exception $previous
	 * @return string
	 */
	protected function formatMessage($sql, $bindings, $previous) {
		return $previous->getMessage().' (SQL: '.str_replace_array('\?', $bindings, $sql).')';
	}

	/**
	 * Get the SQL for the query.
	 *
	 * @return string
	 */
	public function getSql() {
		return $this->sql;
	}

	/**
	 * Get the bindings for the query.
	 *
	 * @return array
	 */
	public function getBindings() {
		return $this->bindings;
	}

}
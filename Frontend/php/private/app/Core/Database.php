<?php
namespace Webapp\Core;

/**
 * The Database class used for querying a MYSQL|MariaDB Database
 */
class Database {
	private ?\PDO $connection = null;
	private \PDOStatement $stmt;

	private string $error;
	private ?string $lastError = null;
	private string $sql;
	private array $params = [];
	private ?DBType $limit = null;
	private ?DBType $offset = null;
	private bool $distinct = false;

	private bool $debug = false;
	private bool $debugDie = false;

	private array $operators = [
		'=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
		'like', 'like binary', 'not like', 'ilike',
		'&', '|', '^', '<<', '>>', '&~', 'is', 'is not',
		'rlike', 'not rlike', 'regexp', 'not regexp',
		'~', '~*', '!~', '!~*', 'similar to',
		'not similar to', 'not ilike', '~~*', '!~~*',
		'between', 'not between', 'exists', 'in', 'not in',
	];
	private string $defaultWhereOperator = "=";

	private array $sqlParams = [
		'select' => [],
		'from' => [],
		'join' => [],
		'where' => [],
		'group' => [],
		'having' => [],
		'order' => [],
	];

	/**
	 * Creates a Database object, which is used to execute queries at the database.
	 * 
	 * @param string $dbHost 	(optional) The host name or IP address of the database server.
	 * @param string $dbName 	(optional) The name of the database you want to use.
	 * @param string $dbUser 	(optional) The user to connect to the database server.
	 * @param string $dbPass 	(optional) The password for the user to connect to the database server.
	 * @param string $lastError (optional) The last thrown error.
	 * 
	 * @return Database
	 */
	public function __construct(string $dbHost = null, string $dbName = null, string $dbUser = null, string $dbPass = null, string $lastError = null) {
		$this->lastError = $lastError;
		$dbHost = (is_null($dbHost) ? Config::get('db.host') : $dbHost);
		$dbName = (is_null($dbName) ? Config::get('db.name') : $dbName);
		$dbUser = (is_null($dbUser) ? Config::get('db.user') : $dbUser);
		$dbPass = (is_null($dbPass) ? Config::get('db.password') : $dbPass);
		try {
			if(Config::get('db.active')) {
				$dsn = 'mysql:host='.$dbHost.';dbname='.$dbName.';charset=UTF8;';
				$this->connection = new \PDO($dsn, $dbUser, $dbPass, [
					\PDO::ATTR_PERSISTENT => false,
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				]);
			}
		} catch(\PDOException $pdoE) {
			$this->error = $pdoE->getMessage();
		}
	}

	/**
	 * Creates a new Database object, with the same `from` bindings
	 * 
	 * @return self an instance of the class itself (self).
	 */
	private function newQuery(): self {
		$query = DB::newInstance($this->getError())->table($this->getBindings()['from']);
		return $query;
	}

	/**
	 * Executes the configured query
	 * 
	 * @param array $params (optional) The parameters to be binded using `\PDO`.
	 * @param int 	$mode 	(optional) The type the result should be returned (default: `DB::FETCH_ARRAY`).
	 * 
	 * @return array|false Array with all found records on success, false on failiure.
	 */
	private function execute(array $params = [], int $mode = DB::FETCH_ARRAY): array|false {
		$params = !empty($params) ? $params : $this->params;
		if($this->debug) {
			$dump = [
				'sql' => $this->sql,
				'sqlParsed' => str_replace(array_map(fn($data) => ':'.$data, array_keys($params)), array_map(fn($data) => $data, array_values($params)), $this->sql),
				'distinct' => $this->distinct,
				'select' => $this->getSelects(),
				'from' => $this->getFroms(),
				'join' => $this->getJoins(),
				'where' => $this->getWheres(),
				'order' => $this->getOrders(),
				'group' => $this->getGroups(),
				'having' => $this->getHavings(),
				'limit' => $this->limit,
				'offset' => $this->offset,
				'params' => $params,
			];
			DB::newInstance();
			if($this->debugDie) {
				dd($dump);
			}
			return $dump;
		}
		if($this->connection && !$this->debug) {
			$this->stmt = $this->connection->prepare($this->sql);
			if(!empty($params) && is_countable($params) && count($params)) {
				foreach($params as $key => $param) {
					if(is_int($key)) $key++;
					$this->bind($key, ($param instanceof DBType ? $param->rawValue : $param));
				}
			}
			try {
				$this->stmt->execute();
				if($mode == DB::FETCH_ARRAY) {
					$result = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
				} else {
					$result = $this->stmt->fetchAll(\PDO::FETCH_OBJ);
				}
				DB::newInstance($this->getError());
			} catch (\PDOException $excep) {
				if(Config::get('dev')) throw $excep;
				$this->error = $excep->getMessage();
				DB::newInstance($excep->getMessage());
			}
		}

		return isset($result) ? $result : false;
	}

	/**
	 * Binds a value to the `PDOStatement`
	 * 
	 * @param int|string 	$param 	The placeholder inside the prepared statement.
	 * @param mixed 		$value 	The value for the parameter.
	 * @param int 			$type 	(optional) The type of the value. If not set, it will determine the type automatically.
	 */
	private function bind(int|string $param, mixed $value, int $type = null): void {
		if(is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = \PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = \PDO::PARAM_BOOL;
					break;
				case (is_null($value)):
					$type = \PDO::PARAM_NULL;
					break;
				default:
					$type = \PDO::PARAM_STR;
			}
		}
		$this->stmt->bindValue($param, $value, $type);
	}

	/**
	 * Checks if a string is a valid SQL operator
	 * 
	 * @param string $operator The string to check
	 * 
	 * @return bool true if valid, false if invalid.
	 */
	private function isValidWhereOperator(string $operator = ""): bool {
		return in_array(trim(strtolower($operator)), $this->operators);
	}

	/* QUERY BUILDER */
	/**
	 * Builds the SQL Statement.
	 */
	private function buildQuery(): void {
		$this->sql = $this->buildSelect();
		$this->sql .= $this->buildFrom();
		$this->sql .= $this->buildJoin();
		$this->sql .= $this->buildCondition();
		$this->sql .= $this->buildGroup();
		$this->sql .= $this->buildOrder();
		$this->sql .= $this->buildLimit();
		$this->sql .= $this->buildOffset();
	}
	/**
	 * Builds a SELECT statement in SQL based on the provided `select` bindings.
	 * 
	 * @return string The SELECT statement string.
	 */
	private function buildSelect(): string {
		$sql = "SELECT ".($this->distinct ? "DISTINCT " : "");
		$selects = $this->getSelects();
		if(empty($selects)) {
			$sql .= DBType::column("*");
		} else {
			$subCount = 0;
			foreach($selects as $key => $select) {
				$sql .= ($key == 0 ? "" : ", ");
				if($select->type == _DBType::Sub) {
					$alias = $select->alias;
					($select->rawValue)($tmpQuery = DB::newInstance());
					$subSql = $tmpQuery->toSql();
					$subParams = $tmpQuery->getParams();
					$subParamsNew = array_map(fn($param) => preg_replace("/([\w]+)_([0-9]+)/", '${1}_'.$subCount.'${2}', $param), array_keys($subParams));
					$subSql = str_replace(array_keys($subParams), $subParamsNew, $subSql);
					$sqlParams = [];
					foreach ($subParamsNew as $key => $param) {
						$sqlParams[$param] = array_values($subParams)[$key];
					}
					$this->addParam($sqlParams);
					$sql .= "(".$subSql.")".(!empty($alias) ? " as ".$alias : "");
					$subCount++;
				} else {
					$sql .= $select;
				}
			}
		}

		return $sql;
	}
	/**
	 * Builds a FROM statement in SQL based on the provided `from` bindings.
	 * 
	 * @return string The FROM statement string.
	 */
	private function buildFrom(): string {
		$sql = " FROM";
		$froms = $this->getFroms();
		if(is_string($froms)) {
			$sql .= $froms;
		} else if(is_array($froms)) {
			foreach($this->getFroms() as $key => $from) {

				$sql .= ($key > 0 ? "," : "")." ".$from;
			}
		}

		return $sql;
	}
	/**
	 * Builds a JOIN statement in SQL based on the provided `join` bindings.
	 * 
	 * @return string The JOIN statement string.
	 */
	private function buildJoin(): string {
		$sql = "";
		if(!empty($this->getJoins())) {
			foreach($this->getJoins() as $join) {
				if($join['type'] == "CROSS JOIN") {
					$sql .= " ".$join['type']." ".$join['table'];
				} else {
					$sql .= " ".$join['type']." ".$join['table']." ON ";
					if(count($join['conditions']) != count($join['conditions'], COUNT_RECURSIVE)) {
						foreach($join['conditions'] as $key => $condition) {
							$sql .= ($key == 0 ? "" : " ".$condition['operand']." ");
							$sql .= (isset($condition['prefix']) ? $condition['prefix'] : "");
							$sql .= $condition['column'].$condition['operator'].$condition['value'];
							$sql .= (isset($condition['suffix']) ? $condition['suffix'] : "");
						}
					}
				}
			}
		}

		return $sql;
	}
	/**
	 * Builds a [WHERE|HAVING|JOIN] SQL condition.
	 * 
	 * @param array 	$conditions (optional) an array containing the conditions.
	 * @param string 	$operand 	the operand to join the conditions together.
	 * @param string 	$param 		the type of the condition [WHERE|HAVING|JOIN].
	 * 
	 * @return string The condition statement string.
	 */
	private function buildCondition(array $conditions = null, string $operand = "AND", string $param = "WHERE"): string {
		$sql = "";
		if(is_null($conditions)) {
			$conditions = $this->{"get".ucfirst(strtolower($param))."s"}();
			if(!empty($conditions)) {
				$sql .= " ".strtoupper($param)." ";
				if(is_string($conditions)) {
					$sql .= $conditions;
					return $sql;
				}
			}
		}
		foreach($conditions as $key => $condition) {
			$operand = $condition['operand'];

			if(is_array($condition['value'])) {
				$value = "(".$this->buildCondition($condition['value']).")";
				$operator = "";
				$column = "";
			} else {
				$columnPrefix = $condition['column']->prefix;

				$column = $condition['column'];
				$operator = $condition['operator'];

				$columnValue = preg_replace("/[^a-zA-Z0-9_]/", "", $column->rawValue);
	
				if($condition['value']->type == _DBType::List) {
					$paramsTmp = [];
					$value = "(";
					foreach($condition['value']->rawValue as $paramKey => $paramValue) {
						$paramsTmp[str_replace(".", "_", $columnValue).'_'.count($this->params).$paramKey] = $paramValue->rawValue;
						$value .= ($paramKey == 0 ? "" : ", ").":".str_replace(".", "_", $columnValue).'_'.count($this->params).$paramKey;
					}
					$value .= ")";
					$this->params = array_merge($this->params, $paramsTmp);

				} else if($condition['value']->type == _DBType::Range) {
					$paramsTmp = [];
					foreach($condition['value']->rawValue as $paramKey => $value) {
						$paramsTmp[str_replace(".", "_", $columnValue).'_'.count($this->params).$paramKey] = $value;
					}
					$value = ":".str_replace(".", "_", $columnValue).'_'.count($this->params)."0"." AND ".":".str_replace(".", "_", $columnValue).'_'.count($this->params)."1";
					$this->params = array_merge($this->params, $paramsTmp);
					
				} else if($condition['value']->type == _DBType::Function || $condition['value']->type == _DBType::Column) {
					$value = $condition['value'];

				} else if($condition['value']->type == _DBType::Sub) {
					($condition['value']->rawValue)($query = DB::newInstance());
					$value = "(".$query->toSql().")";
					$this->params = array_merge($this->params, $query->getParams());

				} else if($condition['value']->type == _DBType::Null) {
					$value = $condition['value'];
				} else {
					$value = ':'.str_replace(".", "_", $columnValue)."_".count($this->params);
					$this->params[str_replace(".", "_", $columnValue)."_".count($this->params)] = $condition['value'];
				}
			}

			$sql .= ($key == 0 ? "" : " ".$operand." ");
			$sql .= (!empty($columnPrefix) ? $columnPrefix." " : "");
			$sql .= (!empty($column) ? $column : "");
			$sql .= (!empty($operator) ? $operator : "");
			$sql .= $value;
		}

		return $sql;
	}
	/**
	 * Builds a GROUP BY statement in SQL based on the provided `group` bindings.
	 * 
	 * @return string The GROUP BY statement string.
	 */
	private function buildGroup(): string {
		$sql = "";
		$groups = $this->getGroups();
		if(!empty($groups)) {
			$sql = " GROUP BY ";
			foreach($groups as $key => $group) {
				$sql .= ($key == 0 ? "" : ", ").$group;
				$havings = $this->getHavings();
				if(!empty($havings)) {
					$sql .= " HAVING ".$this->buildCondition($havings, param: "HAVING");
				}
			}
		}

		return $sql;
	}
	/**
	 * It builds the ORDER BY clause of the SQL based on the provided `order` bindings.
	 * 
	 * @return string The GROUP BY statement string.
	 */
	private function buildOrder(): string {
		$sql = "";
		$orders = $this->getOrders();
		if(!empty($orders)) {
			$sql = " ORDER BY ";
			foreach($orders as $key => $order) {
				$sql .= ($key == 0 ? "" : ", ").$order[0]." ".strtoupper($order[1]);
			}
		}

		return $sql;
	}
	/**
	 * It builds the LIMIT clause of the SQL based on the provided `limit` bindings.
	 * 
	 * @return string The LIMIT statement string.
	 */
	private function buildLimit(): string {
		$sql = "";
		if(!empty($this->limit)) {
			$sql .= " LIMIT ".$this->limit;
		}

		return $sql;
	}
	/**
	 * It builds the OFFSET clause of the SQL based on the provided `limit` bindings.
	 * 
	 * @return string The OFFSET statement string.
	 */
	private function buildOffset(): string {
		$sql = "";
		if(!empty($this->limit) && !empty($this->offset)) {
			$sql .= " OFFSET ".$this->offset;
		}

		return $sql;
	}

	/* TRANSACTIONS */
	/**
	 * Starts & Finishes a transaction.
	 *
	 * @param \Closure $fun Must return a boolean to determine if the transaction should be comitted or canceled.
	 * @return void
	 */
	public function transaction(\Closure $fun) {
		$this->begintransaction();
		if($fun($this->newQuery())) {
			$this->endTransaction();
		} else {
			$this->cancelTransaction();
		}
	}
	/**
	 * Begins a transaction.
	 *
	 * @return Database
	 */
	public function beginTransaction(): self {
		$this->connection->begintransaction();
		return $this;
	}
	/**
	 * Commits a transaction.
	 *
	 * @return void
	 */
	public function endTransaction(): void {
		$this->connection->commit();
	}
	/**
	 * Cancels a transaction.
	 *
	 * @return void
	 */
	public function cancelTransaction(): void {
		$this->connection->rollBack();
	}

	/* RAW FUNCTIONS */
	/**
	 * Execute a raw Statement.
	 *
	 * @param string $query
	 * @param array $params
	 * @return array|false
	 */
	public function statement(string $query, array $params = []): array|false {
		$this->sql = $query;
		return $this->execute($params);
	}

	/* BINDING FUNCTIONS */
	/**
	 * Creates a new database connection with the specified database-name.
	 * 
	 * @param string $database The database name.
	 * 
	 * @return self an instance of the class itself (self).
	 */
	public function database(string $database): self {
		return new self(dbName: $database);
	}
	/**
	 * Sets the tablename(s) for the FROM statement part.
	 * @see from
	 * 
	 * @param string|array $table The `$table` parameter can be either an array or a string. If it is an array, it represents multiple table names. If it is a string, it represents the name of a single table.
	 * @param string|array $alias The `$alias` parameter can be either an array or a string. If it is an array, it represents the alias for the corresponding table name at the given array index. This is only the case if the table name is not of type `DBType` at this index. If it is a string, it represents the alias of a single table.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * DB::table('tbl_invoice')->[...];
	 * DB::table('tbl_invoice i')->[...];
	 * DB::table('tbl_invoice as inv')->[...];
	 * DB::table('tbl_invoice', 'rechnung')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] FROM `tbl_invoice` [...]
	 * [...] FROM `tbl_invoice` as i [...]
	 * [...] FROM `tbl_invoice` as i [...]
	 * [...] FROM `tbl_invoice`, `tbl_invoice` as i, `tbl_invoice` as inv [...]
	 * [...] FROM `tbl_invoice` as rechnung [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function table(string|array $table, string|array $alias = ""): self {
		return $this->from($table, $alias);
	}
	/**
	 * Sets the columns for the SELECT statement part.
	 * 
	 * @param string|array|DBType $select The `select` parameter can be of type `array`, `string`, or `DBType`.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->select('id')->[...]
	 * [...]->select('invoice.id')->[...]
	 * [...]->select('db.invoice.id')->[...]
	 * [...]->select('db.invoice.id')->[...]
	 * [...]->select('id, name')->[...]
	 * [...]->select('id', 'invoice_id')->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * SELECT `id` [...]
	 * SELECT `invoice`.`id` [...]
	 * SELECT `db`.`invoice`.`id` [...]
	 * SELECT `id`, `name` [...]
	 * SELECT `id` as invoice_id [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function select(string|array|DBType $select, string|array $alias = ""): self {
		if($select instanceof DBType) {
			$this->addBinding('select', $select);
		} else if(is_array($select)) {
			foreach($select as $selectNameKey => $selectName) {
				if(is_array($selectName)) {
					$this->select($selectName[0], $selectName[1]);
				} else if(is_numeric($selectNameKey)) {
					if(is_array($alias) && ($selectName instanceof DBType)) array_splice($alias, $selectNameKey, 0, "");
					if(is_array($alias) && !empty($alias[$selectNameKey])) $this->addBinding('select', ($selectName instanceof DBType) ? $selectName : DBType::column($selectName, alias: $alias[$selectNameKey]));
					else $this->addBinding('select', ($selectName instanceof DBType) ? $selectName : DBType::column($selectName));
				} else {
					$this->addBinding('select', DBType::column($selectNameKey, alias: $selectName));
				}
			}
		} else {
			if(strpos($select, ",") !== false) {
				$this->select(array_map(fn($val) => trim($val), explode(",", $select)));
			} else {
				$this->addBinding('select', DBType::column($select, alias: $alias));
			}
		}

		return $this;
	}
	/**
	 * Sets the tablename(s) for the FROM statement part.
	 * 
	 * @param string|array $table The `$table` parameter can be either an array or a string. If it is an array, it represents multiple table names. If it is a string, it represents the name of a single table.
	 * @param string|array $alias The `$alias` parameter can be either an array or a string. If it is an array, it represents the alias for the corresponding table name at the given array index. This is only the case if the table name is not of type `DBType` at this index. If it is a string, it represents the alias of a single table.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * 
	 * # Examples
	 * ```php
	 * DB::from('tbl_invoice')->[...];
	 * DB::from('tbl_invoice i')->[...];
	 * DB::from('tbl_invoice as inv')->[...];
	 * DB::from('tbl_invoice', 'rechnung')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] FROM `tbl_invoice` [...]
	 * [...] FROM `tbl_invoice` as i [...]
	 * [...] FROM `tbl_invoice` as i [...]
	 * [...] FROM `tbl_invoice`, `tbl_invoice` as i, `tbl_invoice` as inv [...]
	 * [...] FROM `tbl_invoice` as rechnung [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function from(string|array $table, string|array $alias = ""): self {
		$this->setBinding('from', []);
		if(is_string($table)) {
			$table = DBType::column($table, alias: $alias);
			$this->addBinding('from', $table);
		} else {
			foreach($table as $tableNameKey => $tableName) {
				if(is_array($tableName)) {
					foreach($tableName as $tableNameParams) {
						$this->from($tableNameParams[0], $tableNameParams[1] ?? "");
					}
				} else if(is_numeric($tableNameKey)) {
					if(is_array($alias) && ($tableName instanceof DBType)) array_splice($alias, $tableNameKey, 0, "");
					if(is_array($alias) && !empty($alias[$tableNameKey])) $this->addBinding('from', ($tableName instanceof DBType) ? $tableName : DBType::column($tableName, alias: $alias[$tableNameKey]));
					else $this->addBinding('from', ($tableName instanceof DBType) ? $tableName : DBType::column($tableName));
				} else {
					$this->addBinding('from', DBType::column($tableNameKey, alias: $tableName));
				}
			}
			return $this;
		}

		return $this;
	}
	/**
	 * Start a query by specifying to select distinct.
	 * 
	 * # Example
	 * ```php
	 * [...]->distinct()->[...]
	 * ```
	 * 
	 * Converts to
	 * ```sql
	 * SELECT DISTINCT [...]
	 * ```
	 *
	 * @return self an instance of the class itself (self).
	 */
	public function distinct(): self {
		$this->distinct = true;
		return $this;
	}

	/* BUILDER FUNCTIONS */
	/**
	 * Builds the `join` binding.
	 * 
	 * @param DBType $table The table to join.
	 * @param DBType $param1
	 * @param DBType $param2
	 * @param DBType $param3
	 * @param string $type
	 * @param string $operand
	 * 
	 * @return self an instance of the class itself (self).
	 */
	private function joinBuilder(DBType $table, DBType $param1 = null, DBType $param2 = null, DBType $param3 = null, string $type = "INNER", string $operand = "AND"): self {
		$join = [
			'table' => $table,
			'type' => $type." JOIN",
			'conditions' => []
		];
		if($param1->type == _DBType::Sub) {
			($param1->rawValue)($query = DB::newInstance());
			$conditions = $query->getJoins();
			$conditions[0]['prefix'] = "(";
			$conditions[array_key_last($conditions)]['suffix'] = ")";
			foreach($conditions as $cond) {
				$join['conditions'][] = $cond;
			}
		} else {
			$join['conditions'][] = [
				'operand' => $operand,
				'column' => $param1,
				'operator' => (is_null($param3) ? DBType::operator($this->defaultWhereOperator) : $param2),
				'value' => (is_null($param3) ? $param2 : $param3)
			];
		}
		$this->addBinding('join', $join);
		return $this;
	}
	/**
	 * Generates a join on condition and adds it to the `join` bindings.
	 * 
	 * @param DBType $param1	(optional)
	 * @param DBType $param2 	(optional)
	 * @param DBType $param3 	(optional)
	 * @param string $operand
	 * 
	 * @return self an instance of the class itself (self).
	 */
	private function joinOnBuilder(DBType $param1 = null, DBType $param2 = null, DBType $param3 = null, string $operand = "AND"): self {
		$conditions = [];
		if($param1->type == _DBType::Sub) {
			($param1->rawValue)($query = DB::newInstance());
			$conditions = $query->getJoins();
			$conditions[0]['prefix'] = "(";
			$conditions[array_key_last($conditions)]['suffix'] = ")";
			foreach($conditions as $cond) {
				$this->addBinding('join', $cond);
			}
		} else {
			$conditions = [
				'operand' => $operand,
				'column' => $param1,
				'operator' => (!is_null($param2) && !is_null($param3)) ? $param2 : $this->defaultWhereOperator,
				'value' => (is_null($param3) ? $param2 : $param3)
			];
			$this->addBinding('join', $conditions);
		}

		return $this;
	}
	/**
	 * Builds a condition based on the given `$type`.
	 * 
	 * @param array|DBType 	$param1
	 * @param DBType 		$param2 	(optional)
	 * @param DBType 		$param3 	(optional)
	 * @param string 		$operand	(optional)
	 * @param string 		$type
	 * 
	 * @return array The generated condition.
	 */
	private function conditionBuilder(array|DBType $param1, DBType $param2 = null, DBType $param3 = null, string $operand = "AND", string $type = "where") : array {
		$condition = [
			'operand' => $operand,
			'column' => "",
			'operator' => DBType::operator($this->defaultWhereOperator),
			'value' => ""
		];

		if(is_array($param1)) {
			$condition = [];
			foreach($param1 as $condArray) {
				if($operand == "OR") $this->{"or".ucfirst($type)}($condArray[0], $condArray[1], $condArray[2]);
				else $this->{ucfirst($type)}($condArray[0], $condArray[1], $condArray[2]);
			}
		} else {
			if($param1->type == _DBType::Sub) {
				($param1->rawValue)($query = $this->newQuery());
				$condition['value'] = $query->{"get".ucfirst($type)."s"}();
			} else {
				$condition['column'] = $param1;

				if(!is_null($param2) && is_null($param3)) {
					$condition['value'] = $param2;
				} else {
					$condition['operator'] = $param2;
					$condition['value'] = $param3;
				}
			}
		}

		return $condition;
	}
	/**
	 * Builds the `where` binding.
	 * 
	 * @param array|DBType	$param1
	 * @param DBType		$param2	(optional)
	 * @param DBType		$param3	(optional)
	 * @param string		$operand
	 * 
	 * @return self an instance of the class itself (self).
	 */
	private function whereBuilder(array|DBType $param1, DBType $param2 = null, DBType $param3 = null, string $operand = "AND"): self {
		$condition = $this->conditionBuilder($param1, $param2, $param3, $operand);
		if(!empty($condition)) $this->addBinding('where', $condition);
		return $this;
	}
	/**
	 * Builds the `having` binding.
	 * 
	 * @param array|DBType	$param1
	 * @param DBType		$param2	(optional)
	 * @param DBType		$param3	(optional)
	 * @param string 		$operand
	 * 
	 * @return self an instance of the class itself (self).
	 */
	private function havingBuilder(array|DBType $param1, DBType $param2 = null, DBType $param3 = null, string $operand = "AND"): self {
		$condition = $this->conditionBuilder($param1, $param2, $param3, $operand, "having");
		if(!empty($condition)) $this->addBinding('having', $condition);
		return $this;
	}

	/* WHERE FUNCTIONS */
	/**
	 * Creates a WHERE Statement part.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2 (optional)
	 * @param string|int|DBType|\Closure	$param3 (optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->where('id', 1)->[...];
	 * [...]->where('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->where('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->where(function($query) {
	 * 		$query->where('id', 10)
	 * 		->orWhere('id', 15)
	 * })->[...];
	 * [...]->where('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `id`=1 [...]
	 * [...] WHERE [AND] `id`[=,>,<,>=,<=]1 [...]
	 * [...] WHERE [AND] `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] WHERE [AND] (`id`=10 OR `id`=15) [...]
	 * [...] WHERE [AND] `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function where(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2 = null, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1);
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else if(!is_null($param2) && !($param2 instanceof DBType)) $param2 = (is_string($param2) ? DBType::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3) && !($param3 instanceof DBType)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->whereBuilder($param1, $param2, $param3);
	}
	/**
	 * Creates a WHERE Statement part.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2 (optional)
	 * @param string|int|DBType|\Closure	$param3 (optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhere('id', 1)->[...];
	 * [...]->orWhere('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->orWhere('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->orWhere(function($query) {
	 * 		$query->orWhere('id', 10)
	 * 		->orWhere('id', 15)
	 * })->[...];
	 * [...]->orWhere('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `id`=1 [...]
	 * [...] WHERE [OR] `id`[=,>,<,>=,<=]1 [...]
	 * [...] WHERE [OR] `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] WHERE [OR] (`id`=10 OR `id`=15) [...]
	 * [...] WHERE [OR] `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function orWhere(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2 = null, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1);
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else if(!is_null($param2)) $param2 = (is_string($param2) ? DBType::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3) && !($param3 instanceof DBType)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->whereBuilder($param1, $param2, $param3, "OR");
	}
	/**
	 * Creates a WHERE Statement part. This function inverts the condition using the `NOT` operand.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2
	 * @param string|int|DBType|\Closure	$param3	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereNot('id', 1)->[...];
	 * [...]->whereNot('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->whereNot('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->whereNot(function($query) {
	 * 		$query->whereNot('id', 10)
	 * 		->orWhere('id', 15)
	 * })->[...];
	 * [...]->whereNot('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] NOT `id`=1 [...]
	 * [...] WHERE [AND] NOT `id`[=,>,<,>=,<=]1 [...]
	 * [...] WHERE [AND] NOT `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] WHERE [AND] NOT (`id`=10 OR `id`=15) [...]
	 * [...] WHERE [AND] NOT `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function whereNot(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1, "NOT");
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1, "NOT");
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else $param2 = (is_string($param2) ? DB::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3) && !($param3 instanceof DBType)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->whereBuilder($param1, $param2, $param3);
	}
	/**
	 * Creates a WHERE Statement part. This function inverts the condition using the `NOT` operand.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1 
	 * @param string|int|DBType|\Closure	$param2 
	 * @param string|int|DBType|\Closure	$param3	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereNot('id', 1)->[...];
	 * [...]->orWhereNot('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->orWhereNot('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->orWhereNot(function($query) {
	 * 		$query->orWhereNot('id', 10)
	 * 		->orWhere('id', 15)
	 * })->[...];
	 * [...]->orWhereNot('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] NOT `id`=1 [...]
	 * [...] WHERE [OR] NOT `id`[=,>,<,>=,<=]1 [...]
	 * [...] WHERE [OR] NOT `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] WHERE [OR] NOT (`id`=10 OR `id`=15) [...]
	 * [...] WHERE [OR] NOT `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function orWhereNot(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1, "NOT");
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1, "NOT");
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else $param2 = (is_string($param2) ? DBType::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3) && !($param3 instanceof DBType)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->whereBuilder($param1, $param2, $param3, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value is between two given values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param array			$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereBetween('id', [10, 15])->[...];
	 * [...]->whereBetween(DBType::function('AVG', 'price'), [0, 100])->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `id` BETWEEN 10 AND 15 [...]
	 * [...] WHERE [AND] AVG(`price`) BETWEEN 0 AND 100 [...]
	 * ```
	 */
	public function whereBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->whereBuilder($param1, DBType::operator(" BETWEEN "), DBType::range($param2));
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value isn't between two given values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param array			$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereNotBetween('id', [10, 15])->[...];
	 * [...]->whereNotBetween(DBType::function('AVG', 'price'), [0, 100])->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `id` NOT BETWEEN 10 AND 15 [...]
	 * [...] WHERE [AND] AVG(`price`) NOT BETWEEN 0 AND 100 [...]
	 * ```
	 */
	public function whereNotBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->whereBuilder($param1, DBType::operator(" NOT BETWEEN "), DBType::range($param2));
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value is between two given values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1 
	 * @param array			$param2 
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * ### Example
	 * 
	 * ```php
	 * ->orWhereBetween('column', [1, 100]);
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [...] [OR] \`column\` BETWEEN 1 AND 100
	 * ```
	 */
	public function orWhereBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->whereBuilder($param1, DBType::operator(" BETWEEN "), DBType::range($param2), "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value isn't between two given values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1 
	 * @param array			$param2 
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereNotBetween('id', [10, 15])->[...];
	 * [...]->orWhereNotBetween(DBType::function('AVG', 'price'), [0, 100])->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `id` NOT BETWEEN 10 AND 15 [...]
	 * [...] WHERE [OR] AVG(`price`) NOT BETWEEN 0 AND 100 [...]
	 * ```
	 */
	public function orWhereNotBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->whereBuilder($param1, DBType::operator(" NOT BETWEEN "), DBType::range($param2), "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value is in a list of values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->whereIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `id` IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] WHERE [AND] `id` IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function whereIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_numeric($data)) $data = DBType::numeric($data);
				else $data = DBType::text($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->whereBuilder($param1, DBType::operator(" IN "), $param2);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value isn't in a list of values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereNotIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->whereIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `id` NOT IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] WHERE [AND] `id` NOT IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function whereNotIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->whereBuilder($param1, DBType::operator(" NOT IN "), $param2);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value is in a list of values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->orWhereIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `id` IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] WHERE [OR] `id` IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function orWhereIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->whereBuilder($param1, DBType::operator(" IN "), $param2, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if column
	 * value isn't in a list of values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereNotIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->orWhereNotIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `id` NOT IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] WHERE [OR] `id` NOT IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function orWhereNotIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->whereBuilder($param1, DBType::operator(" NOT IN "), $param2, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a column is null.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereNull('customer_id')->[...];
	 * [...]->whereNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `customer_id` IS NULL [...]
	 * [...] WHERE [AND] CONCAT(`firstname`, ' ', `lastname`) IS NULL [...]
	 * ```
	 */
	public function whereNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->whereBuilder($column, DBTYPE::operator(" IS "), DBType::null());
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a column isn't null.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereNotNull('customer_id')->[...];
	 * [...]->whereNotNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `customer_id` IS NOT NULL [...]
	 * [...] WHERE [AND] CONCAT(`firstname`, ' ', `lastname`) IS NOT NULL [...]
	 * ```
	 */
	public function whereNotNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->whereBuilder($column, DBTYPE::operator(" IS NOT "), DBType::null());
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a column is null
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereNull('customer_id')->[...];
	 * [...]->orWhereNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `customer_id` IS NULL [...]
	 * [...] WHERE [OR] CONCAT(`firstname`, ' ', `lastname`) IS NULL [...]
	 * ```
	 */
	public function orWhereNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->whereBuilder($column, DBTYPE::operator(" IS "), DBType::null(), "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a column isn't null
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereNotNull('customer_id')->[...];
	 * [...]->orWhereNotNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `customer_id` IS NOT NULL [...]
	 * [...] WHERE [OR] CONCAT(`firstname`, ' ', `lastname`) IS NOT NULL [...]
	 * ```
	 */
	public function orWhereNotNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->whereBuilder($column, DBTYPE::operator(" IS NOT "), DBType::null(), "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `UNIX_TIMESTAMP`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereDate('column', '24.11.2023 00:00:00')->[...];
	 * [...]->whereDate('column', [=|<|>|<=|>=], '24.11.2023 00:00:00')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] UNIX_TIMESTAMP(`column`)=1732402800 [...]
	 * [...] WHERE [AND] UNIX_TIMESTAMP(`column`)[=|<|>|<=|>=]1732402800 [...]
	 * ```
	 */
	public function whereDate(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("unix_timestamp", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->getTimestamp());
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((is_string($dateOrOperator) ? strtotime($dateOrOperator) : $dateOrOperator));

		if($date instanceof \DateTime) $date = DBType::numeric($date->getTimestamp());
		else if(!is_null($date)) $date = DBType::numeric((is_string($date) ? strtotime($date) : $date));

		return $this->whereBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `UNIX_TIMESTAMP`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereDate('column', '24.11.2023 00:00:00')->[...];
	 * [...]->whereDate('column', [=|<|>|<=|>=], '24.11.2023 00:00:00')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] UNIX_TIMESTAMP(`column`)=1732402800 [...]
	 * [...] WHERE [AND] UNIX_TIMESTAMP(`column`)[=|<|>|<=|>=]1732402800 [...]
	 * ```
	 */
	public function orWhereDate(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("unix_timestamp", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->getTimestamp());
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((is_string($dateOrOperator) ? strtotime($dateOrOperator) : $dateOrOperator));

		if($date instanceof \DateTime) $date = DBType::numeric($date->getTimestamp());
		else if(!is_null($date)) $date = DBType::numeric((is_string($date) ? strtotime($date) : $date));

		return $this->whereBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `YEAR`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereYear('column', 2023)->[...];
	 * [...]->whereYear('column', [=|<|>|<=|>=], 2023)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] YEAR(`column`)=2023 [...]
	 * [...] WHERE [AND] YEAR(`column`)[=|<|>|<=|>=]2023 [...]
	 * ```
	 */
	public function whereYear(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("YEAR", DBType::column($column));

		if(is_string($dateOrOperator) &&$this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("Y"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("Y"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->whereBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `YEAR`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereYear('column', 2023)->[...];
	 * [...]->orWhereYear('column', [=|<|>|<=|>=], 2023)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] YEAR(`column`)=2023 [...]
	 * [...] WHERE [OR] YEAR(`column`)[=|<|>|<=|>=]2023 [...]
	 * ```
	 */
	public function orWhereYear(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("YEAR", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("Y"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("Y"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->whereBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `MONTH`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereMonth('column', 11)->[...];
	 * [...]->whereMonth('column', [=|<|>|<=|>=], 11)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] MONTH(`column`)=11 [...]
	 * [...] WHERE [AND] MONTH(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function whereMonth(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("MONTH", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("m"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("m"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->whereBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `MONTH`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereMonth('column', 11)->[...];
	 * [...]->orWhereMonth('column', [=|<|>|<=|>=], 11)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] MONTH(`column`)=11 [...]
	 * [...] WHERE [OR] MONTH(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function orWhereMonth(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("MONTH", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("m"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("m"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->whereBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `DAY`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereDay('column', 24)->[...];
	 * [...]->whereDay('column', [=|<|>|<=|>=], 24)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] DAY(`column`)=11 [...]
	 * [...] WHERE [AND] DAY(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function whereDay(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("DAY", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("d"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("d"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->whereBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if the `DAY`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereDay('column', 24)->[...];
	 * [...]->whereDay('column', [=|<|>|<=|>=], 24)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] DAY(`column`)=11 [...]
	 * [...] WHERE [AND] DAY(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function orWhereDay(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("DAY", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("d"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("d"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->whereBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if two columns match.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|array|DBType	$param1
	 * @param string|DBType			$param2		(optional)
	 * @param string|DBType			$param3 	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereColumn('column1', 'column2')->[...];
	 * [...]->whereColumn('column1', [=|<|>|<=|>=], 'column2')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `column1`=`column2` [...]
	 * [...] WHERE [AND] `column1`[=|<|>|<=|>=]`column2` [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function whereColumn(string|array|DBType $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);

		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if(!$param2 instanceof DBType) $param2 = DBType::column($param2);

		if(is_string($param3)) $param3 = DBType::column($param3);

		return $this->whereBuilder($param1, $param2, $param3);
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if two columns match.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|array|DBType	$param1
	 * @param string|DBType			$param2		(optional)
	 * @param string|DBType			$param3 	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereColumn('column1', 'column2')->[...];
	 * [...]->orWhereColumn('column1', [=|<|>|<=|>=], 'column2')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `column1`=`column2` [...]
	 * [...] WHERE [OR] `column1`[=|<|>|<=|>=]`column2` [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function orWhereColumn(string|array|DBType $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);

		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if(!$param2 instanceof DBType) $param2 = DBType::column($param2);

		if(is_string($param3)) $param3 = DBType::column($param3);

		return $this->whereBuilder($param1, $param2, $param3, "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a string can be found
	 * inside a column.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `column` LIKE '%search%' [...]
	 * ```
	 */
	public function whereLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->whereBuilder($param1, DBType::operator(" LIKE "), DBType::text($param2));
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a string can be found
	 * inside a column.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `column` LIKE '%search%' [...]
	 * ```
	 */
	public function orWhereLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->whereBuilder($param1, DBType::operator(" LIKE "), DBType::text($param2), "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a string can not be found
	 * inside a column using the MYSQL `NOT` operand.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->whereNotLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] `column` NOT LIKE '%search%' [...]
	 * ```
	 */
	public function whereNotLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->whereBuilder($param1, DBType::operator(" NOT LIKE "), DBType::text($param2));
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a string can not be found
	 * inside a column using the MYSQL `NOT` operand.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orWhereNotLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] `column` NOT LIKE '%search%' [...]
	 * ```
	 */
	public function orWhereNotLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->whereBuilder($param1, DBType::operator(" NOT LIKE "), DBType::text($param2), "OR");
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a given subquery exists
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param \Closure	$fun
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Example
	 * ```php
	 * [...]->whereExists(function($query) {
	 * 		$query->from('table')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [AND] EXISTS (SELECT * FROM `table` WHERE `id`=10) [...]
	 * ```
	 */
	public function whereExists(\Closure $fun): self {
		return $this->whereBuilder(DBType::column(""), DBType::operator("EXISTS "), DBType::sub($fun));
	}
	/**
	 * Creates a WHERE Statement part. The added condition checks if a given subquery exists
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param \Closure	$fun
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Example
	 * ```php
	 * [...]->orWhereExists(function($query) {
	 * 		$query->from('table')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [OR] EXISTS (SELECT * FROM `table` WHERE `id`=10) [...]
	 * ```
	 */
	public function orWhereExists(\Closure $fun): self {
		return $this->whereBuilder(DBType::column(""), DBType::operator("EXISTS "), DBType::sub($fun));
	}

	/* ORDER FUNCTIONS */
	/**
	 * Creates a ORDER BY Statement part.
	 * 
	 * @param string|array|DBType	$column	
	 * @param string 				$direction	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orderBy('id')->[...];
	 * [...]->orderBy('id', 'DESC')->[...];
	 * [...]->orderBy(['id', ['name', 'ASC']])->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] ORDER BY `id` ASC [...]
	 * [...] ORDER BY `id` DESC [...]
	 * [...] ORDER BY `id` ASC, `name` ASC [...]
	 * ```
	 */
	public function orderBy(string|array|DBType $column, string $direction = "ASC"): self {
		if(is_string($column)) {
			$this->addBinding('order', [DBType::column($column), $direction]);
		} else if(is_array($column)) {
			foreach($column as $order) {
				$orderBinding = [is_array($order) ? ($order[0] instanceof DBType ? $order[0] : DBType::column($order[0])) : ($order instanceof DBType ? $order : DBType::column($order))];
				$orderBinding[] = is_array($order) && ($order[1]) ? $order[1] : $direction;
				$this->addBinding('order', $orderBinding);
			}
		} else {
			$this->addBinding('order', [$column, $direction]);
		}

		return $this;
	}
	/**
	 * Clears the ORDER BY statement part or sets the new order with the given parameters.
	 * 
	 * @param string|array	$param1
	 * @param string 		$param2	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 */
	public function reorder(string|array $param1 = null, string $param2 = "ASC"): self {
		$this->clearBinding('order');
		if(!is_null($param1)) {
			return $this->orderBy($param1, $param2);
		}
		return $this;
	}
	/**
	 * Adds a random order binding to `order`.
	 * 
	 * @param int $seed (optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Example
	 * ```php
	 * [...]->inRandomOrder()->[...]
	 * [...]->inRandomOrder(25565)->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] ORDER BY RAND() [...]
	 * [...] ORDER BY RAND(25565) [...]
	 * ```
	 */
	public function inRandomOrder(int $seed = null): self {
		$this->addBinding('order', [DBType::function("rand", (!is_null($seed) ? DBType::numeric($seed) : "")), "ASC"]);
		return $this;
	}

	/* GROUP FUNCTIONS */
	/**
	 * Creates a GROUP BY Statement part.
	 * 
	 * @param string|array|DBType	$column
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->groupBy('id')->[...];
	 * [...]->groupBy(['id', 'name'])->[...];
	 * [...]->groupBy(DBType::column('id'))->[...];
	 * [...]->groupBy(['id', DBType::column('name')])->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] GROUP BY `id` [...]
	 * [...] GROUP BY `id`, `name` [...]
	 * [...] GROUP BY `id` [...]
	 * [...] GROUP BY `id`, `name` [...]
	 * ```
	 */
	public function groupBy(string|array|DBType $column): self {
		if(is_string($column)) {
			$this->addBinding('group', DBType::column($column));
		} else if(is_array($column)) {
			$orderBinding = [];
			foreach($column as $order) {
				$orderBinding[] = ($order instanceof DBType ? $order : DBType::column($order));
			}
			if(!empty($orderBinding)) $this->addBinding('group', $orderBinding);
		} else {
			$this->addBinding('group', $column);
		}

		return $this;
	}

	/* HAVING FUNCTIONS */
	/**
	 * Creates a HAVING Statement part.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2 (optional)
	 * @param string|int|DBType|\Closure	$param3 (optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->having('id', 1)->[...];
	 * [...]->having('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->having('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->having(function($query) {
	 * 		$query->having('id', 10)
	 * 		->orHaving('id', 15)
	 * })->[...];
	 * [...]->having('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->having('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `id`=1 [...]
	 * [...] HAVING [AND] `id`[=,>,<,>=,<=]1 [...]
	 * [...] HAVING [AND] `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] HAVING [AND] (`id`=10 OR `id`=15) [...]
	 * [...] HAVING [AND] `id`=(SELECT `number` FROM tbl_a HAVING `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function having(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2 = null, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1);
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else if(!is_null($param2) && !($param2 instanceof DBType)) $param2 = (is_string($param2) ? DBType::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3) && !($param3 instanceof DBType)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->havingBuilder($param1, $param2, $param3);
	}
	/**
	 * Creates a HAVING Statement part.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2 (optional)
	 * @param string|int|DBType|\Closure	$param3 (optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHaving('id', 1)->[...];
	 * [...]->orHaving('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->orHaving('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->orHaving(function($query) {
	 * 		$query->orHaving('id', 10)
	 * 		->orHaving('id', 15)
	 * })->[...];
	 * [...]->orHaving('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `id`=1 [...]
	 * [...] HAVING [OR] `id`[=,>,<,>=,<=]1 [...]
	 * [...] HAVING [OR] `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] HAVING [OR] (`id`=10 OR `id`=15) [...]
	 * [...] HAVING [OR] `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function orHaving(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2 = null, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1);
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else if(!is_null($param2)) $param2 = (is_string($param2) ? DBType::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->havingBuilder($param1, $param2, $param3, "OR");
	}
	/**
	 * Creates a HAVING Statement part. This function inverts the condition using the `NOT` operand.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2
	 * @param string|int|DBType|\Closure	$param3	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingNot('id', 1)->[...];
	 * [...]->havingNot('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->havingNot('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->havingNot(function($query) {
	 * 		$query->havingNot('id', 10)
	 * 		->orHaving('id', 15)
	 * })->[...];
	 * [...]->havingNot('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] NOT `id`=1 [...]
	 * [...] HAVING [AND] NOT `id`[=,>,<,>=,<=]1 [...]
	 * [...] HAVING [AND] NOT `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] HAVING [AND] NOT (`id`=10 OR `id`=15) [...]
	 * [...] HAVING [AND] NOT `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function havingNot(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1, "NOT");
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1, "NOT");
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else $param2 = (is_string($param2) ? DB::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->havingBuilder($param1, $param2, $param3);
	}
	/**
	 * Creates a HAVING Statement part. This function inverts the condition using the `NOT` operand.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|array|DBType|\Closure	$param1
	 * @param string|int|DBType|\Closure	$param2
	 * @param string|int|DBType|\Closure	$param3	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingNot('id', 1)->[...];
	 * [...]->orHavingNot('id', [=|<|>|<=|>=], 1)->[...];
	 * [...]->orHavingNot('id', [=|<|>|<=|>=], DBType::function('sum', 'somefield'))->[...];
	 * [...]->orHavingNot(function($query) {
	 * 		$query->orHavingNot('id', 10)
	 * 		->orHaving('id', 15)
	 * })->[...];
	 * [...]->orHavingNot('id', ['='|'<'|'>'|'<='|'>='], function($query) {
	 * 		$query->select('number')->table('tbl_a')->where('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] NOT `id`=1 [...]
	 * [...] HAVING [OR] NOT `id`[=,>,<,>=,<=]1 [...]
	 * [...] HAVING [OR] NOT `id`[=|<|>|<=|>=]SUM(`somefield`) [...]
	 * [...] HAVING [OR] NOT (`id`=10 OR `id`=15) [...]
	 * [...] HAVING [OR] NOT `id`=(SELECT `number` FROM tbl_a WHERE `id`=10) [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function orHavingNot(string|array|DBType|\Closure $param1, string|int|DBType|\Closure $param2, string|int|DBType|\Closure $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1, "NOT");
		if($param1 instanceof \Closure) $param1 = DBType::sub($param1, "NOT");
		
		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else $param2 = (is_string($param2) ? DBType::text($param2) : DBType::numeric($param2));

		if($param3 instanceof \Closure) $param3 = DBType::sub($param3);
		else if(!is_null($param3)) $param3 = (is_string($param3) ? DBType::text($param3) : DBType::numeric($param3));

		return $this->havingBuilder($param1, $param2, $param3, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value is between two given values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param array			$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingBetween('id', [10, 15])->[...];
	 * [...]->havingBetween(DBType::function('AVG', 'price'), [0, 100])->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `id` BETWEEN 10 AND 15 [...]
	 * [...] HAVING [AND] AVG(`price`) BETWEEN 0 AND 100 [...]
	 * ```
	 */
	public function havingBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->havingBuilder($param1, DBType::operator(" BETWEEN "), DBType::range($param2));
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value isn't between two given values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param array			$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingNotBetween('id', [10, 15])->[...];
	 * [...]->havingNotBetween(DBType::function('AVG', 'price'), [0, 100])->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `id` NOT BETWEEN 10 AND 15 [...]
	 * [...] HAVING [AND] AVG(`price`) NOT BETWEEN 0 AND 100 [...]
	 * ```
	 */
	public function havingNotBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->havingBuilder($param1, DBType::operator(" NOT BETWEEN "), DBType::range($param2));
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value is between two given values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param array			$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * ### Example
	 * 
	 * ```php
	 * ->orHavingBetween('column', [1, 100]);
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] WHERE [...] [OR] \`column\` BETWEEN 1 AND 100
	 * ```
	 */
	public function orHavingBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->havingBuilder($param1, DBType::operator(" BETWEEN "), DBType::range($param2), "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value isn't between two given values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param array			$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingNotBetween('id', [10, 15])->[...];
	 * [...]->orHavingNotBetween(DBType::function('AVG', 'price'), [0, 100])->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `id` NOT BETWEEN 10 AND 15 [...]
	 * [...] HAVING [OR] AVG(`price`) NOT BETWEEN 0 AND 100 [...]
	 * ```
	 */
	public function orHavingNotBetween(string|DBType $param1, array $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		$param2 = array_map(function($data) {
			if(is_string($data)) $data = DBType::text($data);
			else $data = DBType::numeric($data);
			return $data;
		}, $param2);

		return $this->havingBuilder($param1, DBType::operator(" NOT BETWEEN "), DBType::range($param2), "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value is in a list of values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->havingIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `id` IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] HAVING [AND] `id` IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function havingIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->havingBuilder($param1, DBType::operator(" IN "), $param2);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value isn't in a list of values.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingNotIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->havingIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `id` NOT IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] HAVING [AND] `id` NOT IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function havingNotIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->havingBuilder($param1, DBType::operator(" NOT IN "), $param2);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value is in a list of values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->orHavingIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `id` IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] HAVING [OR] `id` IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function orHavingIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->havingBuilder($param1, DBType::operator(" IN "), $param2, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if column
	 * value isn't in a list of values.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$param1 	The column to check or a `DBType::function`
	 * @param array|\Closure	$param2 	An array of values or a `\Closure` to check a subquery
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingNotIn('id', [1,2,3,4,5,6,9])->[...];
	 * [...]->orHavingNotIn('id', function($query) {
	 * 		$query->select('id')->from('table');
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `id` NOT IN (1, 2, 3, 4, 5, 6, 9) [...]
	 * [...] HAVING [OR] `id` NOT IN (SELECT `id` FROM `table`) [...]
	 * ```
	 */
	public function orHavingNotIn(string|DBType $param1, array|\Closure $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		
		if($param2 instanceof \Closure) $param2 = DBType::sub($param2);
		else {
			$param2 = array_map(function($data) {
				if(is_string($data)) $data = DBType::text($data);
				else $data = DBType::numeric($data);
				return $data;
			}, $param2);
			$param2 = DBType::list($param2);
		}

		return $this->havingBuilder($param1, DBType::operator(" NOT IN "), $param2, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a column is null.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingNull('customer_id')->[...];
	 * [...]->havingNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `customer_id` IS NULL [...]
	 * [...] HAVING [AND] CONCAT(`firstname`, ' ', `lastname`) IS NULL [...]
	 * ```
	 */
	public function havingNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->havingBuilder($column, DBTYPE::operator(" IS "), DBType::null());
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a column isn't null.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingNotNull('customer_id')->[...];
	 * [...]->havingNotNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `customer_id` IS NOT NULL [...]
	 * [...] HAVING [AND] CONCAT(`firstname`, ' ', `lastname`) IS NOT NULL [...]
	 * ```
	 */
	public function havingNotNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->havingBuilder($column, DBTYPE::operator(" IS NOT "), DBType::null());
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a column is null
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingNull('customer_id')->[...];
	 * [...]->orHavingNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `customer_id` IS NULL [...]
	 * [...] HAVING [OR] CONCAT(`firstname`, ' ', `lastname`) IS NULL [...]
	 * ```
	 */
	public function orHavingNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->havingBuilder($column, DBTYPE::operator(" IS "), DBType::null(), "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a column isn't null
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType		$column 	The column to check or a `DBType::function`
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingNotNull('customer_id')->[...];
	 * [...]->orHavingNotNull(DBType::function('concat', ['firstname', ' ', 'lastname]))->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `customer_id` IS NOT NULL [...]
	 * [...] HAVING [OR] CONCAT(`firstname`, ' ', `lastname`) IS NOT NULL [...]
	 * ```
	 */
	public function orHavingNotNull(string|DBType $column): self {
		if(is_string($column)) $column = DBType::column($column);
		return $this->havingBuilder($column, DBTYPE::operator(" IS NOT "), DBType::null(), "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `UNIX_TIMESTAMP`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingDate('column', '24.11.2023 00:00:00')->[...];
	 * [...]->havingDate('column', [=|<|>|<=|>=], '24.11.2023 00:00:00')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] UNIX_TIMESTAMP(`column`)=1732402800 [...]
	 * [...] HAVING [AND] UNIX_TIMESTAMP(`column`)[=|<|>|<=|>=]1732402800 [...]
	 * ```
	 */
	public function havingDate(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("unix_timestamp", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->getTimestamp());
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((is_string($dateOrOperator) ? strtotime($dateOrOperator) : $dateOrOperator));

		if($date instanceof \DateTime) $date = DBType::numeric($date->getTimestamp());
		else if(!is_null($date)) $date = DBType::numeric((is_string($date) ? strtotime($date) : $date));

		return $this->havingBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `UNIX_TIMESTAMP`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingDate('column', '24.11.2023 00:00:00')->[...];
	 * [...]->havingDate('column', [=|<|>|<=|>=], '24.11.2023 00:00:00')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] UNIX_TIMESTAMP(`column`)=1732402800 [...]
	 * [...] HAVING [AND] UNIX_TIMESTAMP(`column`)[=|<|>|<=|>=]1732402800 [...]
	 * ```
	 */
	public function orHavingDate(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("unix_timestamp", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->getTimestamp());
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((is_string($dateOrOperator) ? strtotime($dateOrOperator) : $dateOrOperator));

		if($date instanceof \DateTime) $date = DBType::numeric($date->getTimestamp());
		else if(!is_null($date)) $date = DBType::numeric((is_string($date) ? strtotime($date) : $date));

		return $this->havingBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `YEAR`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingYear('column', 2023)->[...];
	 * [...]->havingYear('column', [=|<|>|<=|>=], 2023)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] YEAR(`column`)=2023 [...]
	 * [...] HAVING [AND] YEAR(`column`)[=|<|>|<=|>=]2023 [...]
	 * ```
	 */
	public function havingYear(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("YEAR", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("Y"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("Y"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->havingBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `YEAR`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingYear('column', 2023)->[...];
	 * [...]->orHavingYear('column', [=|<|>|<=|>=], 2023)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] YEAR(`column`)=2023 [...]
	 * [...] HAVING [OR] YEAR(`column`)[=|<|>|<=|>=]2023 [...]
	 * ```
	 */
	public function orHavingYear(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("YEAR", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("Y"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("Y"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->havingBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `MONTH`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingMonth('column', 11)->[...];
	 * [...]->havingMonth('column', [=|<|>|<=|>=], 11)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] MONTH(`column`)=11 [...]
	 * [...] HAVING [AND] MONTH(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function havingMonth(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("MONTH", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("m"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("m"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->havingBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `MONTH`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingMonth('column', 11)->[...];
	 * [...]->orHavingMonth('column', [=|<|>|<=|>=], 11)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] MONTH(`column`)=11 [...]
	 * [...] HAVING [OR] MONTH(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function orHavingMonth(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("MONTH", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("m"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("m"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->havingBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `DAY`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingDay('column', 24)->[...];
	 * [...]->havingDay('column', [=|<|>|<=|>=], 24)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] DAY(`column`)=11 [...]
	 * [...] HAVING [AND] DAY(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function havingDay(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("DAY", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("d"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("d"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->havingBuilder($column, $dateOrOperator, $date);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if the `DAY`
	 * of a column matches the value.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType					$column 			The column to check or a `DBType::function`
	 * @param string|int|DBType|\DateTime	$dateOrOperator
	 * @param string|int|DBType|\DateTime	$date
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingDay('column', 24)->[...];
	 * [...]->havingDay('column', [=|<|>|<=|>=], 24)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] DAY(`column`)=11 [...]
	 * [...] HAVING [AND] DAY(`column`)[=|<|>|<=|>=]11 [...]
	 * ```
	 */
	public function orHavingDay(string|DBType $column, string|int|DBType|\DateTime $dateOrOperator, string|int|DBType|\DateTime $date = null): self {
		if(is_string($column)) $column = DBType::function("DAY", DBType::column($column));

		if(is_string($dateOrOperator) && $this->isValidWhereOperator($dateOrOperator)) $dateOrOperator = DBType::operator($dateOrOperator);
		else if($dateOrOperator instanceof \DateTime) $dateOrOperator = DBType::numeric($dateOrOperator->format("d"));
		else if(!$dateOrOperator instanceof DBType) $dateOrOperator = DBType::numeric((int) $dateOrOperator);

		if($date instanceof \DateTime) $date = DBType::numeric($date->format("d"));
		else if(!is_null($date)) $date = DBType::numeric((int) $date);

		return $this->havingBuilder($column, $dateOrOperator, $date, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if two columns match.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|array|DBType	$param1
	 * @param string|DBType			$param2		(optional)
	 * @param string|DBType			$param3 	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingColumn('column1', 'column2')->[...];
	 * [...]->havingColumn('column1', [=|<|>|<=|>=], 'column2')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `column1`=`column2` [...]
	 * [...] HAVING [AND] `column1`[=|<|>|<=|>=]`column2` [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function havingColumn(string|array|DBType $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);

		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if(!$param2 instanceof DBType) $param2 = DBType::column($param2);

		if(is_string($param3)) $param3 = DBType::column($param3);

		return $this->havingBuilder($param1, $param2, $param3);
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if two columns match.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|array|DBType	$param1
	 * @param string|DBType			$param2		(optional)
	 * @param string|DBType			$param3 	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingColumn('column1', 'column2')->[...];
	 * [...]->orHavingColumn('column1', [=|<|>|<=|>=], 'column2')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `column1`=`column2` [...]
	 * [...] HAVING [OR] `column1`[=|<|>|<=|>=]`column2` [...]
	 * ```
	 * 
	 * The string parameters can also be added to an array.
	 */
	public function orHavingColumn(string|array|DBType $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		if(is_string($param1)) $param1 = DBType::column($param1);

		if(is_string($param2) && $this->isValidWhereOperator($param2)) $param2 = DBType::operator($param2);
		else if(!$param2 instanceof DBType) $param2 = DBType::column($param2);

		if(is_string($param3)) $param3 = DBType::column($param3);

		return $this->havingBuilder($param1, $param2, $param3, "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a string can be found
	 * inside a column.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `column` LIKE '%search%' [...]
	 * ```
	 */
	public function havingLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->havingBuilder($param1, DBType::operator(" LIKE "), DBType::text($param2));
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a string can be found
	 * inside a column.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `column` LIKE '%search%' [...]
	 * ```
	 */
	public function orHavingLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->havingBuilder($param1, DBType::operator(" LIKE "), DBType::text($param2), "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a string can not be found
	 * inside a column using the MYSQL `NOT` operand.
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->havingNotLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] `column` NOT LIKE '%search%' [...]
	 * ```
	 */
	public function havingNotLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->havingBuilder($param1, DBType::operator(" NOT LIKE "), DBType::text($param2));
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a string can not be found
	 * inside a column using the MYSQL `NOT` operand.
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param string|DBType	$param1
	 * @param string		$param2
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->orHavingNotLike('column', '%search%')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] `column` NOT LIKE '%search%' [...]
	 * ```
	 */
	public function orHavingNotLike(string|DBType $param1, string $param2): self {
		if(is_string($param1)) $param1 = DBType::column($param1);
		return $this->havingBuilder($param1, DBType::operator(" NOT LIKE "), DBType::text($param2), "OR");
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a given subquery exists
	 * If a condition is already present, it gets joined with the `AND` operand.
	 * 
	 * @param \Closure	$fun
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Example
	 * ```php
	 * [...]->havingExists(function($query) {
	 * 		$query->from('table')->having('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [AND] EXISTS (SELECT * FROM `table` HAVING `id`=10) [...]
	 * ```
	 */
	public function havingExists(\Closure $fun): self {
		return $this->havingBuilder(DBType::column(""), DBType::operator("EXISTS "), DBType::sub($fun));
	}
	/**
	 * Creates a HAVING Statement part. The added condition checks if a given subquery exists
	 * If a condition is already present, it gets joined with the `OR` operand.
	 * 
	 * @param \Closure	$fun
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Example
	 * ```php
	 * [...]->orHavingExists(function($query) {
	 * 		$query->from('table')->having('id', 10);
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] HAVING [OR] EXISTS (SELECT * FROM `table` HAVING `id`=10) [...]
	 * ```
	 */
	public function orHavingExists(\Closure $fun): self {
		return $this->havingBuilder(DBType::column(""), DBType::operator("EXISTS "), DBType::sub($fun));
	}

	/* JOIN FUNCTIONS */
	/**
	 * The function "join" is used to build a SQL join statement with various parameters.
	 * 
	 * @param string $table The `$table` parameter is a string that represents the name of the table you want to join with.
	 * @param string|DBType|\Closure $param1 The parameter `$param1` can be either a string, closure or DBType. If it is a string, it will be converted to a column using the `DBType::column()` method. If it is a closure, it will be converted to a subquery using the `DBType::sub()`
	 * @param string|DBType $param2 The parameter `$param2` is an optional string or DBType parameter.
	 * @param string|DBType $param3 The parameter `$param3` is an optional string or DBType parameter.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->join('table', 'table.id', 'othertable.table_id')->[...];
	 * [...]->join('table', function(\Webapp\Core\Database $query) {
	 * 		$query->on('table.id', 'othertable.table_id')
	 * 		->orOn('table.id', 'anothertable.table_id')
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] INNER JOIN `table` ON `table`.`id`=`othertable`.`table_id` [...]
	 * [...] INNER JOIN `table` ON (`table`.`id`=`othertable`.`table_id` OR `table`.`id`=`anothertable`.`table_id`) [...]
	 * ```
	 */
	public function join(string $table, string|DBType|\Closure $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		$table = DBType::column($table);
		if(is_string($param1)) $param1 = DBType::column($param1);
		else if(is_callable($param1)) $param1 = DBType::sub($param1);

		if(is_string($param2)) {
			if(is_null($param3)) $param2 = DBType::column($param2);
			else if(is_string($param3)) {
				$param2 = DBType::operator($param2);
				$param3 = DBType::column($param3);
			}
		}

		return $this->joinBuilder($table, $param1, $param2, $param3);
	}
	/**
	 * The function "leftJoin" is used to build a SQL join statement with various parameters.
	 * 
	 * @param string $table The `$table` parameter is a string that represents the name of the table you want to join with.
	 * @param string|DBType|\Closure $param1 The parameter `$param1` can be either a string, closure or DBType. If it is a string, it will be converted to a column using the `DBType::column()` method. If it is a closure, it will be converted to a subquery using the `DBType::sub()`
	 * @param string|DBType $param2 The parameter `$param2` is an optional string or DBType parameter.
	 * @param string|DBType $param3 The parameter `$param3` is an optional string or DBType parameter.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->leftJoin('table', 'table.id', 'othertable.table_id')->[...];
	 * [...]->leftJoin('table', function(\Webapp\Core\Database $query) {
	 * 		$query->on('table.id', 'othertable.table_id')
	 * 		->orOn('table.id', 'anothertable.table_id')
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] LEFT JOIN `table` ON `table`.`id`=`othertable`.`table_id` [...]
	 * [...] LEFT JOIN `table` ON (`table`.`id`=`othertable`.`table_id` OR `table`.`id`=`anothertable`.`table_id`) [...]
	 * ```
	 */
	public function leftJoin(string $table, string|DBType|\Closure $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		$table = DBType::column($table);
		if(is_string($param1)) $param1 = DBType::column($param1);
		else if(is_callable($param1)) $param1 = DBType::sub($param1);

		if(is_string($param2)) {
			if(is_null($param3)) $param2 = DBType::column($param2);
			else if(is_string($param3)) {
				$param2 = DBType::operator($param2);
				$param3 = DBType::column($param3);
			}
		}
		
		return $this->joinBuilder($table, $param1, $param2, $param3, "LEFT");
	}
	/**
	 * The function "rightJoin" is used to build a SQL join statement with various parameters.
	 * 
	 * @param string $table The `$table` parameter is a string that represents the name of the table you want to join with.
	 * @param string|DBType|\Closure $param1 The parameter `$param1` can be either a string, closure or DBType. If it is a string, it will be converted to a column using the `DBType::column()` method. If it is a closure, it will be converted to a subquery using the `DBType::sub()`
	 * @param string|DBType $param2 The parameter `$param2` is an optional string or DBType parameter.
	 * @param string|DBType $param3 The parameter `$param3` is an optional string or DBType parameter.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->rightJoin('table', 'table.id', 'othertable.table_id')->[...];
	 * [...]->rightJoin('table', function(\Webapp\Core\Database $query) {
	 * 		$query->on('table.id', 'othertable.table_id')
	 * 		->orOn('table.id', 'anothertable.table_id')
	 * })->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] RIGHT JOIN `table` ON `table`.`id`=`othertable`.`table_id` [...]
	 * [...] RIGHT JOIN `table` ON (`table`.`id`=`othertable`.`table_id` OR `table`.`id`=`anothertable`.`table_id`) [...]
	 * ```
	 */
	public function rightJoin(string $table, string|DBType|\Closure $param1, string|DBType $param2 = null, string|DBType $param3 = null): self {
		$table = DBType::column($table);
		if(is_string($param1)) $param1 = DBType::column($param1);
		else if(is_callable($param1)) $param1 = DBType::sub($param1);

		if(is_string($param2)) {
			if(is_null($param3)) $param2 = DBType::column($param2);
			else if(is_string($param3)) {
				$param2 = DBType::operator($param2);
				$param3 = DBType::column($param3);
			}
		}

		return $this->joinBuilder($table, $param1, $param2, $param3, "RIGHT");
	}
	/**
	 * The function "leftJoin" is used to build a SQL join statement with various parameters.
	 * 
	 * @param string $table The `$table` parameter is a string that represents the name of the table you want to join with.
	 * 
	 * @return self an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->crossJoin('table')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] CROSS JOIN `table` [...]
	 * ```
	 */
	public function crossJoin(string $table): self {
		$table = DBType::column($table);
		return $this->joinBuilder($table, type: "CROSS");
	}
	/**
	 * The function takes in three parameters, column1, columnOrOperator, and column2, and returns an
	 * instance of the class it belongs to after performing some operations on the parameters.
	 * 
	 * @param string|DBType|\Closure $column1 The first parameter, `$column1`, can be either a string, a closure, or an instance of the `DBType` class.
	 * @param string|DBType $columnOrOperator The parameter `columnOrOperator` can be either a string representing a column name or an operator, or it can be an instance of the `DBType` class.
	 * @param string|DBType $column2 The parameter "column2" is an optional parameter that represents the second column or value to be used in the join condition. It can be either a string representing a column name or a DBType object representing a value or expression.
	 * 
	 * @return self an instance of the current class (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->on('table.id', 'othertable.id')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] JOIN [...] ([AND] `table`.`id`=`othertable`.`id`) [...]
	 * ```
	 */
	public function on(string|DBType|\Closure $column1, string|DBType $columnOrOperator = null, string|DBType $column2 = null): self {
		if(is_string($column1)) $column1 = DBType::column($column1);
		else if(is_callable($column1)) $column1 = DBType::sub($column1);

		if(is_string($columnOrOperator) && is_null($column2)) $columnOrOperator = DBType::column($columnOrOperator);
		else if(is_string($columnOrOperator) && !is_null($column2)) $column2 = DBType::operator($columnOrOperator);
		return $this->joinOnBuilder($column1, $columnOrOperator, $column2);
	}
	/**
	 * The function `orOn` is used to add an "OR" condition to a join query in PHP.
	 * 
	 * @param string|DBType|\Closure $column1 The first parameter, `$column1`, can be either a string, a closure, or an instance of the `DBType` class.
	 * @param string|DBType $columnOrOperator The parameter `columnOrOperator` can be either a string representing a column name or an operator, or it can be an instance of the `DBType` class.
	 * @param string|DBType $column2 The parameter "column2" is an optional parameter that represents the second column or value to be used in the join condition. It can be either a string representing a column name or a DBType object representing a value or expression.
	 * 
	 * @return self an instance of the class that it belongs to.
	 * 
	 * # Examples
	 * ```php
	 * [...]->orOn('table.id', 'othertable.id')->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] JOIN [...] ([OR] `table`.`id`=`othertable`.`id`) [...]
	 * ```
	 */
	public function orOn(string|\Closure|DBType $column1, string|DBType $columnOrOperator = null, string|DBType $column2 = null): self {
		if(is_string($column1)) $column1 = DBType::column($column1);
		else if(is_callable($column1)) $column1 = DBType::sub($column1);

		if(is_string($columnOrOperator) && is_null($column2)) $columnOrOperator = DBType::column($columnOrOperator);
		else if(is_string($columnOrOperator) && !is_null($column2)) $column2 = DBType::operator($columnOrOperator);
		
		return $this->joinOnBuilder($column1, $columnOrOperator, $column2, operand: "OR");
	}

	/* INSERT FUNCTIONS */
	/**
	 * It takes an array of arrays and inserts them into the database.
	 * 
	 * @param array $inserts An array of arrays. Each array is a row to be inserted.
	 * @param bool $exitOnError If true, the script will exit if there is an error.
	 * 
	 * @return bool True on success, false on error.
	 */
	public function insert(array $inserts, bool $exitOnError = true): bool|array {
		if(count($inserts) == count($inserts, COUNT_RECURSIVE)) $inserts = [$inserts];

		$params = [];
		$columns = array_map(fn($col) => DBType::column($col), array_keys($inserts[0]));

		$this->sql = "INSERT INTO ";
		$this->sql .= implode(", ", array_map(function($from) {
			return $from;
		}, $this->getFroms()));
		$this->sql .= " (".implode(', ', array_map(fn($col) => $col, $columns)).")";
		$this->sql .= " VALUES ";
		foreach($inserts as $key => $insert) {
			$bindings = array_map(fn($binding) => preg_replace("/[^\w]/", "", $binding)."_".$key, array_keys($insert));
			$values = array_map(fn($val) => (is_string($val) ? DBType::text($val) : (is_null($val) ? DBType::null() : DBType::numeric($val))), array_values($insert));
			
			$this->sql .= ($key == 0 ? "" : ", ")."(:".implode(", :", $bindings).")";
			foreach($bindings as $key => $binding) {
				$params[$binding] = $values[$key];
			}
		}

		$result = $this->execute($params);

		if($exitOnError && !empty($this->error)) dd($this->error);
		return ($this->debug ? $result : $this->stmt->rowCount() > 0);
	}
	/**
	 * It inserts data into a database table.
	 * 
	 * @param array $inserts An array of arrays. Each array is a row to be inserted.
	 * 
	 * @return bool A boolean value.
	 */
	public function insertOrIgnore(array $inserts): bool {
		return $this->insert($inserts, false);
	}
	/**
	 * It inserts data into a database table.
	 * 
	 * @param array $inserts An array of arrays. Each array is a row to be inserted.
	 * 
	 * @return int|array|false The last inserted id(s).
	 */
	public function insertGetId(array $inserts): int|array|false {
		$ids = [];
		if(count($inserts) != count($inserts, COUNT_RECURSIVE)) {
			foreach($inserts as $insert) {
				$this->insert($insert);
				$ids[] = $this->connection->lastInsertId();
			}
		} else {
			$this->insert($inserts);
			$ids = $this->connection->lastInsertId();
		}

		return !empty($ids) ? $ids : false;
	}
	/**
	 * It inserts an array of data into a table and returns the number of rows affected
	 * 
	 * @param array $inserts An array of arrays. Each array is a row to be inserted.
	 * 
	 * @return int|false The number of rows affected by the last SQL statement.
	 */
	public function insertGetCount(array $inserts): int|false {
		if($this->insert($inserts)) {
			return $this->stmt->rowCount();
		}

		return false;
	}
	/**
	 * It takes an array of column names and an array of values and inserts them into the database.
	 * 
	 * @param array $columns An array of column names
	 * @param array $values An array of values to insert. If you want to insert multiple rows, make the values a multidimensional array.
	 * 
	 * @return bool|array return value is the last inserted id.
	 */
	public function insertUsing(array $columns, array $values): bool|array {
		$inserts = [];
		if(count($values) != count($values, COUNT_RECURSIVE)) {
			foreach($values as $value) {
				if(count($value) != count($columns)) return false;

				$valuesItem = array_values($value);
				foreach($valuesItem as $key => $valueItem) {
					$valuesItem[$columns[$key]] = $valueItem;
					unset($valuesItem[$key]);
				}
				$inserts[] = $valuesItem;
			}
		} else {
			if(count($values) != count($columns)) return false;

			$valuesItem = array_values($values);
			foreach($valuesItem as $key => $valueItem) {
				$valuesItem[$columns[$key]] = $valueItem;
				unset($valuesItem[$key]);
			}
			$inserts[] = $valuesItem;
		}

		return $this->insert($inserts);
	}

	/* UPDATE FUNCTIONS */
	/**
	 * It updates a table with the values passed in the array.
	 * 
	 * @param array $values An array of key-value pairs. The key is the column name and the value is the value to be inserted.
	 * 
	 * @return int|array The number of rows affected by the update statement
	 */
	public function update(array $values): int|array {
		$this->sql = "UPDATE ";

		foreach($this->getFroms() as $key => $table) {
			$this->sql .= ($key == 0 ? "" : ", ").$table;
		}

		if(!empty($values)) {
			$this->sql .= $this->buildJoin();
			$this->sql .= " SET ";
			$index = 0;
			foreach($values as $key => $valueType) {
				if(!$valueType instanceof DBType) {
					if(is_null($valueType)) $valueType = DBType::null();
					else if(is_string($valueType)) $valueType = DBType::text($valueType);
					else if(is_numeric($valueType) || is_bool($valueType)) $valueType = DBType::numeric($valueType);
				}
				$this->sql .= ($index == 0 ? "" : ", ");
				$this->sql .= DBType::column($key)."=";
				if($valueType->type == _DBType::Function) $this->sql .= $valueType;
				else {
					$key = preg_replace("/[^\w]/", "", $key);
					$this->sql .= ":".$key."_".$index;
					$this->params[$key."_".$index] = $valueType;
				}
				$index++;
			}
			$this->sql .= $this->buildCondition();
			$result = $this->execute($this->params);

			return ($this->debug ? $result : $this->stmt->rowCount());
		}

		return 0;
	}
	/**
	 * If the record exists, update it, otherwise insert it
	 * 
	 * @param array $search An array of columns and values to search for.
	 * @param array $update The array of data to update.
	 * 
	 * @return int The number of rows affected by the last query.
	 */
	public function updateOrInsert(array $search, array $update): int {
		$where = [];

		foreach($search as $column => $value) {
			$where[] = [$column, $this->defaultWhereOperator, is_string($value) ? "'".$value."'" : (is_bool($value) ? (int) $value : $value)];
		}

		$this->where($where);
		$query = DB::from($this->getBindings()['from']);

		if($query->exists()) {
			return $this->update($update);
		} else {
			$insert = array_merge($search, $update);
			$this->insert($insert);
		}

		return $this->stmt->rowCount();
	}
	/**
	 * It takes an array of data, checks if the data exists in the database, and if it does, it updates
	 * the data, otherwise it inserts it.
	 * 
	 * @param array $insertOrUpdate An array of arrays, each array being a row to insert or update.
	 * @param string|array $uniqueIdentifier This is the column or columns that will be used to determine if the row exists.
	 * @param string|array $update The array of data to update.
	 * 
	 * @return int The number of rows affected by the upsert.
	 */
	public function upsert(array $insertOrUpdate, string|array $uniqueIdentifier, string|array $update): int {
		$toInsert = [];
		$numRows = 0;
		foreach($insertOrUpdate as $data) {
			$where = [];
			foreach($data as $column => $value) {
				if((is_string($uniqueIdentifier) && $uniqueIdentifier == $column) || (is_array($uniqueIdentifier) && in_array($column, $uniqueIdentifier))) {
					$where[] = [$column, $this->defaultWhereOperator, is_string($value) ? "'".$value."'" : (is_bool($value) ? (int) $value : $value)];
				}
			}

			$this->clearBinding('where');
			$this->where($where);
			$query = DB::from($this->getBindings()['from']);

			if($query->exists())  {
				$toUpdate = [];
				if(is_array($update)) {
					foreach($update as $column) {
						$toUpdate[$column] = $data[$column];
					}
				} else {
					$toUpdate[$update] = $data[$update];
				}
				$numRows += $this->update($toUpdate);
			} else {
				$toInsert[] = $data;
			}
		}

		if(!empty($toInsert)) { 
			$this->insert($toInsert);
			$numRows += $this->stmt->rowCount();
		}

		return $numRows;
	}

	/* MISC FUNCTIONS */
	/**
	 * The function sets the limit for the number of results to be returned in a database query.
	 * 
	 * @param int $limit The "limit" parameter is an integer that specifies the maximum number of records to be returned in a query. By default, it is set to 1.
	 * 
	 * @return self The method is returning an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->limit()->[...];
	 * [...]->limit(50)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] LIMIT 1 [...]
	 * [...] LIMIT 50 [...]
	 * ```
	 */
	public function limit(int $limit = 1): self {
		$this->limit = DBType::numeric($limit);
		return $this;
	}
	/**
	 * Sets the offset value for a database query.
	 * 
	 * @param int $offset The "offset" parameter is an integer value that represents the number of rows to skip before starting to fetch the result set. It is commonly used in database queries to implement pagination, where you can fetch a subset of rows starting from a specific offset.
	 * 
	 * @return self The method is returning an instance of the class itself (self).
	 * 
	 * # Examples
	 * ```php
	 * [...]->offset(50)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] OFFSET 50 [...]
	 * ```
	 */
	public function offset(int $offset): self {
		$this->offset = DBType::numeric($offset);
		return $this;
	}
	/**
	 * The function `take` is an alias for limit.
	 * @see limit
	 * 
	 * @param int $n The parameter `n` is an integer that represents the number of items to be taken.
	 * 
	 * @return self The method is returning an instance of the current class.
	 * 
	 * # Examples
	 * ```php
	 * [...]->take(50)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] LIMIT 50 [...]
	 * ```
	 */
	public function take(int $n): self {
		return $this->limit($n);
	}
	/**
	 * The function "skip" is an alias for offset.
	 * @see offset
	 * 
	 * @param int $n The parameter "n" is an integer that represents the number of elements to skip.
	 * 
	 * @return self The method is returning an instance of the class itself.
	 * 
	 * 
	 * # Examples
	 * ```php
	 * [...]->skip(50)->[...];
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * [...] OFFSET 50 [...]
	 * ```
	 */
	public function skip(int $n): self {
		return $this->offset($n);
	}
	/**
	 * The function deletes records from a database table and returns the number of rows affected.
	 * 
	 * @return int an integer value, which is the number of rows affected by the delete operation.
	 * 
	 * # Example
	 * ```php
	 * [...]->delete();
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * DELETE FROM [...]
	 * ```
	 */
	public function delete(): int {
		$sql = $this->toSql();
		$sql = "DELETE".substr($sql, strpos($sql, " FROM "));
		$this->sql = $sql;
		$this->execute();

		return $this->stmt->rowCount();
	}
	/**
	 * The function takes a condition and two closures as arguments, and executes the first closure if the
	 * condition is true, and the second closure if the condition is false.
	 * 
	 * @param bool $condition A boolean value that determines whether to execute the or closure.
	 * @param \Closure $funTrue The `$funTrue` parameter is a closure that will be executed if the `$condition` is true. It takes `self` as an argument, which refers to the current instance of the class.
	 * @param \Closure $funFalse The parameter `$funFalse` is an optional closure that will be executed if the condition is false. It is set to `null` by default, meaning that if no value is provided, it will not be executed.
	 * 
	 * @return self the instance of the class itself.
	 * 
	 * # Examples
	 * ```php
	 * [...]->when(true, function($query) {
	 * 		$query->select('truevalue')
	 * }, function($query) {
	 * 		$query->select('falsevalue')
	 * })->[...]
	 * [...]->when(false, function($query) {
	 * 		$query->select('truevalue')
	 * }, function($query) {
	 * 		$query->select('falsevalue')
	 * })->[...]
	 * ```
	 * 
	 * Converts to
	 * 
	 * ```sql
	 * SELECT `truevalue` [...]
	 * SELECT `falsevalue` [...]
	 * ```
	 */
	public function when(bool $condition, \Closure $funTrue, \Closure $funFalse = null): self {
		if($condition) {
			$funTrue($this);
		} else if(is_callable($funFalse)) {
			$funFalse($this);
		}

		return $this;
	}

	/* RETURN FUNCTIONS */
	/**
	 * This function retrieves data from the database and returns it as an array or false.
	 * 
	 * @param int $mode The "mode" parameter determines the format in which the query results should be returned. It accepts a constant value from the DB class, which is likely an enumeration or a set of predefined constants. The value passed to the "mode" parameter will determine how the query results are fetched and returned.
	 * @param bool $singleResultConversion The "singleResultConversion" parameter is a boolean flag that determines whether the result of the query should be converted to a single result or not. If it is set to true, the function will check if the result is an array with only one element, and if so, it will return that element
	 * 
	 * @return array|false an array or false.
	 */
	public function get(int $mode = DB::FETCH_ARRAY, bool $singleResultConversion = false) : array|false {
		$this->buildQuery();
		$execute = $this->execute($this->params, $mode);
		return ($singleResultConversion ? ($execute && count($execute)==1 ? $execute[0] : $execute) : $execute);
	}
	/**
	 * The function "getSingle" returns a single result from a database query in PHP.
	 * 
	 * @param int $mode The "mode" parameter determines the format in which the query results should be returned. It accepts a constant value from the DB class, which is likely an enumeration or a set of predefined constants. The value passed to the "mode" parameter will determine how the query results are fetched and returned.
	 * 
	 * @return array|false an array or false.
	 */
	public function getSingle(int $mode = DB::FETCH_ARRAY) : array|false {
		$this->limit();
		return $this->get($mode, true);
	}
	/**
	 * The function retrieves the value(s) of a specified column from the executed query results in PHP.
	 * 
	 * @param string $column The "column" parameter is a string that represents the name of the column in the database table from which you want to retrieve the values.
	 * 
	 * @return string|array|false either a string, an array, or false.
	 */
	public function value(string $column): string|array|false {
		$this->buildQuery();
		$return = [];
		$results = $this->execute($this->params);
		if($results) {
			if(count($results)==1) $return = $results[0][$column];
			else {
				foreach($results as $result) {
					$return[] = $result[$column];
				}
			}
		}

		return $return;
	}
	/**
	 * The function "first" returns the first result from a database query in the specified mode, or false
	 * if no results are found.
	 * 
	 * @param int $mode The "mode" parameter is an optional parameter that specifies the fetch mode for the database query. It has a default value of DB::FETCH_ARRAY, which indicates that the query should return the result as an associative array.
	 * 
	 * @return array|false an array or false.
	 */
	public function first(int $mode = DB::FETCH_ARRAY) : array|false {
		return $this->getSingle($mode);
	}
	/**
	 * The function returns the last row from a database table based on the specified mode and order.
	 * 
	 * @param int $mode The "mode" parameter is an optional parameter that specifies the fetch mode for the database query. It has a default value of DB::FETCH_ARRAY, which means the query will return the result as an associative array.
	 * 
	 * @return array|false an array or false.
	 */
	public function last(int $mode = DB::FETCH_ARRAY) : array|false {
		$orders = $this->getOrders();
		$this->reorder()->orderBy('id', 'DESC')->orderBy($orders);

		return $this->getSingle($mode);
	}
	/**
	 * The pluck function retrieves a specific column from a database query result and returns it as an
	 * array, optionally using another column as the index.
	 * 
	 * @param string $column The `$column` parameter is a string that represents the name of the column in the database table from which you want to retrieve values.
	 * @param string $index The `$index` parameter is an optional parameter that specifies the column to be used as the index in the resulting array. If the `$index` parameter is not provided or is null, the resulting array will be indexed numerically.
	 * 
	 * @return array an array.
	 */
	public function pluck(string $column, string $index = null) : array {
		$this->sql = "SELECT ".$column.(empty($index) ? "" : ", ".$index);
		$res = $this->get();
		$ret = [];
		if($res) {
			foreach($res as $key => $value) {
				if(is_null($index)) {
					$ret[] = $value[$column];
				} else {
					$ret[$value[$index]] = $value[$column];
				}
			}
		}

		if(!empty($ret)) return $ret;
		return [];
	}
	/**
	 * The function counts the number of rows in a database table and returns the count as an integer, an
	 * array, or false.
	 * 
	 * @return int|array|false either an integer, an array, or false.
	 */
	public function count() : int|array|false {
		$this->addBinding('select', DBType::function("COUNT", [DBType::column("*")], alias: "result"));
		$result = $this->getSingle();

		if($result===false) return $result;
		return ($this->debug ? $result : (!empty($result['result']) ? $result['result'] : false));
	}
	/**
	 * The max function retrieves the maximum value from a specified column in a database table.
	 * 
	 * @param string $column The `$column` parameter is optional and specifies the column name for which you want to find the maximum value. By default, it is set to "*", which means it will find the maximum value across all columns.
	 * 
	 * @return int|array|float either an integer, array, or float value. If the result is not empty, it will return the value of the "result" key from the result array. If the result is empty, it will return 0.
	 * 
	 * # Example
	 * ```php
	 * [...]->max('price');
	 * ```
	 * 
	 * Converts to 
	 * 
	 * ```sql
	 * SELECT MAX(`price`) as result [...]
	 * ```
	 */
	public function max(string $column = "*") : int|array|float {
		$this->addBinding('select', DBType::function("MAX", [DBType::column($column)], alias: "result"));
		$result = $this->getSingle();
		
		if(!empty($result)) return ($this->debug ? $result : $result['result']);
		return 0;
	}
	/**
	 * The min function retrieves the minimum value from a specified column in a database table.
	 * 
	 * @param string $column The `$column` parameter is optional and specifies the column name for which you want to find the minimum value. By default, it is set to "*", which means it will find the minimum value across all columns.
	 * 
	 * @return int|array|float either an integer, array, or float value. If the result is not empty, it will return the value of the `$result` key from the result array. If the result is empty, it will return 0.
	 * 
	 * # Example
	 * ```php
	 * [...]->min('price');
	 * ```
	 * 
	 * Converts to 
	 * 
	 * ```sql
	 * SELECT MIN(`price`) as result [...]
	 * ```
	 */
	public function min(string $column = "*") : int|array|float {
		$this->addBinding('select', DBType::function("MIN", [DBType::column($column)], alias: "result"));
		$result = $this->getSingle();
		
		if(!empty($result)) return ($this->debug ? $result : $result['result']);
		return 0;
	}
	/**
	 * The function calculates the average value of a specified column in a database table and returns it
	 * as an integer, array, or float.
	 * 
	 * @param string $column The `$column` parameter is used to specify the column for which you want to calculate the average. By default, it is set to "*", which means the average will be calculated for all columns. However, you can pass the name of a specific column to calculate the average for that column only.
	 * 
	 * @return int|array|float either an integer, array, or float value.
	 * 
	 * # Example
	 * ```php
	 * [...]->avg('price');
	 * ```
	 * 
	 * Converts to 
	 * 
	 * ```sql
	 * SELECT AVG(`price`) as result [...]
	 * ```
	 */
	public function avg(string $column = "*") : int|array|float {
		$this->addBinding('select', DBType::function("AVG", [DBType::column($column)], alias: "result"));
		$result = $this->getSingle();
		
		if(!empty($result)) return ($this->debug ? $result : $result['result']);
		return 0;
	}
	/**
	 * The function calculates the sum of a specified column in a database table and returns the result.
	 * 
	 * @param string $column The `$column` parameter is a string that specifies the column name for which you want to calculate the sum. By default, it is set to "*", which means that the sum will be calculated for all columns in the table.
	 * 
	 * @return int|array|float either an integer, array, or float.
	 * 
	 * # Example
	 * ```php
	 * [...]->sum('price');
	 * ```
	 * 
	 * Converts to 
	 * 
	 * ```sql
	 * SELECT SUM(`price`) as result [...]
	 * ```
	 */
	public function sum(string $column = "*") : int|array|float {
		$this->addBinding('select', DBType::function("SUM", [DBType::column($column)], alias: "result"));
		$result = $this->getSingle();
		
		if(!empty($result)) return ($this->debug ? $result : $result['result']);
		return 0;
	}
	/**
	 * The exists() function checks wether a record with the configured query exists.
	 * 
	 * @return bool a boolean value.
	 */
	public function exists(): bool {
		return ($this->debug ? $this->count() : (bool) $this->count());
	}
	/**
	 * The doesntExist() function checks wether a record with the configured query doesn't exists.
	 * 
	 * @return bool a boolean value.
	 */
	public function doesntExist(): bool {
		return ($this->debug ? $this->count() : !((bool) $this->count()));
	}
	/**
	 * The chunk function takes a closure and an optional chunk size as parameters, and executes the
	 * closure on subsets of data from a database query result.
	 * 
	 * @param \Closure $fun The `$fun` parameter is a closure, which is an anonymous function. It is a callback function that you can pass to the `chunk` method. This closure will be executed for each chunk of data that is retrieved from the database.
	 * @param int $n The parameter `$n` represents the number of records to retrieve in each chunk. By default, it is set to 25.
	 */
	public function chunk(\Closure $fun, int $n = 25) {
		$offset = 0;
		$select = $this->getSelects();
		$this->clearBinding('select');
		$number = $this->count();
		$this->clearBinding('select');
		$this->addBinding('select', $select);
		for($i = 0; $i<$number;$i+=$n) {
			$fun($this->limit($n)->offset($offset)->get());
			$offset+=$n;
		}
	}

	/* GETTERS */
	/**
	 * Returns the `select` bindings
	 * 
	 * @return array bindings
	 */
	public function getSelects() : array {
		return $this->getBindings()['select'];
	}
	/**
	 * Returns the `from` bindings
	 * 
	 * @return array bindings
	 */
	public function getFroms() : array {
		return $this->getBindings()['from'];
	}
	/**
	 * Returns the `join` bindings
	 * 
	 * @return array bindings
	 */
	public function getJoins() : array {
		return $this->getBindings()['join'];
	}
	/**
	 * Returns the `where` bindings
	 * 
	 * @return array bindings
	 */
	public function getWheres() : string|array {
		return $this->getBindings()['where'];
	}
	/**
	 * Returns the `group` bindings
	 * 
	 * @return array bindings
	 */
	public function getGroups() : array {
		return $this->getBindings()['group'];
	}
	/**
	 * Returns the `having` bindings
	 * 
	 * @return array bindings
	 */
	public function getHavings() : array {
		return $this->getBindings()['having'];
	}
	/**
	 * Returns the `order` bindings
	 * 
	 * @return array bindings
	 */
	public function getOrders() : array {
		return $this->getBindings()['order'];
	}
	/**
	 * Returns all bindings
	 * 
	 * @return array bindings
	 */
	public function getBindings() : array {
		return $this->sqlParams;
	}
	/**
	 * Clears the bindings by type.
	 *
	 * @param string $type
	 * 
	 * @return void
	 */
	private function clearBinding(string $type): void {
		$this->sqlParams[$type] = [];
	}
	/**
	 * This function returns the error message.
	 * 
	 * @return string The error message.
	 */
	public function getError(): string {
		return $this->error ?? "";
	}
	/**
	 * Returns the last error
	 * 
	 * @return string
	 */
	public function getLastError(): string {
		return $this->lastError ?? "";
	}
	/**
	 * Returns the params which will be bound to the statement using `\PDO`
	 * 
	 * @return array
	 */
	public function getParams() : array {
		return $this->params;
	}

	/* SETTERS */
	/**
	 * Adds a binding of the given type.
	 * 
	 * @param string $type
	 * @param array|DBType $value
	 */
	public function addBinding(string $type, array|DBType $value) {
		$bindingArrayTypes = ['where', 'order', 'having', 'join'];
		if(is_array($value) && !in_array($type, $bindingArrayTypes)) {
			foreach($value as $v) {
				$this->sqlParams[$type][] = $v;
			}
		} else {
			$this->sqlParams[$type][] = $value;
		}
	}
	/**
	 * Sets a binding of the given type.
	 * 
	 * @param string $type
	 * @param string|array $value
	 */
	public function setBinding(string $type, string|array $value) {
		$this->sqlParams[$type] = $value;
	}

	/* HELPER */
	/**
	 * Builds the SQL Statement string.
	 * 
	 * @param bool $safeForHtml (optional)
	 *
	 * @return string
	 */
	public function toSql(bool $safeForHtml = false): string {
		$this->buildQuery();
		return ($safeForHtml ? htmlspecialchars($this->sql) : $this->sql);
	}
	/**
	 * Adds a paramter for binding to the statement.
	 * 
	 * @param string|array $params
	 * @param string|int $value	(optional)
	 * 
	 * @return self an instance of the class itself (self).
	 */
	public function addParam(string|array $params, string|int $value = null): self {
		if(is_array($params)) {
			foreach($params as $key => $value) {
				$this->addParam($key, $value);
			}
		} else if(!is_null($value)) {
			$this->params[$params] = $value;
		}

		return $this;
	}

	/* DEBUG */
	/**
	 * This function activates debugging.
	 * 
	 * @param bool $die if set, the query gets dumped and the script terminates.
	 * 
	 * @return self The method is returning an instance of the class itself (self)
	 */
	public function debug(bool $die = false): self {
		$this->debug = true;
		$this->debugDie = $die;

		return $this;
	}
}
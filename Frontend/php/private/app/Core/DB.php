<?php
namespace Webapp\Core;

/**
 * Singelton Facade to access the database.
 * @see Database
 * 
 * @method static void 			transaction()
 * @method static Database 		beginTransaction()
 * @method static void 			endTransaction()
 * @method static void 			cancelTransaction()
 * @method static array|false	statement(string $query, array $params = [])
 * @method static Database		database(string $databaseName)
 * @method static Database		table(string|array $tableName, string|array $alias = "")
 * @method static Database		select(string|array|DBType $select, string|array $alias = "")
 * @method static Database		from(string|array $tableName, string|array $alias = "")
 * @method static Database		distinct()
 * @method static Database		when(bool $condition, \Closure $funTrue, \Closure $funFalse = null)
 */
class DB {
	public const FETCH_ARRAY  = 0;
	public const FETCH_OBJECT = 1;

	private static ?Database $instance = null;

	/**
	 * If the instance is not set, set it to a new instance of the Database class, and return the
	 * instance.
	 * 
	 * @param string host The hostname of the database server.
	 * @param string dbName The name of the database you want to connect to.
	 * @param string user The username for the database
	 * @param string pass the password for the database
	 * 
	 * @return Database The instance of the Database class.
	 */
	public static function connection(string $host, string $dbName, string $user, string $pass) : Database {
		self::$instance = new Database($host, $dbName, $user, $pass);
		return self::$instance;
	}

	/**
	 * Creates a new Instance of the Database class
	 * 
	 * @return Database The instance of the Database class.
	 */
	public static function newInstance() : Database{
		self::$instance = new Database();
		return self::$instance;
	}

	/**
	 * If the instance is not set, set it to a new instance of the class. Then, return the instance's
	 * method with the arguments passed to the static method.
	 * 
	 * @param name The name of the method being called.
	 * @param args The arguments passed to the method.
	 * 
	 * @return mixed The return value of the method call on the instance.
	 */
	public static function __callStatic($name, $args) : mixed {
		if(!self::$instance) {
			self::$instance = new Database();
		}
		return self::$instance->{$name}(...$args);
	}
}

class DBType {

	private array $data = [];

	function __construct(_DBType $type, mixed $value, string $prefix = "", string|array|DBType $data = "", string $alias = "", string|array|DBType $funParams = []) {
		$this->data['type'] = $type;
		$this->data['rawValue'] = $value;
		if(!empty($prefix)) $this->data['prefix'] = $prefix;
		if(!empty($data)) $this->data['data'] = $data;
		if(!empty($alias)) $this->data['alias'] = $alias;
		if(!empty($funParams)) $this->data['funParams'] = $funParams;
	}

	private function columnString(string $column): string {
		$columnAlias = (strpos($column, " as ") !== false ? explode(" as ", $column) : explode(" ", $column));
		$column = $columnAlias[0];
		$alias = (!empty($columnAlias[1]) ? $columnAlias[1] : $this->alias);

		$params = explode(".", $column);
		return (
			!empty($params)
			? implode(".", array_map(fn($column) => (
				$column != "*" && !empty($column)
				? "`".$column."`"
				: $column
			), $params))
			: ""
		).(!empty($alias) ? " as ".$alias : "");
	}

	private function textString(string $text): string {
		return "'".$text."'";
	}

	private function numericString(int|float $num): string {
		return $num;
	}

	private function functionString($funName, $funParams): string {
		$funString = strtoupper($funName)."(";
		if(!empty($funParams)) {
			$funParams = (is_array($funParams) ? $funParams : [$funParams]);
			$funString .= implode(", ", array_map(function($dbType) {
				if($dbType instanceof DBType) {
					if($dbType->type == _DBType::Function) {
						return $dbType->__toString();
					}
					return $dbType;
				}
				return (is_string($dbType) ? (substr_count($dbType, ":") === 0 ? DBType::column($dbType) : $dbType) : DBType::numeric($dbType));
			}, $funParams));
		}
		$funString .= ")";
		$alias = $this->alias;
		$funString .= (!empty($alias) ? " as ".$alias : "");
		return $funString;
	}

	private function listString(array $list): string {
		return "(".implode(", ", $list).")";
	}

	private function rangeString(array $range): string {
		return implode(" AND ", $range);
	}

	public function __get(string $name): mixed {
		if(isset($this->data[$name])) return $this->data[$name];
		return null;
	}

	public function __toString(): string {
		switch ($this->type) {
			case _DBType::Column:
				return $this->columnString($this->rawValue);
			case _DBType::Function:
				return $this->functionString($this->rawValue, $this->funParams);
			case _DBType::List:
				return $this->listString($this->rawValue);
			case _DBType::Null:
				return 'NULL';
			case _DBType::Numeric:
				return $this->numericString($this->rawValue);
			case _DBType::Range:
				return $this->rangeString($this->rawValue);
			case _DBType::Text:
				return $this->textString($this->rawValue);
			default:
				return $this->rawValue;
		}

		return "";
	}

	public static function column(string $param, string $prefix = "", string $alias = ""): self {
		return new DBType(
			_DBType::Column,
			value: $param,
			prefix: $prefix,
			alias: $alias
		);
	}
	public static function function(string $funName, string|array|DBType $funParams = "", string $alias = ""): self {
		return new DBType(
			_DBType::Function,
			$funName,
			funParams: $funParams,
			alias: $alias,
		);
	}
	public static function numeric(string|int|float $param, string $prefix = ""): self {
		if(is_string($param)) $param = floatval($param);
		return new DBType(
			_DBType::Numeric,
			$param,
			$prefix,
		);
	}
	public static function text(string $param, string $prefix = ""): self {
		return new DBType(
			_DBType::Text,
			$param,
			$prefix,
		);
	}
	public static function null(): self {
		return new DBType(
			_DBType::Null,
			null,
		);
	}
	public static function sub(\Closure $param, string $prefix = "", string $alias = ""): self {
		return new DBType(
			_DBType::Sub,
			$param,
			$prefix,
			alias: $alias,
		);
	}
	public static function operator(string $param): self {
		return new DBType(
			_DBType::Operator,
			$param,
		);
	}
	public static function range(array $param): self {
		return new DBType(
			_DBType::Range,
			$param,
		);
	}
	public static function list(array $param): self {
		return new DBType(
			_DBType::List,
			$param,
		);
	}
	public static function param(string|int $param): self {
		return new DBType(
			_DBType::Parameter,
			$param
		);
	}
}

enum _DBType: string {
	case Column = 'column';
	case Function = 'function';
	case Numeric = 'numeric';
	case Text = 'text';
	case Null = 'null';
	case Sub = 'sub';
	case Operator = 'operator';
	case Range = 'range';
	case List = 'list';
	case Parameter = 'parameter';
}
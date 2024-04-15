<?php
namespace Webapp\Core;

class Model {

	protected array $dbFields = [];
	protected string $dbTable = "";

	function __construct() {
		// set db columns as variables
		if(!empty($this->dbTable) && Config::get('db.active')) {
			$table = DB::statement("SHOW TABLES LIKE '".$this->dbTable."'");
			if(!empty($table)) {
				$columns = DB::statement("SHOW COLUMNS FROM ".$this->dbTable);
				if(!empty($columns)) {
					if(!isset($this->dbFields['_required'])) $this->dbFields['_required'] = [];
					if(!isset($this->dbFields['_primary'])) $this->dbFields['_primary'] = [];
					foreach($columns as $column) {
						$columnName = $column['Field'];
						unset($column['Field']);
						$this->dbFields[$columnName] = $column;
						if($column['Null']=='NO' && is_null($column['Default'])) $this->dbFields['_required'][] = $columnName;
						if($column['Key']=='PRI') $this->dbFields['_primary'][] = $columnName;
					}
				}
			}
		}
	}

}
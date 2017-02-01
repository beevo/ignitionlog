<?php

class Model
{

	/**
	 * @var object $db_connection The database connection
	 */
	public $db_connection = null;
	/**
	 * @var bool success state of registration
	 */

	public $errors = array();
	/**
	 * @var array collection of success / neutral messages
	 */
	public $messages = array();


	public function __construct(){
		$this->tableName = '`' . $this->tableName . '`';
		if(!isset($this->relations)){
			$this->relations = array();
		}
		if(!isset($this->defaultOrderColumn)){
			$this->defaultOrderColumn = '';
		}
	}

	/**
	 * Checks if database connection is opened and open it if not
	 */
	public function databaseConnection(){
		// connection already opened
		if ($this->db_connection != null) {
			return true;
		} else {
			try {
				$this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
				return true;
			}
			catch (PDOException $e) {
				$this->errors[] = MESSAGE_DATABASE_ERROR;
				return false;
			}
		}
	}
	private function _setRelations(&$record){
		$record = (array)$record;
		$modelClass = "";
		//get the records relations and add them to the object
		for($i = 0; $i < count($this->relations); $i++){
			$model = array_keys($this->relations)[$i];
			$foreignKey = array_values($this->relations)[$i];
			$modelClass = ucfirst($model) . "Model";
			$record[$model] = $modelClass::model()->findById($record[$foreignKey]);
		}
		$record = (object)$record;
	}
  //finds all records
  public function findAll(){
    $records = array();
    // if database connection opened
    if ($this->databaseConnection()) {
			$select = 'SELECT * FROM ' . $this->tableName;
			if($this->defaultOrderColumn){
				$select .= ' ORDER BY ' . $this->defaultOrderColumn;
			}
      $query = $this->db_connection->prepare($select);
      $query->execute();
      if ($query->rowCount() > 0) {
        while ($record = $query->fetchObject()) {
					$this->_setRelations($record);
          $records[] = $record;
        }
      }
    }else{
			throw new Exception('No Db Connection');
		}
    return $records;
  }

	var $canDelete = false;
	//delete a specific item by it's primary key
  public function deleteById($id){
		if ($this->canDelete) {
			$record = false;
			if ($this->databaseConnection()) {
				$select = 'DELETE FROM ' . $this->tableName . ' WHERE id = :id';
				$query = $this->db_connection->prepare($select);
				$query->bindValue(':id', $id, PDO::PARAM_INT );
				$query->execute();
				$record = true;
			}else{
				throw new Exception('No Db Connection');
			}
			return $record;
		}else{
			Helper::log("cannot delete this model");
			return null;
		}

  }
  //find a specific item by it's primary key
  public function findById($id){
    $record = null;
		if($id){
			if ($this->databaseConnection()) {
				$select = 'SELECT * FROM ' . $this->tableName . ' WHERE id = :id';
				$query = $this->db_connection->prepare($select);
				$query->bindValue(':id', $id, PDO::PARAM_INT );
				$query->execute();
				if ($query->rowCount() > 0) {
					$record = $query->fetchObject();
					$this->_setRelations($record);
				}
			}else{
				throw new Exception('No Db Connection');
			}
		}
    return $record;
  }
	public function beforeUpdate(&$attributes){
		return true;
	}
	public function afterUpdate(){
		return true;
	}
	public function beforeCreate(&$attributes){
		return true;
	}
	public function afterCreate(){
		return true;
	}
	var $model = null;
	// given an array of attributes and matching values,
	// this funciton creates the model given that the id param is accurate
	// returns true if it succeeded
	public function create($attributes = array()){
		if(!$this->beforeCreate($attributes)){
			return;
		}
		if(empty($attributes)){
			return false;
		}
		// create the where statement by mapping the attributes
		// a format like id = :id, name = :name
		$names = array_keys($attributes);
		$columns = implode(', ', $names);
		$values = array();
		foreach($names as $name){
			$values[] = ':' . $name;
		}
		$values = implode(', ', $values);
		$insert = 'INSERT INTO ' . $this->tableName . " ($columns) VALUES ($values)";
		// if database connection opened
		if ($this->databaseConnection()) {
			$query = $this->db_connection->prepare($insert);
			try {
				$query->execute($attributes);
			} catch (PDOException $e) {
				Application::log("Error" . $e);
			}
			// print_r($query->errorInfo());
			if ($query->rowCount() > 0) {
				$id = $this->db_connection->lastInsertId();

				$this->model = $this->findById($id);
				$this->afterCreate();
				return($this->model);
			}else{
				echo "wat";
			}
		}else{
			throw new Exception('No Db Connection');
		}
		return false;
	}

	// given an array of attributes and matching values,
  // this funciton updates the model given that the id param is accurate
	// returns true if it succeeded
  public function update($attributes = array()){
    if(empty($attributes)){
      return false;
    }
		$this->beforeUpdate($attributes);
    $set = '';
		$where = 'id = :id';
    // create the where statement by mapping the attributes
    // a format like id = :id, name = :name
		$end = array_keys($attributes)[count($attributes)-1];
    foreach(array_keys($attributes) as $name){
			if($name != 'id'){
				$set .= "$name = :$name";
				if($name == $end){
					$set .= ' ';
				}else{
					$set .= ', ';
				}
			}
		  $attributes[":$name"] = $attributes[$name];
      unset($attributes[$name]);
    }
    $update = 'UPDATE ' . $this->tableName . ' SET ' . $set;
    $update .= ' WHERE '. $where;
		// if database connection opened
    if ($this->databaseConnection()) {
      $query = $this->db_connection->prepare($update);
      $query->execute($attributes);
			if ($query->rowCount() > 0) {
				$this->afterUpdate();
				return true;
      }else{
				// echo $query->errorInfo();
			}
    }else{
      throw new Exception('No Db Connection');
    }
    return false;
  }

  // given an array of attributes and matching values,
  // return an array of those items in the db.
  public function findByAttributes($attributes = array()){
    $record = null;
    if(empty($attributes)){
      return $this->findAll();
    }
    $where = '';
    // create the where statement by mapping the attributes
    // a format like id = :id, name = :name
    foreach(array_keys($attributes) as $name){
      $where .= "$name = :$name AND ";
      $attributes[":$name"] = $attributes[$name];
      unset($attributes[$name]);
    }
    $where .= '1';
    $select = 'SELECT * FROM ' . $this->tableName;
    $select .= ' WHERE '. $where;
    // if database connection opened
    if ($this->databaseConnection()) {
      $query = $this->db_connection->prepare($select);
      $query->execute($attributes);
      if ($query->rowCount() > 0) {
        $record = $query->fetchObject();
				$this->_setRelations($record);
      }
    }else{
      throw new Exception('No Db Connection');
    }
    return $record;
  }
  // given an array of attributes and matching values,
  // return an array of those items in the db.
  public function findAllByAttributes($attributes = array(), $sort = array()){
    $records = array();
    if(empty($attributes)){
      return $this->findAll();
    }
    $where = '';
    // create the where statement by mapping the attributes
    // a format like id = :id, name = :name
    foreach(array_keys($attributes) as $name){
      $where .= "$name = :$name AND ";
      $attributes[":$name"] = $attributes[$name];
      unset($attributes[$name]);
    }
    $where .= '1';
    $select = 'SELECT * FROM ' . $this->tableName;
    $select .= ' WHERE '. $where;
		if(!empty($sort)){
			if(isset($sort['order'])) {
				$select .= ' ORDER BY ' . $sort['order'] . ' ASC';
			}
		}else if($this->defaultOrderColumn){

				$select .= ' ORDER BY ' . $this->defaultOrderColumn;
		}
    // if database connection opened
    if ($this->databaseConnection()) {
      $query = $this->db_connection->prepare($select);
      $query->execute($attributes);
      if ($query->rowCount() > 0) {
        while ($record = $query->fetchObject()) {
					$this->_setRelations($record);
          $records[] = $record;
        }
      }
    }else{
      throw new Exception('No Db Connection');
    }
    return $records;
  }
  
    public function search($attributes = array(), $sort = array()){
	/*
		array(
			'action LIKE' => '%shove%',
			'AND effective_bet_bbs >' => 10
		);
	
	*/
    $records = array();
    if(empty($attributes)){
      return $this->findAll();
    }
    $where = '';
    // create the where statement by mapping the attributes
    // a format like id = :id, name = :name
    foreach(array_keys($attributes) as $key => $name){
      $where .= "$name :p$key ";
      $attributes[":p$key"] = $attributes[$name];
      unset($attributes[$name]);
    }

    $select = 'SELECT * FROM ' . $this->tableName;
    $select .= ' WHERE '. $where;
		if(!empty($sort)){
			if(isset($sort['order'])) {
				$select .= ' ORDER BY ' . $sort['order'] . ' ASC';
			}
		}else if($this->defaultOrderColumn){

				$select .= ' ORDER BY ' . $this->defaultOrderColumn;
		}
    // if database connection opened
    if ($this->databaseConnection()) {
      $query = $this->db_connection->prepare($select);
      $query->execute($attributes);
      if ($query->rowCount() > 0) {
        while ($record = $query->fetchObject()) {
					$this->_setRelations($record);
          $records[] = $record;
        }
      }
    }else{
      throw new Exception('No Db Connection');
    }
    return $records;
  }
}

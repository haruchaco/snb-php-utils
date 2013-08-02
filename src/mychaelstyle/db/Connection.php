<?php
/**
 * @package mychaelstyle
 * @subpackage db
 * @author  Masanori Nakashima <m_nakashima@users.sourceforge.jp>
 */
class Connection {
  /** in transaction  */
  private $inTransaction    = false;
  // for query
  /** bind params map */
  private $bindParamsHash    = array();
  /** bind values map */
  private $bindValuesHash    = array();
  /** limit */
  private $limit = null;
  /** offset */
  private $offset = null;
  /**
   * constructor
   */
  public function __construct() {

  }
  /**
   * get last insert id
   */
  public function lastInsertId($table=null){
    $connection  = $this->getProperConnection('INSERT');
    return $connection->lastInsertId($table);
  }
  /**
   * quote
   * @param string $value
   * @param string $parameterType data type
   * @return string quoted strings
   * @access public
   */
  public function quote($query,$parameterType=PDO::PARAM_STR){
    $connection  = $this->getProperConnection();
    return $connection->quote( $query, $parameterType );
  }
  /**
   * begin transaction
   * @return boolean
   * @access public
   */
  public function beginTransaction() {
    $this->inTransaction  = true;
    $connection  = $this->getProperConnection();
    return $connection->beginTransaction();
  }
  /**
   * commit
   * @return boolean
   * @access public
   */
  public function commit() {
    $connection  = $this->getProperConnection();
    $this->inTransaction  = false;
    return $connection->commit();
  }
  /**
   * rollbak
   * @return boolean
   * @access public
   */
  public function rollback() {
    $connection  = $this->getProperConnection();
    $this->inTransaction  = false;
    return $connection->rollback();
  }
  /**
   * statement
   * @var PDOStatement
   */
  private $statement;
  /**
   * prepare
   * @param string $query
   */
  public function prepare($query){
    $connection  = $this->getProperConnection($query);
    $this->statement = $connection->prepare($query);
    return $this->statement;
  }
  /**
   * close statement cursor
   */
  public function closeCursor(){
    if( $this->statement ){
      $this->statement->closeCursor();
      $this->bindParamsHash = array();
      $this->bindValuesHash = array();
      $this->statement = null;
    }
  }
  /**
   * query statement
   * @param string $query
   * @return statement
   * @access public
   */
  public function query( $query ) {
    $query = trim( $query );
    $connection = $this->getProperConnection($query);
    $this->statement = $connection->prepare($query);
    if( is_array($this->bindParamsHash) ){
      foreach( $this->bindParamsHash as $key => $info ){
        $this->statement->bindParam($key, $this->bindParamsHash[$key]['value'], $info['type'] );
      }
    }
    if( is_array($this->bindValuesHash) ){
      foreach( $this->bindValuesHash as $key => $info ){
        $this->statement->bindValue($key, $this->bindValuesHash[$key]['value'], $info['type'] );
      }
    }
    $this->statement->execute();
    return $this->statement;
  }
  /**
   * bind param for query.
   * @param mixed $key
   * @param mixed $value
   * @param int $type
   */
  public function bindParam($key,$value,$type){
    if( !is_array($this->bindParamsHash) ){
      $this->bindParamsHash = array();
    }
    $array = array('key'=>$key,'value'=>$value,'type'=>$type);
    $this->bindParamsHash[$key] = $array;
  }
  /**
   * bind value for query.
   * @param mixed $key
   * @param mixed $value
   * @param int $type
   */
  public function bindValue($key,$value,$type){
    if( !is_array($this->bindValuesHash) ){
      $this->bindValuesHash = array();
    }
    $array = array('key'=>$key,'value'=>$value,'type'=>$type);
    $this->bindValuesHash[$key] = $array;
  }
  /**
   * set limit for queryAll
   * @param int $limit
   * @param int $offset
   */
  public function setLimit( $limit, $offset = null ) {
    $this->limit = $limit;
    $this->offset = $offset;
  }
  /**
   * query all rows
   * @param string $query
   * @param mixed $fetchType
   * @param string $callback
   * @param array $callbackParams
   * @throws spider_Exception
   */
  public function queryAll($query,$fetchType=null,$callback=null,$callbackParams=array()){
    if( preg_match('/^select/',trim(strtolower($query))) == 0
    && preg_match('/^show/',trim(strtolower($query))) == 0 ){
      throw new spider_Exception('errors.db.query.limitedonlyforselect', array('queryAll'));
    }
    $connection  = $this->getProperConnection();
    // set limit and offset
    $query = $connection->appendLimitClause( $query, $this->limit, $this->offset );
    $this->limit  = null;
    $this->offset  = null;
    // bind params
    $statement  = $connection->prepare($query);
    if( is_array($this->bindParamsHash) ){
      foreach( $this->bindParamsHash as $key => $info ){
        $statement->bindParam($key, $this->bindParamsHash[$key]['value'], $info['type'] );
      }
    }
    if( is_array($this->bindValuesHash) ){
      foreach( $this->bindValuesHash as $key => $info ){
        $statement->bindValue($key, $this->bindValuesHash[$key]['value'], $info['type'] );
      }
    }
    // execute and create result set
    $statement->execute();
    $resultHash = null;
    if( is_object($fetchType) ){
      $resultHash  = $statement->fetchAll(PDO::FETCH_CLASS,get_class($fetchType));
    } else if( strlen($fetchType)>0 && 'hash'==strtolower($fetchType) ){
      $resultHash  = $statement->fetchAll(PDO::FETCH_ASSOC);
    } else if( strlen($fetchType)>0 && 'array'==strtolower($fetchType) ){
      $resultHash  = $statement->fetchAll(PDO::FETCH_NUM);
    } else if( strlen($fetchType)>0 ){
      $resultHash  = $statement->fetchAll(PDO::FETCH_CLASS,$fetchType);
    } else {
      $resultHash  = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    $this->bindParamsHash = array();
    $this->bindValuesHash = array();
    $statement->closeCursor();
    if( $callback ){
      if(!is_callable($callback)){
        throw new spider_Exception('DBO invalid callback!');
      }
      if(!is_array($callbackParams)){
        if(!is_null($callbackParams)){
          $callbackParams = array($callbackParams);
        } else {
          $callbackParams = array();
        }
      }
      if(is_array($callback) && count($callback)>0){
        if(is_object($callback[0])){
          if(is_object($fetchType) && get_class($fetchType)==get_class($callback[0])){
            // callbackがオブジェクトで指定クラスと同じ場合のみリストの各オブジェクトのメソッドコール
            foreach($resultHash as $key => $obj){
              $callback[0] = & $resultHash[$key];
              call_user_func_array($callback, $callbackParams);
            }
          } else {
            // callbackがobjectで指定クラスと違う場合
            array_push($callbackParams,$resultHash);
            call_user_func_array($callback, $callbackParams);
          }
        } else {
          // callbackがobjectではない場合は関数化静的メソッド
          array_push($callbackParams,$resultHash);
          call_user_func_array($callback, $callbackParams);
        }
      } else {
        // callbackが文字列の場合
        array_push($callbackParams,$resultHash);
        call_user_func_array($callback, $callbackParams);
      }
    }
    return $resultHash;
  }
  /**
   * query one row
   * @param string $query
   * @param int $fetchType
   * @param mixed $callback
   * @param array $callbackParams
   * @throws spider_Exception
   */
  public function queryRow( $query, $fetchType=null, $callback=null,$callbackParams=array() ){
    if( preg_match('/^select/',trim(strtolower($query))) == 0
    && preg_match('/^show/',trim(strtolower($query))) == 0 ){
      throw new spider_Exception('errors.db.query.limitedonlyforselect', array('queryRow'));
    }
    $connection  = $this->getProperConnection();
    // bind params
    $statement  = $connection->prepare($query);
    if( is_array($this->bindParamsHash) ){
      foreach( $this->bindParamsHash as $key => $info ){
        $statement->bindParam($key, $this->bindParamsHash[$key]['value'], $info['type'] );
      }
    }
    if( is_array($this->bindValuesHash) ){
      foreach( $this->bindValuesHash as $key => $info ){
        $statement->bindValue($key, $this->bindValuesHash[$key]['value'], $info['type'] );
      }
    }
    // execute and create result set
    $statement->execute();
    $result = null;
    if( is_object($fetchType) ){
      $statement->setFetchMode( PDO::FETCH_INTO, $fetchType);
      $statement->fetch(PDO::FETCH_INTO);
      $result = $fetchType;
    } else if( strlen($fetchType)>0 && 'hash'==strtolower($fetchType) ){
      $result  = $statement->fetch(PDO::FETCH_ASSOC);
    } else if( strlen($fetchType)>0 && 'array'==strtolower($fetchType) ){
      $result  = $statement->fetch(PDO::FETCH_NUM);
    } else if( strlen($fetchType)>0 ){
      if (!class_exists($fetchType)) {
        Loader::load($fetchType);
      }
      $statement->setFetchMode( PDO::FETCH_CLASS, $fetchType);
      $result  = $statement->fetch(PDO::FETCH_CLASS);
    } else {
      $result  = $statement->fetch(PDO::FETCH_ASSOC);
    }
    $this->bindParamsHash = array();
    $this->bindValuesHash = array();
    $statement->closeCursor();
    if( $callback ){
      if(!is_array($callbackParams)){
        if(!is_null($callbackParams)){
          $callbackParams = array($callbackParams);
        } else {
          $callbackParams = array();
        }
      }
      array_push($callbackParams,$this);
      call_user_func_array($callback, $callbackParams);
    }
    return $result;
  }
  /**
   * query one column value
   * @param string $query
   * @param mixed $callback
   * @param array $callbackParams
   * @throws spider_Exception
   */
  public function queryOne( $query, $callback=null, $callbackParams=array() ){
    if( preg_match('/^select/',trim(strtolower($query))) == 0
    && preg_match('/^show/',trim(strtolower($query))) == 0 ){
      throw new spider_Exception('errors.db.common', '',array('queryOne can use in select statement only.'));
    }
    $connection  = $this->getProperConnection();
    // bind params
    $statement  = $connection->prepare($query);
    if( is_array($this->bindParamsHash) ){
      foreach( $this->bindParamsHash as $key => $info ){
        $statement->bindParam($key, $this->bindParamsHash[$key]['value'], $info['type'] );
      }
    }
    if( is_array($this->bindValuesHash) ){
      foreach( $this->bindValuesHash as $key => $info ){
        $statement->bindValue($key, $this->bindValuesHash[$key]['value'], $info['type'] );
      }
    }
    // execute and create result set
    $statement->execute();
    $result  = $statement->fetch(PDO::FETCH_NUM);
    $this->bindParamsHash = array();
    $this->bindValuesHash = array();
    $statement->closeCursor();
    if( $callback ){
      if(!is_array($callbackParams)){
        if(!is_null($callbackParams)){
          $callbackParams = array($callbackParams);
        } else {
          $callbackParams = array();
        }
      }
      array_push($callbackParams,$this);
      call_user_func_array($callback, $callbackParams);
    }
    return $result[0];
  }
  /**
   * load
   * @param object $dataObject
   * @param hash $conditionHash
   * @param mixed $callback
   * @param array $callbackParams
   * @throws spider_Exception
   */
  public function load( $dataObject, $conditionHash=array(), $callback=null, $callbackParams=array() ){
    $conditionArray = array();
    foreach( $conditionHash as $key => $val ){
      if( strlen(trim($key)) > 0 ){
        $str = $key.'= :'.trim($key);
        array_push($conditionArray, $str);
      }
    }
    if( count($conditionArray) == 0 ){
      throw new spider_Exception('errors.db.common'
      , array('load can use with any conditions only.'));
    }
    foreach( $conditionHash as $key => $val ){
      $this->bindValue(':'.$key,$conditionHash[$key],PDO::PARAM_STR);
    }
    $query = 'SELECT * FROM '.$dataObject->getTableName()
    .' WHERE '.implode(' AND ',$conditionArray);
    return $this->queryRow($query, $dataObject, $callback, $callbackParams );
  }
  /**
   * set next id
   * @param object $dataObject
   * @param string $format
   * @throws spider_Exception
   */
  public function setNextId(&$dataObject,$format='ID{num:6}') {
    $uniqueFields = $dataObject->getUniqueFields();
    foreach($uniqueFields as $fieldName){
      if(is_null($dataObject->$fieldName) || strlen($dataObject->$fieldName)==0){
        $nextId = $this->getNextId($dataObject->getTableName(),$fieldName,$format);
        if( false === $nextId ) {
          return false;
        } else {
          $dataObject->$fieldName = $nextId;
        }
      }
    }
    return true;
  }
  /**
   * get next id
   * @param string $tableName
   * @param string $fieldName
   * @param string $format
   */
  public function getNextId($tableName,$fieldName,$format='ID{num:6}'){
    $regx  = $format;
    if( preg_match_all( '/\\{num\\:([0-9]+)\\}/', $regx, $matches, PREG_PATTERN_ORDER ) > 0 ) {
      foreach( $matches[0] as $key => $matchString ) {
        $numLen  = $matches[1][$key];
        $repStr  = '';
        for( $i=0; $i<$numLen;$i++ ) {
          $repStr  .= '_';
        }
        $regx  = str_replace($matchString,$repStr,$regx);
      }
    }
    $sql = sprintf('SELECT %s FROM %s WHERE %s LIKE :regx ORDER BY %s DESC',
      $fieldName,$tableName,$fieldName,$fieldName);
    $this->bindValue(':regx',$regx,PDO::PARAM_STR);
    $this->setLimit( 1, 0 );
    $result  = $this->queryAll($sql);
    if ( $result === false ) {
      throw new spider_Exception('[db_Connection::getNextId]['.$tableName.']['.$fieldName.']'.$sql);
    } else {
      if( count($result) > 0 ) {
        $lastId    = trim($result[0][$fieldName]);
        $lastIdElms  = util_String::slice($lastId,1);
        $nextElms  = $lastIdElms;
        for( $i=count($nextElms)-1; $i>=0; $i-- ) {
          $elm  = $nextElms[$i];
          if( preg_match('/[0-9]+/',$elm) > 0 ) {
            $elm++;
            if( strlen($elm) > strlen($nextElms[$i]) ) {
              $elm  = 1;
              $nextElms[$i]  = sprintf('%0'.strlen($nextElms[$i]).'d',$elm);
            } else {
              $nextElms[$i]  = sprintf('%0'.strlen($nextElms[$i]).'d',$elm);
              break;
            }
          } else if( preg_match('/[A-Z]/',$elm) > 0 ) {
            $charNumArray  = unpack('C*',$elm);
            $charNum    = $charNumArray[0];
            $charNum++;
            if( $charNum > 90 ) {
              $nextElms[$i]  = 'A';
            } else {
              $nextElms[$i]  = pack('C*',$charNum);
              break;
            }
          }
        }
        return implode('',$nextElms);
      } else {
        $nextId  = $format;
        if( preg_match_all( '/\\{num\\:([0-9]+)\\}/', $nextId, $matches, PREG_PATTERN_ORDER ) > 0 ) {
          foreach( $matches[0] as $key => $matchString ) {
            $numLen  = $matches[1][$key];
            $numStr  = sprintf('%0'.$numLen.'d',1);
            $nextId  = str_replace($matchString,$numStr,$nextId);
          }
        }
        return $nextId;
      }
    }
  }
  /**
   * create rundom id
   * @param string $tableName
   * @param string $fieldName
   * @param string $length
   * @throws Exception
   */
  public function createRandomId($tableName,$fieldName,$length=6,$prefix=''){
    Loader::load('util_String');
    $id = $prefix.util_String::getRandomStr($length);
    $counter = 0;
    while(true){
      $counter++;
      $sql = sprintf('SELECT COUNT(%s) FROM %s WHERE %s=:rundomId',$fieldName,$tableName,$fieldName);
      $this->bindValue(':rundomId', $id, PDO::PARAM_STR);
      $c = $this->queryOne($sql);
      if(0===intval($c)){
        return $id;
      } else if($counter>100){
        throw new Exception('fail to create id '.$id.' '.$c);
      }
      $id = $prefix.util_String::getRandomChars($length);
    }
  }
  /**
   * insert
   * @param object $dataObject
   * @param string $callback
   * @param array $callbackParams
   * @throws spider_Exception
   */
  public function insert( &$dataObject, $callback=null, $callbackParams=array() ){
    $tableInformationHash = $this->getTableInformationHash( $dataObject );
    $serialFieldName = null;
    $inserFieldNameMap = array();
    $insertFieldValueArray = array();
    if( false !== $tableInformationHash ) {
      foreach ( $tableInformationHash as $fieldName => $fieldInformation ) {
        if( isset($fieldInformation['is_serial'])
        && $fieldInformation['is_serial'] === true ) {
          $serialFieldName = $fieldName;
        } else {
          if( $fieldInformation['has_default'] && is_null($dataObject->$fieldName) ){
          } else if( !$fieldInformation['not_null'] && is_null($dataObject->$fieldName) ){
          } else {
            $inserFieldNameMap[$fieldName] = '?';
            $insertFieldValueArray[$fieldName] = $dataObject->$fieldName;
          }
        }
      }
      if(is_null($serialFieldName) || strlen($serialFieldName)){
        $serialFieldName = $dataObject->getSerialField();
      }
      if ( count( $inserFieldNameMap ) > 0 ) {
        $query  = 'INSERT INTO '.$dataObject->getTableName()
        .' ( '.implode(',', array_keys($inserFieldNameMap) ).' )'
        .' VALUES ( '.implode(',', $inserFieldNameMap).' )';
        $connection  = $this->getProperConnection($query);
        $statement = $connection->prepare($query);
        $num = 1;
        foreach( $insertFieldValueArray as $fieldName => $value ){
          if( is_null($insertFieldValueArray[$fieldName]) ){
            $statement->bindParam( $num, $insertFieldValueArray[$fieldName], PDO::PARAM_NULL );
          } else if( preg_match('/int/',$tableInformationHash[$fieldName]['type']) > 0 ){
            $statement->bindParam( $num, $insertFieldValueArray[$fieldName], PDO::PARAM_INT );
          } else {
            $statement->bindParam( $num, $insertFieldValueArray[$fieldName] );
          }
          $num++;
        }
        $statement->execute();
        $statement->closeCursor();
        if( $serialFieldName ){
          $dataObject->$serialFieldName = $this->lastInsertId($dataObject->getTableName());
        }
        if( $callback ){
          if(!is_array($callbackParams)){
            if(!is_null($callbackParams)){
              $callbackParams = array($callbackParams);
            } else {
              $callbackParams = array();
            }
          }
          array_push($callbackParams,$this);
          call_user_func_array($callback, $callbackParams);
        }
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
  /**
   * insert using map
   */
  public function insertUsingMap($tableName, $fieldsMap){
    $fields = array_keys($fieldsMap);
    $query = sprintf('INSERT %s('.implode(',',$fields).')'
      .'VALUES(:'.implode(',:',$fields).')',$tableName);
    $connection  = $this->getProperConnection($query);
    $statement = $connection->prepare($query);
    foreach($fieldsMap as $field => $value){
      if(is_null($value)){
        $statement->bindValue(':'.$field,$value,PDO::PARAM_NULL);
      } else if(preg_match('/^[0-9]+$/',$value)>0){
        $statement->bindValue(':'.$field,$value,PDO::PARAM_INT);
      } else {
        $statement->bindValue(':'.$field,$value,PDO::PARAM_STR);
      }
    }
    $ret = $statement->execute();
    $statement->closeCursor();
    return $ret;
  }
  /**
   * update
   * @param system_Data $dataObject
   * @param mixed $callback
   * @param array $callbackParams
   */
  public function update( & $dataObject, $callback=null, $callbackParams=array() ) {
    $tableInformationHash = $this->getTableInformationHash( $dataObject );
    $serialFieldName = $this->getSerialField($dataObject->getTableName());
    if( is_null($serialFieldName) ){
      throw new spider_Exception('errors.db.common'
      , array($dataObject->getTableName().' don\'t have serial field!'));
    }
    $curDataObj = Loader::create(get_class($dataObject));
    $curDataObj = $this->loadBySerial($curDataObj, $dataObject->$serialFieldName);
    $updatedFieldHash = array();
    foreach( $tableInformationHash as $fieldName => $fieldInfoHash ){
      if( $serialFieldName != $fieldName ){
        if( $dataObject->$fieldName != $curDataObj->$fieldName ){
          // if the value is changed, add it to updating hash.
          $updatedFieldHash[$fieldName] = $dataObject->$fieldName;
        }
      }
    }
    $supplementalDataChanged = true;
    if( count($updatedFieldHash) == 0 && !$supplementalDataChanged){
      return true;
    } else {
      if( isset($dataObject->updated_date) ){
        $dataObject->updated_date = date('Y-m-d H:i:s');
      }
    }
    $stateStrArray = array();
    $updateValueArray = array();
    foreach( $updatedFieldHash as $fieldName => $value ){
      array_push($stateStrArray,$fieldName.'=:'.$fieldName);
      array_push($updateValueArray,$value);
      if( 'int' == strtolower($tableInformationHash[$fieldName]['type']) ){
        $this->bindParam(':'.$fieldName, $value, PDO::PARAM_INT);
      } else {
        $this->bindParam(':'.$fieldName, $value, PDO::PARAM_STR);
      }
    }
    $query = 'UPDATE '.$dataObject->getTableName().' SET '
    .implode(', ',$stateStrArray)
    .' WHERE '.$serialFieldName.'='.$this->quote($dataObject->$serialFieldName);
    $connection  = $this->getProperConnection($query);
    if( count($updatedFieldHash) > 0 ){
      $result = $this->query($query);
    }
    $this->closeCursor();
    // callback
    if( $callback ){
      if(!is_array($callbackParams)){
        if(!is_null($callbackParams)){
          $callbackParams = array($callbackParams);
        } else {
          $callbackParams = array();
        }
      }
      array_push($callbackParams,$this);
      call_user_func_array($callback, $callbackParams);
    }
    return true;
  }
}

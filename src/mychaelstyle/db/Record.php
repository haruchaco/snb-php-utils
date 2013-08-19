<?php
/**
 * データアクセスオブジェクトの基底抽象クラス
 * @package mychaelstyle
 * @subpackage db
 */
namespace mychaelstyle\db;
/**
 * データアクセスオブジェクトの基底抽象クラス
 * @package mychaelstyle
 * @subpackage db
 */
abstract class Record {
	/**
	 * constructor
	 * @return unknown_type
	 */
	function __construct(){
	}
	/**
	 * 本オブジェクトが保持するデータのデータベーステーブル名を取得します。
	 * テーブル名は実装クラス名を_で区切った末尾文字列に対して大文字部分を_[小文字]に置き換えた文字列です。
	 * 例えばDaoAdminMemberならadmin_memberとなります。
   * @since 5.0
	 */
	function getTableName() {
		$elements	= explode('_',get_class($this));
		$baseName	= array_pop($elements);
		$tableName	= preg_replace('/([A-Z])/','_${0}',$baseName);
		$tableName	= preg_replace('/^(.)*Dao\\_/','',$tableName);
		return strtolower($tableName);
	}
  /**
   * データベースの行と内容に差分があるか確認し差分があればtrueを返します。
   */
  public function isChanged(&$request){
    $dbo = $request->getAttribute('dbo');
    $uniqueIds = $this->getUniqueId();
    if(is_null($uniqueIds)){
      throw new Exception('Unique fields are not found!');
    } else if(is_array($uniqueIds) && count($uniqueIds)==0){
      throw new Exception('Unique fields are not found!');
    } else if(is_scalar($uniqueIds) && strlen($uniqueIds)==0){
      throw new Exception('Unique fields are not found!');
    }
    $whereClauses = array();
    foreach($uniqueIds as $key => $val){
      $whereClauses[] = $key.'=:'.$key;
    }
    $whereClauseString = implode(' AND ',$whereClauses);
    $sql = sprintf('SELECT * FROM %s WHERE %s',
      $this->getTableName(),
      $whereClauseString);
    foreach($uniqueIds as $key => $val){
      $dbo->bindValue(':'.$key,$val,PDO::PARAM_STR);
    }
    $row = $dbo->queryRow($sql);
    $excludes = $this->getExcludedFields();
    foreach($row as $key => $val){
      if(!in_array($key,$excludes) && $val != $this->$key){
        return true;
      }
    }
    return false;
  }
  /**
   * excluded fields to compare to
   * @return array fields names
   */
  public function getExcludedFields(){
    return array('updated_at','updated_date');
  }
  /**
   * 検索の場合にレコードを一意に特定するテーブルのユニークキーフィールド名配列を返します。
   * 組み合わせの場合は配列で返します。
   * @return array ('fieldName', 'fieldName',...)
   */
  public function getUniqueFields(){
    return null;
  }
  /**
   * 自動番号(auto_increment)のフィールド名を取得します。
   * @return string auto_increment フィールド名
   */
  public function getSerialField(){
    return 'serial_number';
  }
	/**
   * 検索の場合にレコードを一意に特定するテーブルのユニークキーを返します。
   * @return array ('fieldName'=>val, 'fieldName'=>val,...)
	 */
	public function getUniqueId(){
    $uniqueFields = $this->getUniqueFields();
    if(is_null($uniqueFields)){
      return null;
    } else if(is_array($uniqueFields)){
      $retVals = array();
      foreach($uniqueFields as $key){
        $retVals[$key] = $this->$key;
      }
      return $retVals;
    } else if(isset($this->$uniqueFields)){
      return array($this->$uniqueFields);
    }
    return null;
	}
	//
	// override
	//
	/**
	 * execute before loading a row
	 * @param spider_HttpRequest $request
	 */
  public function prepareLoad(&$request){
    return true;
  }
	/**
	 * execute after loading a row
	 * @param spider_HttpRequest $request
	 */
  public function finalizeLoad(&$request){
    $dbo = $request->getAttribute('dbo');
    $this->postLoad($request,$dbo);
    return true;
  }
	/**
	 * execute before insert
	 * @param spider_HttpRequest $request
	 */
	public function prepareInsert( &$request ){
		// TODO: write here prepare insert process
    return true;
	}
	/**
	 * execute after insert
	 * @param spider_HttpRequest $request
	 */
  public function finalizeInsert(&$request){
    $dbo = $request->getAttribute('dbo');
    $this->postInsert($request,$dbo);
    return true;
  }
	/**
	 * execute before update
	 * @param spider_HttpRequest $request
	 */
	public function prepareUpdate( &$request ){
		// TODO: write here prepare update process
    return true;
	}
	/**
	 * execute after update a row
	 * @param spider_HttpRequest $request
	 */
  public function finalizeUpdate(&$request){
    $dbo = $request->getAttribute('dbo');
    $this->postUpdate($request,$dbo);
    return true;
  }
	/**
	 * adjust fields after input
	 * @param spider_HttpRequest $request
	 */
	public function adjustFields(&$request){
		// TODO: write here to adjust fields after input
    return true;
	}
}

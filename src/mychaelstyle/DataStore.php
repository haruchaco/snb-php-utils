<?php
/**
 * datastore
 * @package mychaelstyle
 */
namespace mychaelstyle;

/**
 * datastore
 * @package mychaelstyle
 */
class DataStore {
  /**
   * @var mychaelstyle\datastore\Factory
   */
  private $factory = null;
  /**
   * DNS roots
   * @var array
   */
  private $dsn_map = array();
  /**
   * @var array providers map array('dsn'=>provider instance);
   */
  private $providers = array();
  /**
   * auto commit
   * @var boolean
   */
  private $auto_commit = true;
  /**
   * constructor
   * @param string $dsn ファイル保存先DNS
   * @param array $options 保存先接続オプション情報
   * @param boolean $autoCommit
   */
  public function __construct($dsn,$options,$autoCommit=true){
    $this->addProvider($dsn,$options);
    $this->auto_commit = $autoCommit;
    $this->factory = new datastore\Factory();
  }
  /**
   * Add root dns
   * @param string $dsn DSN
   * @param array $options オプション情報
   */
  public function addProvider($dsn,$options){
    $this->dsn_map[$dsn] = $options;
  }
  /**
   *
   */
  private function __getProviderInstance($dsn,$options=array()){
    if(isset($this->providers[$dsn])){
      return $this->providers[$dsn];
    }
    $this->providers[$dsn] = $this->factory->getProvider($dsn,$options);
    return $this->providers[$dsn];
  }
  /**
   * batch write items
   */
  public function batchWrite(array $datas){
    $keys = array();
    foreach($datas as $table => $rows){
      $keys[$table] = array();
      foreach($rows as $row){
        $fields = array_keys($row);
        $key = $fields[0];
        $val = $row[$key];
        $keys[$table][] = array($key=>$val);
      }
    }
    $orgs = $this->batchGet($keys);
    try{
      foreach($this->dsn_map as $dsn => $options){
        $provider = $this->__getProviderInstance($dsn,$options);
        $provider->batchWrite($datas);
      }
    }catch(\Exception $e){
      // if exception, rollback all
      foreach($this->dsn_map as $dsn => $options){
        try{
          $provider = $this->__getProviderInstance($dsn,$options);
          $provider->batchWrite($orgs);
        }catch(\Exception $ex){
          continue;
        }
      }
    }
  }
  /**
   * batch get items
   */
  public function batchGet(array $keys){
    foreach($this->dsn_map as $dsn => $options){
      try{
        $provider = $this->__getProviderInstance($dsn,$options);
        return $provider->batchGet($keys);
      }catch(\Exception $e){
        continue;
      }
    }
    throw new \mychaelstyle\Exception('Fail to get datas all providers');
  }
  /**
   * batch remove items
   */
  public function batchRemove(array $keys){
    foreach($this->dsn_map as $dsn => $options){
      try{
        $provider = $this->__getProviderInstance($dsn,$options);
        return $provider->batchRemove($keys);
      }catch(\Exception $e){
        continue;
      }
    }
  }
  /**
   * write a record
   */
  public function write($table,array $data){
    $datas = array($table => array($data));
    return $this->batchWrite($datas);
  }
  /**
   * get
   */
  public function get($table,$key){
    $keys = array(
      $table => array(
        $key
      )
    );
    $result = $this->batchGet($keys);
    return (isset($result[$table]) && isset($result[$table][0])) ? $result[$table][0] : null;
  }
  /**
   * remove
   */
  public function remove($table,$key){
    $keys = array(
      $table => array(
        $key
      )
    );
    $this->batchRemove($keys);
  }
}

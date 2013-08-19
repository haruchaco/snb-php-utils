<?php
/**
 * Queue provider for memcached
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
class Memcache implements \mychaelstyle\datastore\Provider {
  /**
   * @var Memcached
   */
  private $memcached = null;
  /**
   * @var int expire
   */
  private $expire = 604800;
  /**
   * constructor
   */
  public function __construct(){
  }
  /**
   * connection create
   */
  public function connect($uri,$options=array()){
    if(!class_exists('Memcached')){
      throw new \mychaelstyle\Exception('Class Memcached not found!');
    }
    $elms = explode(',',$uri);
    $hosts = array();
    foreach($elms as $elm){
      $elm = trim($elm);
      $hostelms = explode(':',$elm);
      $map = array(
        (isset($hostemls[0])?$hostelms[0]:''),
        (isset($hostemls[1])?$hostelms[1]:11211),
        (isset($hostemls[2])?$hostelms[2]:6),
      );
      if(strlen($map[0])>0){
        $hosts[] = $map;
      }
    }
    $prefix = isset($options['prefix'])?$options['prefix']:'ms_datastore_';
    $this->expire = isset($options['expire'])?$options['expire']:(60*60*24*7);
    $this->memcached = new \Memcached();
    $this->memcached->addServers($hosts);
    $this->memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
    $this->memcached->setOption(Memcached::DISTRIBUTION_CONSISTENT, true);
    $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix);
  }
  /**
   * disconnect
   */
  public function disconnect(){
    $this->memcached = null;
    $this->expire = 604800;
  }
  /**
   * batch write datas
   * @param array $datas
   */
  public function batchWrite(array $datas){
    foreach($datas as $table => $rows){
      foreach($rows as $row){
        // use first elment
        $keys = array_keys($row);
        $key  = $keys[0];
        $keyValue = $row[$key];
        $contents = json_encode($row);
				$this->memcached->set($keyValue,$contents,$this->expire);
      }
    }
  }
  public function batchGet(array $keys){
    $retMap = array();
    foreach($keys as $table => $rows){
      $retMap[$table] = isset($retMap[$table])?$retMap[$table]:array();
      foreach($rows as $row){
        // use first elment
        $keys = array_keys($row);
        $key  = $keys[0];
        $keyValue = $row[$key];
				$contents = $this->memcached->get($keyValue);
        $item = jcon_decode($contents);
        $retMap[$table][] = $item;
      }
    }
    return $retMap;
  }
  /**
   * batch remove
   */
  public function batchRemove(array $keys){
    foreach($keys as $table => $rows){
      foreach($rows as $row){
        // use first elment
        $keys = array_keys($row);
        $key  = $keys[0];
        $keyValue = $row[$key];
				$this->memcached->remove($keyValue);
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

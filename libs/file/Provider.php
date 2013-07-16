<?php
/**
 * snb\file\Provider.php abstract class file.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the Apache2 License. For more information please see
 * <http://github.com/haruchaco>
 */

namespace snb\file;

/**
 * 抽象ストレージプロバイダークラス
 * <p>
 * ストレージプロバイダの基底インターフェースを持つ抽象クラス。
 * すべてのストレージプロバイダクラスは本クラスの拡張クラスとして実装する。
 * </p>
 * <p>
 * 唯一の実装メソッドgetInstanceでは
 * DSNからストレージプロバイダを判断してプロバイダに接続済みのインスタンスを取得できる。
 * </p>
 * @author    Masanori Nakashima <>
 * @version   $Id$
 * @package   snb\file
 */
abstract class Provider {
  /**
   * @var string provider name
   */
  protected $provider_name = null;
  /**
   * @var string provider root path
   */
  protected $provider_root = null;
  /**
   * @var array options
   */
  protected $options = array();
  /**
   * @var array prividers
   */
  private static $providers = array();
   /**
   * get driver class instance
   * @param string $dsn
   * @param array $options
   * @return Object class extends Provider
   */
  public static function getInstance($dsn,$options=array()){
    if(isset(self::$providers[$dsn])){
      return self::$providers[$dsn];
    }
    $baseName = self::getProviderName($dsn);
    $file = dirname(__FILE__).'/providers/'.$baseName.'.php';
    $className = 'snb\\file\\providers\\'.$baseName;
    if(!class_exists($className)){
      if(file_exists($file)){
        require_once($file);
      } else {
        throw new Exception('File storage provider '.$baseName.' is not found!',0);
      }
    }
    $obj = new $className;
    $obj->connect($dsn,$options);
    self::$providers[$dsn] = $obj;
    return $obj;
  }

  /**
   * set DSN
   * @param string $dsn
   */
  protected function perseDsn($dsn){
    list($this->provider_name, $this->provider_root) = explode('://',$dsn);
    $this->provider_name = strtolower($this->provider_name);
    $name = strtolower(basename(str_replace('\\','/',(preg_replace('/([0-9a-z])([A-Z])/','${1}_${2}',get_class($this))))));
    if($name !== $this->provider_name){
      throw new \snb\file\Exception('Invalid dsn strings!',
        \snb\file\Exception::ERROR_PROVIDER_CONNECTION);
    } else if(strlen(trim($this->provider_root))==0){
      throw new \snb\file\Exception('The provider root is null!',
        \snb\file\Exception::ERROR_PROVIDER_CONNECTION);
    }
  }
 /**
   * get driver name
   * @param string $dsn
   * @return string driver name
   */
  public static function getProviderName($dsn){
    list($name) = explode('://',$dsn);
    $name = ucwords($name);
    $name = str_replace('_','',$name);
    return $name;
  }
	/**
	 * connect a storage provider
	 * @param string $dsn
	 */
	abstract public function connect($dsn,$options=array());
  /**
   * disconnect from a storage provider
   */
  abstract public function disconnect();
  /**
   * get contents from uri
   */
  abstract public function get($uri,$path=null);
	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	abstract public function put($srcPath,$dstUri,$options=array());
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	abstract public function remove($dstUri,$recursive=false);
}

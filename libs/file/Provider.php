<?php
namespace snb\file;
/**
 * 抽象ストレージプロバイダークラス
 *
 * ストレージプロバイダの基底インターフェースを持つ抽象クラス。
 * すべてのストレージプロバイダクラスは本クラスの拡張クラスとして実装する。
 *
 * 唯一の実装メソッドgetInstanceでは
 * DSNからストレージプロバイダを判断してプロバイダに接続済みのインスタンスを取得できる。
 *
 * @package snb\file
 * @auther Masanori Nakashima
 */
abstract class Provider {
  /**
   * prividers
   */
  private static $providers = array();
   /**
   * get driver class instance
   * @param string $dsn
   * @param array $options
   */
  public static function getInstance($dsn,$options=array()){
    if(isset(self::$providers[$dsn])){
      return self::$providers[$dsn];
    }
    $baseName = self::getProviderName($dsn);
    $file = dirname(__FILE__).'/providers/'.$baseName.'.php';
    $className = 'snb\\file\\providers\\'.$baseName;
    if(file_exists($file)){
      require_once($file);
      $obj = new $className;
      $obj->connect($dsn,$options);
      self::$providers[$dsn] = $obj;
      return $obj;
    } else {
      throw new Exception('File storage provider '.$baseName.' is not found!',0);
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

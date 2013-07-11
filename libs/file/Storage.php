<?php
namespace snb\file;
require_once(dirname(__FILE__).'/Exception.php');
require_once(dirname(__FILE__).'/File.php');
require_once(dirname(__FILE__).'/Provider.php');
/**
 * ファイルを扱うユーティリティクラス
 * 
 * @package snb\file
 */
class Storage {
  /**
   * DNS roots
   */
  private $dsn_map = array();
  /**
   * auto commit
   */
  private $auto_commit = true;
  /**
   * file object instances
   */
  private $files = array();
  /**
   * Constructor
   * @param string $dsn ファイル保存先ルートディレクトリDNS
   * @param array $options 保存先接続オプション情報
   */
  public function __construct($dsn,$options=array(),$autoCommit=true){
    $this->auto_commit = $autoCommit;
    $this->addProvider($dsn,$options);
  }
  /**
   * 指定URIのファイルオブジェクトを取得
   * @param string $uri
   * @param boolean $autoCommint
   */
  public function createFile($uri,array $options=array(),$autoCommit=false){
    if(!isset($this->files[$uri])){
      $this->files[$uri] = new File($this,$uri,$options,$autoCommit);
    }
    $obj = & $this->files[$uri];
    return $obj;
  }
  /**
   * Add root dns
   * @param string $dsn ファイル保存先ルートディレクトリDNS
   * @param array $options 保存先接続オプション情報
   */
  public function addProvider($dsn,$options=array()){
    if(!is_array($this->dsn_map)){
      $this->dsn_map = array();
    }
    $this->dsn_map[$dsn] = $options;
  }
  /**
   * Remove root dns
   * @param string $dsn ファイル保存先ルートディレクトリDNS
   */
  public function removeProvider($dsn){
    if(is_array($this->dsn_map) && isset($this->dsn_map[$dsn])){
      unset($this->dsn_map[$dsn]);
    }
  }
  /**
   * ローカルファイルを指定URIに送信
   * @param string $path ローカルファイルパス
   * @param string $uri リモートURI
   * @param array $options
   */
  public function put($path,$uri,$options=array()){
    foreach($this->dsn_map as $dsn => $defaultOptions){
      $options = array_merge($defaultOptions,$options);
      try{
        $provider = Provider::getInstance($dsn,$options);
        $provider->put($path,$uri);
      } catch(Exception $e){
        $this->remove($uri);
        throw new Exception('Commit failed! '.$dsn,0,$e);
      }
    }
  }
  /**
   * 指定URIのファイルを取得
   * @param string $uri リモートURI
   * @param string $path ローカルパス
   */
  public function get($uri,$path=null){
    $exceptions = array();
    foreach($this->dsn_map as $dsn => $options){
      try{
        $provider = Provider::getInstance($dsn,$options);
        return $provider->get($uri,$path);
      } catch(Exception $e){
        $exceptions[] = $e;
      }
    }
    if(count($exceptions)>0){
      throw new Exception('Fail to get file! '.$uri,0,null,$exceptions);
    }
  }
  /**
   * 指定URIのファイルを削除
   * @param string $uri リモートURI
   */
  public function remove($uri){
    $messages = '';
    foreach($this->dsn_map as $dsn => $options){
      try{
        $provider = Provider::getInstance($dsn,$options);
        $provider->remove($uri);
      } catch(Exception $e){
        $messages .= ':'.$e->getCode().' '.$e->getMessage();
      }
    }
    if(strlen($messages)>0){
      trigger_error('Fail to remove. '.$messages,E_USER_NOTICE);
    }
  }
  /**
   * put contents to uri. like file_put_contents
   * @param string $uri
   * @param string $contents
	 * @param boolean $recursive
   */
  public function putContents($uri,$contents,$options=array()){
    $tmp = tempnam(sys_get_temp_dir(),'snb_tmp_');
    if(false === file_put_contents($tmp,$contents)){
      $this->put($uri,$tmp,$options);
    }
  }
  /**
   * get uri contents as strings. move like file_get_contents
   * @param string $uri
   * @return string file contents
   */
  public function getContents($uri){
    $tmp = tempnam(sys_get_temp_dir(),'snb_tmp_');
    $this->get($uri,$tmp);
    return file_get_contents($tmp);
  }
  /**
   * comit at close a file.
   */
  public function commit(){
    foreach($this->files as $file){
      $file->commit();
    }
  }
  /**
   * rollback
   */
  public function rolback(){
    foreach($this->files as $file){
      $file->rollback();
    }
  }
}

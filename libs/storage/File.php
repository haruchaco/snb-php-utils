<?php
/**
 * snb\storage\File.php class file.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the Apache2 License. For more information please see
 * <http://github.com/haruchaco>
 *
 * @author  Masanori Nakashima <>
 * @version $Id$
 * @package snb
 * @subpackage storage
 */

namespace snb\storage;
require_once(dirname(dirname(__FILE__)).'/Storage.php');
require_once(dirname(__FILE__).'/Exception.php');

/**
 * File object class for all storages.
 *
 * <p>
 * You can operate the files as local files.<br>
 * ストレージ内ファイルを扱うオブジェクトクラス
 * </p>
 * <p>
 * This object supports transaction.
 * このオブジェクトはトランザクション処理をサポートします。
 * </p>
 * 
 * @author  Masanori Nakashima <>
 * @version $Id$
 * @package snb
 * @subpackage storage
 */
class File {
  /**
   * target storage object.
   * @var Storage
   */
  private $storage = null;
  /**
   * file uri on all storages
   * @var string
   */
  private $uri = null;
  /**
   * file options for uploading
   * @var array
   */
  private $options = array();
  /**
   * temporary file path for edit
   * @var string
   */
  private $tmp = null;
  /**
   * original file backup path before edit if exists.
   * @var string
   */
  private $previous = null;
  /**
   * file handle of the temporary file
   * @var resource
   */
  private $handle = null;
  /**
   * file handle open mode. rwa+-
   * @var string
   */
  private $mode = 'r';
  /**
   * auto commit
   * @var boolean
   */
  private $auto_commit = true;
  /**
   * Constructor
   * @param Storage $storage
   * @param string $uri
   * @param array $options 保存先オプション情報
   * @param boolean $autoCommit
   */
  public function __construct(\snb\Storage $storage,$uri,array $options=array(),$autoCommit=true){
    $this->storage = $storage;
    $this->uri = $uri;
    $this->options = $options;
    $this->auto_commit = $autoCommit;
    $this->initialize();
  }
  /**
   * open file
   * @param string $mode mode of fopen
   */
  public function open($mode='r'){
    if($this->isOpened()){
      throw new Exception('This instance already open another uri file!');
    }
    $this->mode = $mode;
    $this->handle  = @fopen($this->tmp, $this->mode);
    if( false === $this->handle ){
      throw new Exception('Fail to open file '.$this->tmp);
    } else {
      $lr = true;
      if( strpos($mode,'r') !== false ){
        $lr = @flock($this->handle,LOCK_SH);
      } else {
        $lr = @flock($this->handle,LOCK_EX);
      }
      if( false === $lr ){
        throw new Exception('Fail to lock file! '.$this->tmp);
      }
    }
  }
  /**
   * write. like fwrite
   * @param string $strings
   */
  public function write($strings){
    $this->checkOpen();
    return fwrite($this->handle,$strings);
  }
  /**
   * gets
   * @param int $length
   */
  public function gets($length=null){
    $this->checkOpen();
    if( is_null($length) ) {
      return fgets($this->handle);
    } else {
      return fgets($this->handle, $length+1);
    }
  }
  /**
   * eof
   * @return boolean true if the handle at end of file.
   */
  public function eof(){
    $this->checkOpen();
    return feof($this->handle);
  }
  /**
   * close file handle
   */
  public function close(){
    $this->checkOpen();
		@flock($this->handle, LOCK_UN);
		@fclose($this->handle);
    $this->handle = null;
		// if read only, return
		if(strpos($this->mode,'r') !== false){
			return true;
		}
    if($this->auto_commit){
      $this->commit();
    }
  }
  /**
   * import a local file.
   * this method does not require to open.
   * @param string $path local file path
   */
  public function import($path){
    @unlink($this->tmp);
    copy($path,$this->tmp);
    if($this->auto_commit){
      $this->commit();
    }
  }
  /**
   * put contents to this file.
   * this method does not require to open.
   * @param string $contents
   */
  public function putContents($contents){
    if(false === file_put_contents($this->tmp,$contents)){
      throw new Exception('Fail to write local temp file! '.$this->tmp.' '.$this->uri,0,null);
    }
    if($this->auto_commit){
      $this->commit();
    }
  }
  /**
   * get contents from this file.
   * this method does not need to open.
   * @return string file contents
   */
  public function getContents(){
    return file_get_contents($this->tmp);
  }
  /**
   * remove this file.
   */
  public function remove(){
    if(!is_null($this->tmp) && file_exists($this->tmp)){
      @unlink($this->tmp);
    }
    $this->tmp = null;
    if($this->auto_commit){
      $this->commit();
    }
  }
  /**
   * comit and close a file.
   */
  public function commit(){
    if(is_null($this->tmp) || !file_exists($this->tmp)){
      $this->storage->remove($this->uri);
    } else {
      $this->storage->put($this->tmp,$this->uri,$this->options);
    }
    $this->clean();
    $this->initialize();
  }
  /**
   * rollback
   */
  public function rollback(){
    $this->storage->remove($this->uri,$this->options);
    if(is_null($this->previous)){
      $this->storage->remove($this->uri);
    } else {
      $this->storage->put($this->previous,$this->uri,$this->options);
    }
    $this->clean();
    $this->initialize();
  }
  /**
   * clean
   */
  public function clean(){
    if(!is_null($this->tmp) && file_exists($this->tmp)){
      @unlink($this->tmp);
    }
    if(!is_null($this->previous) && file_exists($this->previous)){
      @unlink($this->previous);
    }
  }
  /**
   * initialize temporary files
   */
  public function initialize(){
    $this->tmp  = tempnam(sys_get_temp_dir(),'snb_tmp_');
    $this->previous = tempnam(sys_get_temp_dir(),'snb_priv_');
    try{
      $this->storage->get($this->uri,$this->previous);
    } catch(Exception $e){
      // nothing to do
    }
    clearstatcache();
    if(file_exists($this->previous) && filesize($this->previous)>0){
      if(file_exists($this->tmp)){
        @unlink($this->tmp);
      }
      copy($this->previous,$this->tmp);
    } else {
      @unlink($this->previous);
      $this->previous = null;
      touch($this->tmp);
    }
  }
  /**
   * is opened
   * @return boolean true if already opend
   */
  private function isOpened(){
    if(is_null($this->handle)){
      return false;
    }
    return true;
  }
  /**
   * check this instance has already open a file.
   * @throws snb\storage\Exception
   */
  private function checkOpen(){
    if(!$this->isOpened()){
      throw new Exception('This instance has not opened yet!');
    }
  }
}
